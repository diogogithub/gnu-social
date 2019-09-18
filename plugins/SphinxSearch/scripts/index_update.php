#!/usr/bin/env php
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
 * @package   GNUsocial
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$longoptions = array('base=', 'network');

$helptext = <<<END_OF_TRIM_HELP
Runs Sphinx search indexer.
    --rotate             Have Sphinx run index update in background and
                         rotate updated indexes into place as they finish.
    --base               Base dir to Sphinx install
                         (default /usr/local)
    --network            Use status_network global config table for site list
                         (non-functional at present)


END_OF_TRIM_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';
require dirname(__FILE__) . '/sphinx-utils.php';

sphinx_iterate_sites('sphinx_index_update');

function sphinx_index_update($sn)
{
    $base = sphinx_base();

    $baseIndexes = array('notice', 'profile');
    $params = array();

    if (have_option('rotate')) {
        $params[] = '--rotate';
    }
    foreach ($baseIndexes as $index) {
        $params[] = "{$sn->dbname}_{$index}";
    }

    $params = implode(' ', $params);
    $cmd = "$base/bin/indexer --config $base/etc/sphinx.conf $params";

    print "$cmd\n";
    system($cmd);
}
