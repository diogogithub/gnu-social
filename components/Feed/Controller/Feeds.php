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

namespace Component\Feed\Controller;

use function App\Core\I18n\_m;
use App\Util\Common;
use Component\Collection\Util\Controller\FeedController;
use Symfony\Component\HttpFoundation\Request;

class Feeds extends FeedController
{
    /**
     * The Planet feed represents every local post. Which is what this instance has to share with the universe.
     */
    public function public(Request $request): array
    {
        $data = $this->query(
            query: 'note-local:true',
            language: Common::actor()?->getTopLanguage()?->getLocale(),
        );
        return [
            '_template'  => 'collection/notes.html.twig',
            'page_title' => _m(\is_null(Common::user()) ? 'Feed' : 'Planet'),
            'notes'      => $data['notes'],
        ];
    }

    /**
     * The Home feed represents everything that concerns a certain actor (its subscriptions)
     */
    public function home(Request $request): array
    {
        $user  = Common::ensureLoggedIn();
        $actor = $user->getActor();
        $data  = $this->query(
            query: 'note-from:subscribed-person,subscribed-group,subscribed-organization,subscribed-business',
            language: $actor->getTopLanguage()->getLocale(),
            actor: $actor,
        );
        return [
            '_template'  => 'collection/notes.html.twig',
            'page_title' => _m('Home'),
            'notes'      => $data['notes'],
        ];
    }
}
