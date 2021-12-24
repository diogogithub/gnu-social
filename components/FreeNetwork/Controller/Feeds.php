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
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\FreeNetwork\Controller;

use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Util\Common;
use Component\Feed\Feed;
use Component\Feed\Util\FeedController;
use Symfony\Component\HttpFoundation\Request;

class Feeds extends FeedController
{
    /**
     * The Meteorites feed represents every post coming from the
     * known fediverse to this instance's inbox. I.e., it's our
     * known network and excludes everything that is local only
     * or federated out.
     */
    public function network(Request $request): array
    {
        Common::ensureLoggedIn();
        $data = Feed::query(
            query: 'note-local:false',
            page: $this->int('p'),
            language: Common::actor()?->getTopLanguage()?->getLocale(),
        );
        return [
            '_template'     => 'feed/feed.html.twig',
            'page_title'    => _m('Meteorites'),
            'should_format' => true,
            'notes'         => $data['notes'],
        ];
    }

    /**
     * The Planetary System feed represents every planet-centric post, i.e.,
     * everything that is local or comes from outside with relation to local actors
     * or posts.
     */
    public function clique(Request $request): array
    {
        Common::ensureLoggedIn();
        $notes = DB::dql(
            <<<'EOF'
                SELECT n FROM \App\Entity\Note AS n
                WHERE n.is_local = true OR n.id IN (
                    SELECT act.object_id FROM \App\Entity\Activity AS act
                        WHERE act.object_type = 'note' AND act.id IN
                            (SELECT att.activity_id FROM \Component\Notification\Entity\Notification AS att WHERE att.target_id IN 
                                (SELECT a.id FROM \App\Entity\Actor a WHERE a.is_local = true))
                    )
                ORDER BY n.created DESC, n.id DESC
                EOF,
        );
        return [
            '_template'     => 'feed/feed.html.twig',
            'page_title'    => _m('Planetary System'),
            'should_format' => true,
            'notes'         => $notes,
        ];
    }

    /**
     * The Galaxy feed represents everything that is federated out or federated in.
     * Given that any local post can be federated out and it's hard to specifically exclude these,
     * we simply return everything here, local and remote posts. So, a galaxy.
     */
    public function federated(Request $request): array
    {
        Common::ensureLoggedIn();
        $data = Feed::query(
            query: '',
            page: $this->int('p'),
            language: Common::actor()?->getTopLanguage()?->getLocale(),
        );
        return [
            '_template'     => 'feed/feed.html.twig',
            'page_title'    => _m('Galaxy'),
            'should_format' => true,
            'notes'         => $data['notes'],
        ];
    }
}
