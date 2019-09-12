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
 *
 * Some notes...
 *
 * decimal <-> numeric
 *
 * MySQL 'timestamp' columns were formerly used for 'modified' files for their
 * auto-updating properties. This didn't play well with changes to cache usage
 * in 0.9.x, as we don't know the timestamp value at INSERT time and never
 * have a chance to load it up again before caching. For now I'm leaving them
 * in, but we may want to clean them up later.
 *
 * Current code should be setting 'created' and 'modified' fields explicitly;
 * this also avoids mismatches between server and client timezone settings.
 *
 *
 * fulltext indexes?
 * got one or two things wanting a custom charset setting on a field?
 *
 * foreign keys are kinda funky...
 *     those specified in inline syntax (as all in the original .sql) are NEVER ENFORCED on mysql
 *     those made with an explicit 'foreign key' WITHIN INNODB and IF there's a proper index, do get enforced
 *     double-check what we've been doing on postgres?
 */

defined('GNUSOCIAL') || die();

$classes = [
    'Schema_version',
    'Profile',
    'Avatar',
    'Sms_carrier',
    'User',
    'User_group',
    'Subscription',
    'Group_join_queue',
    'Subscription_queue',
    'Consumer',
    'Oauth_application',
    'Oauth_token_association',
    'Conversation',
    'Notice',
    'Notice_location',
    'Notice_source',
    'Notice_prefs',
    'Reply',
    'Token',
    'Nonce',
    'Oauth_application_user',
    'Confirm_address',
    'Remember_me',
    'Queue_item',
    'Notice_tag',
    'Foreign_service',
    'Foreign_user',
    'Foreign_link',
    'Foreign_subscription',
    'Invitation',
    'Profile_prefs',
    'Profile_list',
    'Profile_tag',
    'Profile_tag_subscription',
    'Profile_block',
    'Related_group',
    'Group_inbox',
    'Group_member',
    'File',
    'File_redirection',
    'File_thumbnail',
    'File_to_post',
    'Group_block',
    'Group_alias',
    'Session',
    'Config',
    'Profile_role',
    'Location_namespace',
    'Login_token',
    'User_location_prefs',
    'User_im_prefs',
    'Local_group',
    'User_urlshortener_prefs',
    'Old_school_prefs',
    'User_username',
    'Attention'
];

foreach ($classes as $cls) {
    $schema[strtolower($cls)] = call_user_func([$cls, 'schemaDef']);
}
