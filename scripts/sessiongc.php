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

$helptext = <<<END_OF_GC_HELP
sessiongc.php

Delete old sessions from the server

END_OF_GC_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$maxlifetime = ini_get('session.gc_maxlifetime');

print "Deleting sessions older than $maxlifetime seconds.\n";

InternalSessionHandler::gc($maxlifetime);
