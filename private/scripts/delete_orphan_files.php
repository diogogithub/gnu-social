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
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'y';
$longoptions = array('yes');

$helptext = <<<END_OF_HELP
delete_orphan_files.php [options]
Deletes all files and their File entries where there is no link to a
Notice entry. Good for cleaning up after user deletion or so where the
attached files weren't removed as well.

  -y --yes      do not wait for confirmation

Will print '.' for each deleted File entry and 'x' if it also had a locally stored file.

WARNING WARNING WARNING, this will also delete Qvitter files such as background etc. since
they are not linked to notices (yet anyway).

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

print "Finding File entries that are not related to a Notice (or the notice has been deleted)...";
$file = new File();
$sql = <<<'END'
    SELECT file.*
      FROM file_to_post
      INNER JOIN notice ON file_to_post.post_id = notice.id
      RIGHT JOIN file ON file_to_post.file_id = file.id
      WHERE file_to_post.file_id IS NULL;
    END;

if ($file->query($sql) !== false) {
    print " {$file->N} found.\n";
    if ($file->N == 0) {
        exit(0);
    }
} else {
    print "FAILED";
    exit(1);
}

if (!have_option('y', 'yes')) {
    print "About to delete the entries along with locally stored files. Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strtolower(trim($response)) != 'y') {
        print "Aborting.\n";
        exit(0);
    }
}

print "\nDeleting: ";
while ($file->fetch()) {
    try {
        $file->getPath();
        $file->delete();
        print 'x';
    } catch (Exception $e) {
        // either FileNotFound exception or ClientException
        $file->delete();
        print '.';
    }
}
print "\nDONE.\n";
