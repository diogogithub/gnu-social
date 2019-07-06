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
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Utility class to get OpenGraph data from HTML DOMs etc.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OpenGraphHelper
{
    const KEY_REGEX = '/^og\:(\w+(?:\:\w+)?)/';
    protected static $property_map = [
                            'site_name'      => 'provider_name',
                            'title'          => 'title',
                            'description'    => 'html',
                            'type'           => 'type',
                            'url'            => 'url',
                            'image'          => 'thumbnail_url',
                            'image:height'   => 'thumbnail_height',
                            'image:width'    => 'thumbnail_width',
                            ];

    // This regex map has:    /pattern(match)/ => matchindex | string
    protected static $type_regex_map = [
                            '/^(video)/'   => 1,
                            '/^image/'     => 'photo',
                            ];

    public static function ogFromHtml(DOMDocument $dom)
    {
        $obj = new stdClass();
        $obj->version = '1.0';  // fake it til u make it

        $nodes = $dom->getElementsByTagName('meta');
        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            if (!$node->hasAttributes()) {
                continue;
            }
            $property = $node->attributes->getNamedItem('property');
            $matches = array();
            if ($property === null || !preg_match(self::KEY_REGEX, $property->value, $matches)) {
                // not property="og:something"
                continue;
            }
            if (!isset(self::$property_map[$matches[1]])) {
                // unknown metadata property, nothing we would care about anyway
                continue;
            }

            $prop = self::$property_map[$matches[1]];
            $obj->{$prop} = $node->attributes->getNamedItem('content')->value;
            // I don't care right now if they're empty
        }
        if (isset($obj->type)) {
            // Loop through each known OpenGraph type where we have a match in oEmbed
            foreach (self::$type_regex_map as $pattern=>$replacement) {
                $matches = array();
                if (preg_match($pattern, $obj->type, $matches)) {
                    $obj->type = is_int($replacement)
                                    ? $matches[$replacement]
                                    : $replacement;
                    break;
                }
            }
            // If it's not known to our type map, we just pass it through in hopes of it getting handled anyway
        } elseif (isset($obj->url)) {
            // If no type is set but we have a URL, let's set type=link
            $obj->type = 'link';
        }
        return $obj;
    }
}
