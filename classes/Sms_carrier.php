<?php
/**
 * Table Definition for sms_carrier
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Sms_carrier extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'sms_carrier';                     // table name
    public $id;                              // int(4)  primary_key not_null
    public $name;                            // varchar(64)  unique_key
    public $email_pattern;                   // varchar(191)   not_null   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()   not_null default_0000-00-00%2000%3A00%3A00
    public $modified;                        // datetime()   not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function toEmailAddress($sms)
    {
        return sprintf($this->email_pattern, $sms);
    }

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'int', 'not null' => true, 'description' => 'primary key for SMS carrier'),
                'name' => array('type' => 'varchar', 'length' => 64, 'description' => 'name of the carrier'),
                'email_pattern' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'sprintf pattern for making an email address from a phone number'),
                'created' => array('type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'),
                'modified' => array('type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'sms_carrier_name_key' => array('name'),
            ),
        );
    }
}
