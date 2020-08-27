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
 * StoreRemoteMediaPlugin
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'l::a::i';
$longoptions = ['limit=', 'all', 'image'];

$helptext = <<<END_OF_HELP
remove_remote_media.php [options]
Removes remote media. In most cases, (if not all), an URL will be kept for the original attachment.
In case the attachment is an image its thumbs will be removed as well.

    -l --limit [date]  This is a timestamp, format is: yyyy-mm-dd (optional time hh:mm:ss may be provided)
    -a --all           By default only remote attachments will be deleted, by using this flag you will remove oembed previews and alike
    -i --image         Remove image only attachments (will ignore oembed previews and alike)

END_OF_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

$quiet = have_option('q', 'quiet');
$include_previews = have_option('a', 'all');
$image_only = have_option('i', 'image');

if (!have_option('l', 'limit')) {
    echo "You must provide a limit!\n\n";
    show_help();
    exit(1);
}
$max_date = get_option_value('l', 'limit');
if (empty($max_date)) {
    echo "Invalid empty limit!";
    exit(1);
}

$fn = new DB_DataObject();
$fn->query(sprintf(
    <<<'END'
    SELECT file_to_post.file_id
      FROM file_to_post
      INNER JOIN file ON file_to_post.file_id = file.id
      INNER JOIN notice ON file_to_post.post_id = notice.id
      WHERE notice.is_local = 0
      %1$s%2$sAND notice.modified <= '%3$s'
      GROUP BY file_to_post.file_id
      ORDER BY MAX(notice.modified)
    END,
    $image_only ? 'AND file.width IS NOT NULL AND file.height IS NOT NULL ' : '',
    $include_previews ? '' : 'AND file.filehash IS NOT NULL ',
    $fn->escape($max_date)
));

while ($fn->fetch()) {
    $file = File::getByID($fn->file_id);
    $file_info_id = $file->getID();
    // Delete current file
    $file->delete();
    if (!$quiet) {
        echo "Deleted file with id: {$file_info_id}\n";
    }
}
