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

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'y';
$longoptions = ['yes'];

$helptext = <<<END_OF_HELP
clean_profiles.php [options]
Deletes all profile table entries where the profile does not occur in the
notice table, is not a group and is not a local user.

WARNING: This has not been tested thoroughly. Maybe we've missed a table to compare somewhere.

  -y --yes      do not wait for confirmation

END_OF_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

if (!have_option('y', 'yes')) {
    print "About to delete profiles that we think are useless to save. Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strtolower(trim($response)) != 'y') {
        print "Aborting.\n";
        exit(0);
    }
}

echo 'Deleting';
$user_table = common_database_tablename('user');
$profile = new Profile();
$profile->query(
    <<<END
    SELECT profile.*
      FROM profile
      LEFT JOIN (
        SELECT profile_id AS id FROM notice
        UNION ALL
        SELECT id FROM {$user_table}
        UNION ALL
        SELECT profile_id AS id FROM user_group
        UNION ALL
        SELECT subscriber FROM subscription
        UNION ALL
        SELECT subscribed FROM subscription
      ) AS t1 USING (id)
      WHERE t1.id IS NULL
    END
);
while ($profile->fetch()) {
    echo ' '.$profile->getID().':'.$profile->getNickname();
    $profile->delete();
}
print "\nDONE.\n";
