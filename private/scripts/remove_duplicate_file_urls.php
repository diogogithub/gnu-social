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
remove_duplicate_file_urls.php [options]
Remove duplicate URL entries in the file and file_redirection tables because they for some reason were not unique.

  -y --yes      do not wait for confirmation

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (!have_option('y', 'yes')) {
    print "About to remove duplicate URL entries in file and file_redirection tables. Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strtolower(trim($response)) != 'y') {
        print "Aborting.\n";
        exit(0);
    }
}

$file = new File();
$file->query('SELECT url FROM file GROUP BY url HAVING COUNT(*) > 1');
print "\nFound {$file->N} URLs with duplicate entries in file table";
while ($file->fetch()) {
    // We've got a URL that is duplicated in the file table
    $dupfile = new File();
    $dupfile->url = $file->url;
    if ($dupfile->find(true)) {
        print "\nDeleting duplicate entries in file table for URL: {$file->url} [";
        // Leave one of the URLs in the database by using ->find(true)
        // and only deleting starting with this fetch.
        while ($dupfile->fetch()) {
            print ".";
            $dupfile->delete();
        }
        print "]\n";
    } else {
        print "\nWarning! URL suddenly disappeared from database: {$file->url}\n";
    }
}

$file = new File_redirection();
$file->query('SELECT url FROM file_redirection GROUP BY url HAVING COUNT(*) > 1');
print "\nFound {$file->N} URLs with duplicate entries in file_redirection table";
while ($file->fetch()) {
    // We've got a URL that is duplicated in the file_redirection table
    $dupfile = new File_redirection();
    $dupfile->url = $file->url;
    if ($dupfile->find(true)) {
        print "\nDeleting duplicate entries in file table for URL: {$file->url} [";
        // Leave one of the URLs in the database by using ->find(true)
        // and only deleting starting with this fetch.
        while ($dupfile->fetch()) {
            print ".";
            $dupfile->delete();
        }
        print "]\n";
    } else {
        print "\nWarning! URL suddenly disappeared from database: {$file->url}\n";
    }
}
print "\nDONE.\n";
