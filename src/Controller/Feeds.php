<?php

declare(strict_types = 1);

// {{{ License

// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

// }}}

/**
 * Handle network public feed
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Eliseu Amaro <eliseu@fc.up.pt>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Router\Router;
use App\Core\VisibilityScope;
use App\Entity\Feed;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\NotImplementedException;
use App\Util\Exception\RedirectException;
use Functional as F;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Feeds extends Controller
{
    // Can't have constants inside herestring
    private $public_scope     = VisibilityScope::PUBLIC;
    private $instance_scope   = VisibilityScope::PUBLIC | VisibilityScope::SITE;
    private $message_scope    = VisibilityScope::MESSAGE;
    private $subscriber_scope = VisibilityScope::PUBLIC | VisibilityScope::SUBSCRIBER;

    public function public(Request $request)
    {
        $notes = Note::getAllNotes($this->instance_scope);

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template'  => 'feeds/feed.html.twig',
            'notes'      => $notes_out,
            'page_title' => 'Public feed',
        ];
    }

    public function home(Request $request, string $nickname)
    {
        try {
            $target = DB::findOneBy('actor', ['nickname' => $nickname]);
        } catch (NotFoundException) {
            throw new ClientException(_m('User {nickname} doesn\'t exist', ['{nickname}' => $nickname]));
        }

        // TODO Handle replies in home stream
        $query = <<<END
                    -- Select notes from:
                    select note.* from note left join -- left join ensures all returned notes' ids are not null
                    (
                        -- Subscribed by target
                        select n.id from note n inner join subscription f on n.actor_id = f.subscribed
                            where f.subscriber = :target_actor_id
                        union all
                        -- Replies to notes by target
                        -- select n.id from note n inner join note nr on nr.id = nr.reply_to
                        -- union all
                        -- Notifications to target
                        select a.activity_id from notification a inner join note n on a.activity_id = n.id
                        union all
                        -- Notes in groups target subscriptions
                        select gi.activity_id from group_inbox gi inner join group_member gm on gi.group_id = gm.group_id
                            where gm.actor_id = :target_actor_id
                    )
                    as s on s.id = note.id
                    where
                        -- Remove direct messages
                        note.scope <> {$this->message_scope}
                    order by note.modified DESC
            END;
        $notes = DB::sql($query, ['target_actor_id' => $target->getId()]);

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template'  => 'feeds/feed.html.twig',
            'notes'      => $notes_out,
            'page_title' => 'Home feed',
        ];
    }

    public function network(Request $request)
    {
        $notes = Note::getAllNotes($this->public_scope);

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template'  => 'feeds/feed.html.twig',
            'notes'      => $notes_out,
            'page_title' => 'Network feed',
        ];
    }

    public function edit_feeds(Request $request)
    {
        $user  = Common::ensureLoggedIn();
        $key   = Feed::cacheKey($user);
        $feeds = Feed::getFeeds($user);

        $form_definitions = [];
        foreach ($feeds as $feed) {
            $md5                = md5($feed->getUrl());
            $form_definitions[] = [$md5 . '-url', TextType::class, ['data' => $feed->getUrl(), 'label' => _m('URL'), 'block_prefix' => 'row_url']];
            $form_definitions[] = [$md5 . '-order', IntegerType::class, ['data' => $feed->getOrdering(), 'label' => _m('Order'), 'block_prefix' => 'row_order']];
            $form_definitions[] = [$md5 . '-title', TextType::class, ['data' => $feed->getTitle(), 'label' => _m('Title'), 'block_prefix' => 'row_title']];
            $form_definitions[] = [$md5 . '-remove', SubmitType::class, ['label' => _m('Remove'), 'block_prefix' => 'row_remove']];
        }

        $form_definitions[] = ['url', TextType::class, ['label' => _m('New feed'), 'required' => false]];
        $form_definitions[] = ['order', IntegerType::class, ['label' => _m('Order'), 'data' => (\count($form_definitions) / 4) + 1]];
        $form_definitions[] = ['title', TextType::class, ['label' => _m('Title'), 'required' => false]];
        $form_definitions[] = ['add', SubmitType::class, ['label' => _m('Add')]];
        $form_definitions[] = ['update_exisiting', SubmitType::class, ['label' => _m('Update existing')]];
        $form_definitions[] = ['reset', SubmitType::class, ['label' => _m('Reset to default values')]];

        $form = Form::create($form_definitions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            array_pop($form_definitions);
            array_pop($form_definitions);
            array_pop($form_definitions);
            array_pop($form_definitions);
            array_pop($form_definitions);

            $data = $form->getData();

            if ($form->get('update_exisiting')->isClicked()) {
                // Each feed has a URL, an order and a title
                $feeds_data = array_chunk($data, 3, preserve_keys: true);
                // The last three would be the new one
                array_pop($feeds_data);
                // Sort by the order
                usort($feeds_data, fn ($fd_l, $fd_r) => next($fd_l) <=> next($fd_r));
                // Make the order sequential
                $order = 1;
                foreach ($feeds_data as $i => $fd) {
                    next($fd);
                    $feeds_data[$i][key($fd)] = $order++;
                }
                // Update the fields in the corresponding feed
                foreach ($feeds_data as $fd) {
                    $md5  = str_replace('-url', '', array_key_first($fd));
                    $feed = F\first($feeds, fn ($f) => md5($f->getUrl()) === $md5);
                    $feed->setUrl($fd[$md5 . '-url']);
                    $feed->setOrdering($fd[$md5 . '-order']);
                    $feed->setTitle($fd[$md5 . '-title']);
                    DB::merge($feed);
                }
                DB::flush();
                Cache::delete($key);
                throw new RedirectException();
            }

            // Remove feed
            foreach ($form_definitions as [$field, $type, $opts]) {
                if (str_ends_with($field, '-url')) {
                    $remove_id = str_replace('-url', '-remove', $field);
                    if ($form->get($remove_id)->isClicked()) {
                        DB::remove(DB::getReference('feed', ['actor_id' => $user->getId(), 'url' => $opts['data']]));
                        DB::flush();
                        Cache::delete($key);
                        throw new RedirectException();
                    }
                }
            }

            if ($form->get('reset')->isClicked()) {
                F\map(DB::findBy('feed', ['actor_id' => $user->getId()]), fn ($f) => DB::remove($f));
                DB::flush();
                Cache::delete($key);
                Feed::createDefaultFeeds($user->getId(), $user);
                DB::flush();
                throw new RedirectException();
            }

            // Add feed
            try {
                $match = Router::match($data['url']);
                $route = $match['_route'];
                DB::persist(Feed::create([
                    'actor_id' => $user->getId(),
                    'url'      => $data['url'],
                    'route'    => $route,
                    'title'    => $data['title'],
                    'ordering' => $data['order'],
                ]));
                DB::flush();
                Cache::delete($key);
                throw new RedirectException();
            } catch (ResourceNotFoundException) {
                // throw new ClientException(_m('Invalid route'));
                // continue bellow
            }
        }

        return [
            '_template'  => 'feeds/edit_feeds.html.twig',
            'edit_feeds' => $form->createView(),
        ];
    }

    public function replies(Request $request)
    {
        // TODO replies
        throw new NotImplementedException;
        $actor_id = Common::ensureLoggedIn()->getId();
        $notes    = DB::dql('select n from App\Entity\Note n '
                         . 'where n.reply_to is not null and n.actor_id = :id '
                         . 'order by n.created DESC', ['id' => $actor_id], );

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template'  => 'feeds/feed.html.twig',
            'notes'      => $notes_out,
            'page_title' => 'Replies feed',
        ];
    }
}
