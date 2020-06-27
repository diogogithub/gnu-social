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
 * Retrieve user avatar by filename action class.
 *
 * @category Action
 * @package  GNUsocial
 *
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 */
defined('GNUSOCIAL') || die;

/**
 * Retrieve user avatar by filename action class.
 *
 * @category Action
 * @package  GNUsocial
 *
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <millette@status.net>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @author   Hugo Sales <hugo@fc.up.pt>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 *
 * @see     http://www.gnu.org/software/social/
 */
class AvatarAction extends Action
{
    public $filename = null;
    public $filepath = null;
    public $mimetype = null;

    protected function prepare(array $args = [])
    {
        parent::prepare($args);
        $this->filename = File::tryFilename($this->trimmed('file'));
        $this->filepath = File::path($this->filename, common_config('avatar', 'dir'), false);
        if (!file_exists($this->filepath)) {
            // TRANS: Client error displayed trying to get a non-existing avatar.
            $this->clientError(_m('No such avatar.'), 404);
        }
        $this->mimetype = (new ImageFile(-1, $this->filepath))->mimetype;

        return true;
    }

    protected function handle()
    {
        parent::handle();

        common_send_file($this->filepath, $this->mimetype, $this->filename, 'inline');
    }
}
