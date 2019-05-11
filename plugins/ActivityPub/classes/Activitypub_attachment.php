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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * ActivityPub Attachment representation
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_attachment extends Managed_DataObject
{
    /**
     * Generates a pretty array from an Attachment object
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param Attachment $attachment
     * @return array pretty array to be used in a response
     */
    public static function attachment_to_array($attachment)
    {
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type'      => 'Document',
            'mediaType' => $attachment->mimetype,
            'url'       => $attachment->getUrl(),
            'size'      => $attachment->getSize(),
            'name'      => $attachment->getTitle(),
        ];

        // Image
        if (substr($res["mediaType"], 0, 5) == "image") {
            $res["meta"]= [
                'width'  => $attachment->width,
                'height' => $attachment->height
            ];
        }

        return $res;
    }
}
