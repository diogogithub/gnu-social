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
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

define('INSTALLDIR', realpath(__DIR__ . '/../../..'));

$longoptions = array('dry-run');

$helptext = <<<END_OF_USERROLE_HELP
fixup_files.php [options]
Patches up file entries with corrupted types and titles (the "h bug").

     --dry-run  look but don't touch

END_OF_USERROLE_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$dry = have_option('dry-run');

$f = new File();
$f->title = 'h';
$f->mimetype = 'h';
$f->size = 0;
$f->protected = 0;
$f->find();
echo "Found $f->N bad items:\n";

while ($f->fetch()) {
    echo "$f->id $f->url";

    $data = File_redirection::lookupWhere($f->url);
    if ($dry) {
        if (is_array($data)) {
            echo " (unchanged)\n";
        } else {
            echo " (unchanged, but embedding lookup failed)\n";
        }
    } else {
        // NULL out the mime/title/size/protected fields
        $sql = sprintf(
            "UPDATE file " .
                       "SET mimetype=null,title=null,size=null,protected=null " .
                       "WHERE id=%d",
            $f->id
        );
        $f->query($sql);
        $f->decache();

        if (is_array($data)) {
            Event::handle('EndFileSaveNew', array($f, $data, $f->url));
            echo " (ok)\n";
        } else {
            echo " (ok, but embedding lookup failed)\n";
        }
    }
}

echo "done.\n";
