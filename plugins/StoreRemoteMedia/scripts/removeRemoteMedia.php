#!/usr/bin/env php
<?php
/**
 * GNU social - a federating social network
 *
 * GNU Social - StoreRemoteMediaPlugin
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @copyright 2018 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      https://www.gnu.org/software/social/
 */

// Script author: Diogo Cordeiro <diogo@fc.up.pt>

define('INSTALLDIR', realpath(__DIR__ . '/../../..'));

$longoptions = ['limit='];

$helptext = <<<END_OF_HELP
remove_remote_media.php [options]
Keeps an URL for the original attachments and removes both the original file and the related thumbs.

    --limit date <- this is a timestamp, format is: yyyy-mm-dd (optional time hh:mm:ss may be provided)

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$quiet = have_option('q', 'quiet');

if (!have_option('limit')) {
    show_help();
    exit(1);
}

$max_date = get_option_value('limit');

$query = "
    SELECT 
        file_to_post.file_id
    FROM
        file_to_post
            INNER JOIN
        file ON file.id = file_to_post.file_id
            INNER JOIN
        notice ON notice.id = file_to_post.post_id
    WHERE
        file.filehash IS NOT NULL
            AND file.filename IS NOT NULL
            AND file.width IS NOT NULL
            AND file.height IS NOT NULL
            AND notice.source <> 'web'
            AND notice.modified <= '{$max_date}'
    ORDER BY notice.modified ASC
";

$fn = new DB_DataObject();
$fn->query($query);
while ($fn->fetch()) {
    $file = File::getByID($fn->file_id);
    $file_info_id = $file->getID();
    // Delete current file
    $file->delete();
    if (!$quiet) {
        echo "Deleted file with id: {$file_info_id}\n";
    }
}
