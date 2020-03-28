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
 * Table Definition for consumer
 */

defined('GNUSOCIAL') || die();

class Consumer extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'consumer';                        // table name
    public $consumer_key;                    // varchar(191)  primary_key not_null   not 255 because utf8mb4 takes more space
    public $consumer_secret;                 // varchar(191)   not_null   not 255 because utf8mb4 takes more space
    public $seed;                            // char(32)   not_null
    public $created;                         // datetime()
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'description' => 'OAuth consumer record',
            'fields' => array(
                'consumer_key' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'unique identifier, root URL'),
                'consumer_secret' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'secret value'),
                'seed' => array('type' => 'char', 'length' => 32, 'not null' => true, 'description' => 'seed for new tokens by this consumer'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('consumer_key'),
        );
    }

    public static function generateNew()
    {
        $cons = new Consumer();
        $rand = common_random_hexstr(16);

        $cons->seed            = $rand;
        $cons->consumer_key    = md5(time() + $rand);
        $cons->consumer_secret = md5(md5(time() + time() + $rand));
        $cons->created         = common_sql_now();

        return $cons;
    }

    /**
     * Delete a Consumer and related tokens and nonces
     *
     * XXX: Should this happen in an OAuthDataStore instead?
     *
     */
    public function delete($useWhere = false)
    {
        // XXX: Is there any reason NOT to do this kind of cleanup?

        $this->deleteTokens();
        $this->deleteNonces();

        return parent::delete($useWhere);
    }

    private function deleteTokens()
    {
        $token = new Token();
        $token->consumer_key = $this->consumer_key;
        $token->delete();
    }

    private function deleteNonces()
    {
        $nonce = new Nonce();
        $nonce->consumer_key = $this->consumer_key;
        $nonce->delete();
    }
}
