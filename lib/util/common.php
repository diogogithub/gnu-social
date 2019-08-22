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

defined('GNUSOCIAL') || die();

/* Work internally in UTC */
date_default_timezone_set('UTC');

/* Work internally with UTF-8 */
mb_internal_encoding('UTF-8');

// All the fun stuff to actually initialize GNU social's framework code,
// without loading up a site configuration.
require_once INSTALLDIR . '/lib/framework.php';

try {
    GNUsocial::init(@$server, @$path, @$conffile);
} catch (NoConfigException $e) {
    // XXX: Throw a conniption if database not installed
    // XXX: Find a way to use htmlwriter for this instead of handcoded markup
    // TRANS: Error message displayed when no configuration file was found for a StatusNet installation.
    echo '<p>'. _('No configuration file found.') .'</p>';
    // TRANS: Error message displayed when no configuration file was found for a StatusNet installation.
    // TRANS: Is followed by a list of directories (separated by HTML breaks).
    echo '<p>'. _('I looked for configuration files in the following places:') .'<br /> ';
    echo implode($e->configFiles, '<br />');
    // TRANS: Error message displayed when no configuration file was found for a StatusNet installation.
    echo '<p>'. _('You may wish to run the installer to fix this.') .'</p>';
    // @todo FIXME Link should be in a para?
    // TRANS: Error message displayed when no configuration file was found for a StatusNet installation.
    // TRANS: The text is link text that leads to the installer page.
    echo '<a href="install.php">'. _('Go to the installer.') .'</a>';
    exit;
}
