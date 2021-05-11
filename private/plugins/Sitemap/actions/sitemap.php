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
 * Superclass for sitemap-generating actions
 *
 * @category  Sitemap
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * superclass for sitemap actions
 *
 * @category  Sitemap
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SitemapAction extends Action
{
    /**
     * handle the action
     *
     * @param array $args unused.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        header('Content-Type: text/xml; charset=UTF-8');
        $this->startXML();

        $this->elementStart('urlset', array('xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'));

        while (!is_null($next = $this->nextUrl())) {
            $this->showUrl(...$next);
        }

        $this->elementEnd('urlset');

        $this->endXML();
    }

    public function lastModified()
    {
        $y = $this->trimmed('year');

        $m = $this->trimmed('month');
        $d = $this->trimmed('day');

        $y += 0;
        $m += 0;
        $d += 0;

        $begdate = strtotime("$y-$m-$d 00:00:00");
        $enddate = $begdate + (24 * 60 * 60);

        if ($enddate < time()) {
            return $enddate;
        } else {
            return null;
        }
    }

    public function showUrl(
        $url,
        $lastMod    = null,
        $changeFreq = null,
        $priority   = null
    ) {
        $this->elementStart('url');
        $this->element('loc', null, $url);
        if (!is_null($lastMod)) {
            $this->element('lastmod', null, $lastMod);
        }
        if (!is_null($changeFreq)) {
            $this->element('changefreq', null, $changeFreq);
        }
        if (!is_null($priority)) {
            $this->element('priority', null, $priority);
        }
        $this->elementEnd('url');
    }

    public function nextUrl()
    {
        return null;
    }

    public function isReadOnly()
    {
        return true;
    }
}
