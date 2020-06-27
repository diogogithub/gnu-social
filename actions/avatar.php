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
 * Retrieve user avatar by nickname action class.
 *
 * @category Action
 * @package  GNUsocial
 *
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 */
if (!defined('GNUSOCIAL')) {
    exit(1);
}

/**
 * Retrieve user avatar by nickname action class.
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
    public $filename;
    protected function prepare(array $args = [])
    {
        parent::prepare($args);
        if (empty($this->filename = $this->trimmed('file'))) {
            // TRANS: Client error displayed trying to get a non-existing avatar.
            $this->clientError(_m('No such avatar.'), 404);
        }
        return true;
    }

    protected function handle()
    {
        parent::handle();

        if (is_string($srv = common_config('avatar', 'server')) && $srv != '') {
            common_redirect(Avatar::url($this->filename), 302);
        } else {
            $filepath = common_config('avatar', 'dir') . $this->filename;
            $info = @getimagesize($filepath);
            if ($info !== false) {
                common_send_file($filepath, $info['mime'], $this->filename, 'inline');
            } else {
                throw new UnsupportedMediaException(_m("Avatar is not an image."));
            }
        }
        return true;
    }
}
