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
 * Show list of user pages
 *
 * @category  Sitemap
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * sitemap for users
 *
 * @category  Sitemap
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class UsersitemapAction extends SitemapAction
{
    public $users = null;
    public $j     = 0;

    public function prepare(array $args = [])
    {
        parent::prepare($args);

        $y = $this->trimmed('year');

        $m = $this->trimmed('month');
        $d = $this->trimmed('day');

        $i = $this->trimmed('index');

        $y += 0;
        $m += 0;
        $d += 0;
        $i += 0;

        $this->users = $this->getUsers($y, $m, $d, $i);
        $this->j     = 0;
        return true;
    }

    public function nextUrl()
    {
        if ($this->j < count($this->users)) {
            $nickname = $this->users[$this->j];
            $this->j++;
            return array(common_profile_url($nickname), null, null, '1.0');
        } else {
            return null;
        }
    }

    public function getUsers($y, $m, $d, $i)
    {
        $u = User::cacheGet("sitemap:user:$y:$m:$d:$i");

        if ($u === false) {
            $user = new User();

            $begindt = sprintf('%04d-%02d-%02d 00:00:00', $y, $m, $d);

            // XXX: estimates 1d == 24h, which screws up days
            // with leap seconds (1d == 24h + 1s). Thankfully they're
            // few and far between.

            $theend = strtotime($begindt) + (24 * 60 * 60);
            $enddt  = common_sql_date($theend);

            $user->selectAdd();
            $user->selectAdd('nickname');
            $user->whereAdd("created >= '$begindt'");
            $user->whereAdd("created <  '$enddt'");

            $user->orderBy('created, id');

            $offset = ($i-1) * SitemapPlugin::USERS_PER_MAP;
            $limit  = SitemapPlugin::USERS_PER_MAP;

            $user->limit($offset, $limit);

            $user->find();

            while ($user->fetch()) {
                $u[] = $user->nickname;
            }

            $c = Cache::instance();

            if (!empty($c)) {
                $c->set(
                    Cache::key("sitemap:user:{$y}:{$m}:{$d}:{$i}"),
                    $u,
                    Cache::COMPRESSED,
                    ((time() > $theend) ? (time() + 90 * 24 * 60 * 60) : (time() + 5 * 60))
                );
            }
        }

        return $u;
    }
}
