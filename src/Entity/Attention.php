<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for attentions
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Attention
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'        => 'attention',
            'description' => 'Notice attentions to profiles (that are not a mention and not result of a subscription)',
            'fields'      => [
                'notice_id'  => ['type' => 'int', 'not null' => true, 'description' => 'notice_id to give attention'],
                'profile_id' => ['type' => 'int', 'not null' => true, 'description' => 'profile_id for feed receiver'],
                'reason'     => ['type' => 'varchar', 'length' => 191, 'description' => 'Optional reason why this was brought to the attention of profile_id'],
                'created'    => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'   => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['notice_id', 'profile_id'],
            'foreign keys' => [
                'attention_notice_id_fkey'  => ['notice', ['notice_id' => 'id']],
                'attention_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
            ],
            'indexes' => [
                'attention_notice_id_idx'  => ['notice_id'],
                'attention_profile_id_idx' => ['profile_id'],
            ],
        ];
    }
}
