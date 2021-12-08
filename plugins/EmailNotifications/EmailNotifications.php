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
 * Handle email notifications
 *
 * @package  GNUsocial
 * @category Plugin
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\EmailNotifications;

use App\Core\Event;
use App\Core\Modules\Plugin;

class EmailNotifications extends Plugin
{
    public function onAddNotificationTransport(&$form_defs): bool
    {
        $form_defs['Email']   = $form_defs['placeholder'];
        $form_defs['Email'][] = $form_defs['placeholder']['save']('Email', 'save_email');
        unset($form_defs['Email']['post_on_status_change']);
        return Event::next;
    }
}
