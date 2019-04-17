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

$shortoptions = 'l::a::i';
$longoptions = ['limit=','all','image'];

$helptext = <<<END_OF_HELP
remove_remote_media.php [options]
Removes remote media. In most cases, (if not all), an URL will be kept for the original attachment.
In case the attachment is an image its thumbs will be removed as well.

    -l --limit [date]  This is a timestamp, format is: yyyy-mm-dd (optional time hh:mm:ss may be provided)
    -a --all           By default only remote attachments will be deleted, by using this flag you will remove oembed previews and alike
    -i --image         Remove image only attachments (will ignore oembed previews and alike)

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$quiet = have_option('q', 'quiet');
$include_previews = have_option('a', 'all');
$image_only = have_option('i', 'image');

if (!have_option('l', 'limit')) {
    echo "You must provide a limit!";
    show_help();
    exit(1);
}
$max_date = get_option_value('l', 'limit');
if (empty($max_date)) {
    echo "Invalid empty limit!";
    exit(1);
}

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
        notice.is_local = 0 ";

$query .= $image_only ? " AND file.width IS NOT NULL AND file.height IS NOT NULL " : "";

$query .= $include_previews ? "" : " AND file.filehash IS NOT NULL ";
$query .= " AND notice.modified <= '{$max_date}' ORDER BY notice.modified ASC";

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
