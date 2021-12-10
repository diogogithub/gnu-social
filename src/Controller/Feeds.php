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

use App\Core\Controller\FeedController;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Core\VisibilityScope;
use App\Entity\Note;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;

class Feeds extends FeedController
{
    // Can't have constants inside herestring
    private $public_scope     = VisibilityScope::PUBLIC;
    private $instance_scope   = VisibilityScope::PUBLIC | VisibilityScope::SITE;
    private $message_scope    = VisibilityScope::MESSAGE;
    private $subscriber_scope = VisibilityScope::PUBLIC | VisibilityScope::SUBSCRIBER;

    public function public(Request $request)
    {
        $notes = Note::getAllNotes($this->instance_scope);
        return [
            '_template'  => 'feeds/feed.html.twig',
            'page_title' => 'Public feed',
            'should_format' => true,
            'notes'      => $notes,
        ];
    }

    public function home(Request $request, string $nickname)
    {
        try {
            $target = DB::findOneBy('actor', ['nickname' => $nickname, 'is_local' => true]);
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

        return [
            '_template'  => 'feeds/feed.html.twig',
            'page_title' => 'Home feed',
            'should_format' => true,
            'notes'      => $notes,
        ];
    }

    public function network(Request $request)
    {
        $notes = Note::getAllNotes($this->public_scope);
        return [
            '_template'  => 'feeds/feed.html.twig',
            'page_title' => 'Network feed',
            'should_format' => true,
            'notes'      => $notes,
        ];
    }
}
