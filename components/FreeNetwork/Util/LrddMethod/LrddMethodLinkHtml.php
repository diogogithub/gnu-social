<?php

declare(strict_types = 1);

namespace Component\FreeNetwork\Util\LrddMethod;

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
use Component\FreeNetwork\Util\LrddMethod;
use XML_XRD_Element_Link;

/**
 * Implementation of discovery using HTML <link> element
 *
 * Discovers XRD file for a user by fetching the URL and reading any
 * <link> elements in the HTML response.
 *
 * @category  Discovery
 * @package   GNUsocial
 *
 * @author    James Walker <james@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class LrddMethodLinkHtml extends LRDDMethod
{
    /**
     * For HTTP IDs, fetch the URL and look for <link> elements
     * in the HTML response.
     *
     * @todo fail out of WebFinger URIs faster
     */
    public function discover($uri)
    {
        $response = self::fetchUrl($uri);

        return self::parse($response->getContent());
    }

    /**
     * Parse HTML and return <link> elements
     *
     * Given an HTML string, scans the string for <link> elements
     *
     * @param string $html HTML to scan
     *
     * @return array array of associative arrays in JRD-ish array format
     */
    public function parse($html)
    {
        $links = [];

        preg_match('/<head(\s[^>]*)?>(.*?)<\/head>/is', $html, $head_matches);

        if (\count($head_matches) != 3) {
            return [];
        }
        [, , $head_html] = $head_matches;

        preg_match_all('/<link\s[^>]*>/i', $head_html, $link_matches);

        foreach ($link_matches[0] as $link_html) {
            $link_url  = null;
            $link_rel  = null;
            $link_type = null;

            preg_match('/\srel=(("|\')([^\\2]*?)\\2|[^"\'\s]+)/i', $link_html, $rel_matches);
            if (\count($rel_matches) > 3) {
                $link_rel = $rel_matches[3];
            } elseif (\count($rel_matches) > 1) {
                $link_rel = $rel_matches[1];
            }

            preg_match('/\shref=(("|\')([^\\2]*?)\\2|[^"\'\s]+)/i', $link_html, $href_matches);
            if (\count($href_matches) > 3) {
                $link_uri = $href_matches[3];
            } elseif (\count($href_matches) > 1) {
                $link_uri = $href_matches[1];
            }

            preg_match('/\stype=(("|\')([^\\2]*?)\\2|[^"\'\s]+)/i', $link_html, $type_matches);
            if (\count($type_matches) > 3) {
                $link_type = $type_matches[3];
            } elseif (\count($type_matches) > 1) {
                $link_type = $type_matches[1];
            }

            $links[] = new XML_XRD_Element_Link($link_rel, $link_uri, $link_type);
        }

        return $links;
    }
}
