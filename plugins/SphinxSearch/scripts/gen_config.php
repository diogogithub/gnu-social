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
Generates sphinx.conf file based on StatusNet configuration.
    --base               Base dir to Sphinx install
                         (default /usr/local)
    --network            Use status_network global config table
                         (non-functional at present)


END_OF_TRIM_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';
require dirname(__FILE__) . '/sphinx-utils.php';


$timestamp = date('r');
print <<<END
#
# Sphinx configuration for StatusNet
# Generated {$timestamp}
#

END;

sphinx_iterate_sites('sphinx_site_template');

print <<<END

indexer
{
    mem_limit               = 300M
}

searchd
{
    port                    = 3312
    log                     = {$base}/log/searchd.log
    query_log               = {$base}/log/query.log
    read_timeout            = 5
    max_children            = 30
    pid_file                = {$base}/log/searchd.pid
    max_matches             = 1000
    seamless_rotate         = 1
    preopen_indexes         = 0
    unlink_old              = 1
}

END;

/**
 * Build config entries for a single site
 * @fixme we only seem to have master DB currently available...
 */
function sphinx_site_template($sn)
{
    if (common_config('db', 'type') !== 'mysql') {
        $created_ts = sprintf(
            '((EXTRACT(DAY %1$s) * 24 + EXTRACT(HOUR %1$s)) * 60 + ' .
            'EXTRACT(MINUTE %1$s)) * 60 + FLOOR(EXTRACT(SECOND %1$s)) AS "created_ts"',
            'FROM ("created" - TIMESTAMP \'1970-01-01 00:00:00\')'
        );
    } else {
        $created_ts = "timestampdiff(SECOND, '1970-01-01', `created`) AS `created_ts`";
    }

    return
        sphinx_template(
            $sn,
            'profile',
            'SELECT id, ' . $created_ts . ', nickname, fullname, location, bio, homepage FROM profile',
            'SELECT * FROM profile WHERE id = $id'
        ) .
        sphinx_template(
            $sn,
            'notice',
            'SELECT id, ' . $created_ts . ', content FROM notice',
            'SELECT * FROM notice WHERE notice.id = $id AND notice.is_local <> -2'
        );
}

function sphinx_template($sn, $table, $query, $query_info)
{
    $base = sphinx_base();
    $dbtype = common_config('db', 'type');

    print <<<END

#
# {$sn->sitename}
#
source {$sn->dbname}_src_{$table}
{
    type                    = {$dbtype}
    sql_host                = {$sn->dbhost}
    sql_user                = {$sn->dbuser}
    sql_pass                = {$sn->dbpass}
    sql_db                  = {$sn->dbname}
    sql_query_pre           = SET NAMES utf8;
    sql_query               = {$query}
    sql_query_info          = {$query_info}
    sql_attr_timestamp      = created_ts
}

index {$sn->dbname}_{$table}
{
    source                  = {$sn->dbname}_src_{$table}
    path                    = {$base}/data/{$sn->dbname}_{$table}
    docinfo                 = extern
    charset_type            = utf-8
    min_word_len            = 3
}


END;
}
