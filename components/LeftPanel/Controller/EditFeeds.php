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

namespace Component\LeftPanel\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\Controller\FeedController;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Router\Router;
use App\Entity\Feed;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use Functional as F;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class EditFeeds extends Controller
{
    /**
     * Controller for editing the list of feeds in the user's left
     * panel. Adds and removes `\App\Entity\Feed`s as appropriate
     */
    public function __invoke(Request $request)
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

            /** @var SubmitButton $update_existing */
            $update_existing = $form->get('update_exisiting');
            if ($update_existing->isClicked()) {
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

            // TODO fix orderings when removing
            // Remove feed
            foreach ($form_definitions as [$field, $type, $opts]) {
                if (str_ends_with($field, '-url')) {
                    $remove_id = str_replace('-url', '-remove', $field);
                    /** @var SubmitButton $remove_button */
                    $remove_button = $form->get($remove_id);
                    if ($remove_button->isClicked()) {
                        // @phpstan-ignore-next-line -- Doesn't quite understand that _this_ $opts for the current $form_definitions does have 'data'
                        DB::remove(DB::getReference('feed', ['actor_id' => $user->getId(), 'url' => $opts['data']]));
                        DB::flush();
                        Cache::delete($key);
                        throw new RedirectException();
                    }
                }
            }

            /** @var SubmitButton $reset_button */
            $reset_button = $form->get('reset');
            if ($reset_button->isClicked()) {
                F\map(DB::findBy('feed', ['actor_id' => $user->getId()]), fn ($f) => DB::remove($f));
                DB::flush();
                Cache::delete($key);
                Feed::createDefaultFeeds($user->getId(), $user);
                DB::flush();
                throw new RedirectException();
            }

            // Add feed
            try {
                $match      = Router::match($data['url']);
                $route      = $match['_route'];
                $controller = $match['_controller'];
                if (!is_subclass_of($controller, FeedController::class)) {
                    throw new ClientException(_m('The page with url "{url}" is not a valid feed', ['{url}' => $data['url']]));
                }
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
                throw new ClientException(_m('Invalid route with url "{url}"', ['{url}' => $data['url']]), code: 404);
            }
        }

        return [
            '_template'  => 'left_panel/edit_feeds.html.twig',
            'edit_feeds' => $form->createView(),
        ];
    }
}
