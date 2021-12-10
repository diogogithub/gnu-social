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
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Conversation\Controller;

use _PHPStan_76800bfb5\Nette\NotImplementedException;
use App\Core\Controller\FeedController;
use App\Core\DB\DB;
use App\Core\Form;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\ServerException;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use App\Util\Form\FormFields;
use Component\Posting\Posting;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class Conversation extends FeedController
{
    // if note is root -> just link
    // if note is a reply -> link from above plus anchor
    public function ConversationShow(Request $request)
    {
        throw new NotImplementedException();
        $actor_id = Common::ensureLoggedIn()->getId();
        $notes    = DB::dql('select n from App\Entity\Note n '
            . 'where n.reply_to is not null and n.actor_id = :id '
            . 'order by n.created DESC', ['id' => $actor_id], );
        return [
            '_template'  => 'feeds/feed.html.twig',
            'notes'      => $notes,
            'should_format' => false,
            'page_title' => 'Replies feed',
        ];
    }
}
