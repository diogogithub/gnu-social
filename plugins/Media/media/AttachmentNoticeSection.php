<?php
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

/**
 * FIXME
 *
 * @category  Widget
 * @package   GNUsocial
 *
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Media\media;

/**
 * FIXME
 *
 * These are the widgets that show interesting data about a person * group, or site.
 *
 * @category  Widget
 * @package   GNUsocial
 *
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentNoticeSection // extends NoticeSection
{
    public function showContent()
    {
        // parent::showContent();
        return false;
    }

    public function getNotices()
    {
        $notice = new Notice;

        $notice->joinAdd(['id', 'file_to_post:post_id']);
        $notice->whereAdd(sprintf('file_to_post.file_id = %d', $this->out->attachment->id));

        $notice->selectAdd('notice.id');
        $notice->orderBy('notice.created DESC, notice.id DESC');
        $notice->find();
        return $notice;
    }

    public function title()
    {
        // TRANS: Title.
        return _('Notices where this attachment appears');
    }

    public function divId()
    {
        return 'attachment_section';
    }
}
