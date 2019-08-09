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
 * Allows administrators to define additional profile fields for the users of a GNU social installation.
 *
 * @category  Widget
 * @package   GNU social
 * @author    Max Shinn <trombonechamp@gmail.com>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

function gnusocial_profile_merge(&$profile)
{
    $responses = GNUsocialProfileExtensionResponse::findResponsesByProfile($profile->id);
    $profile->customfields = [];
    foreach ($responses as $response) {
        $title = $response->systemname;
        $profile->$title = $response->value;
        $profile->customfields[] = $title;
    }
}

function gnusocial_field_systemname_validate($systemname)
{
    // Ensure it doesn't exist already
    $fields = GNUsocialProfileExtensionField::allFields();
    foreach ($fields as $field) {
        if ($field->systemname == $systemname) {
            return false;
        }
    }
    // Ensure that it consist of only alphanumeric characters
    return ctype_alnum($systemname);
}
