<?php

namespace App\Entity;

class User
{
    public $id;

    public static function schemaDef()
    {
        return [
            'name'        => 'user',
            'description' => 'local users',
            'fields'      => [
                'id'                   => ['type' => 'int',      'not null' => true, 'description' => 'foreign key to profile table'],
                'nickname'             => ['type' => 'varchar',  'length' => 64,     'description' => 'nickname or username, duped in profile'],
                'password'             => ['type' => 'varchar',  'length' => 191,    'description' => 'salted password, can be null for OpenID users'],
                'email'                => ['type' => 'varchar',  'length' => 191,    'description' => 'email address for password recovery etc.'],
                'incomingemail'        => ['type' => 'varchar',  'length' => 191,    'description' => 'email address for post-by-email'],
                'emailnotifysub'       => ['type' => 'bool',     'default' => true,  'description' => 'Notify by email of subscriptions'],
                'emailnotifyfav'       => ['type' => 'int',      'size' => 'tiny',   'default' => null,                  'description' => 'Notify by email of favorites'],
                'emailnotifynudge'     => ['type' => 'bool',     'default' => true,  'description' => 'Notify by email of nudges'],
                'emailnotifymsg'       => ['type' => 'bool',     'default' => true,  'description' => 'Notify by email of direct messages'],
                'emailnotifyattn'      => ['type' => 'bool',     'default' => true,  'description' => 'Notify by email of @-replies'],
                'language'             => ['type' => 'varchar',  'length' => 50,     'description' => 'preferred language'],
                'timezone'             => ['type' => 'varchar',  'length' => 50,     'description' => 'timezone'],
                'emailpost'            => ['type' => 'bool',     'default' => true,  'description' => 'Post by email'],
                'sms'                  => ['type' => 'varchar',  'length' => 64,     'description' => 'sms phone number'],
                'carrier'              => ['type' => 'int',                          'description' => 'foreign key to sms_carrier'],
                'smsnotify'            => ['type' => 'bool',     'default' => false, 'description' => 'whether to send notices to SMS'],
                'smsreplies'           => ['type' => 'bool',     'default' => false, 'description' => 'whether to send notices to SMS on replies'],
                'smsemail'             => ['type' => 'varchar',  'length' => 191,    'description' => 'built from sms and carrier'],
                'uri'                  => ['type' => 'varchar',  'length' => 191,    'description' => 'universally unique identifier, usually a tag URI'],
                'autosubscribe'        => ['type' => 'bool',     'default' => false, 'description' => 'automatically subscribe to users who subscribe to us'],
                'subscribe_policy'     => ['type' => 'int',      'size' => 'tiny',   'default' => 0,                     'description' => '0 = anybody can subscribe; 1 = require approval'],
                'urlshorteningservice' => ['type' => 'varchar',  'length' => 50,     'default' => 'internal',            'description' => 'service to use for auto-shortening URLs'],
                'private_stream'       => ['type' => 'bool',     'default' => false,                                     'description' => 'whether to limit all notices to followers only'],
                'created'              => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'             => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP',   'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'user_nickname_key'      => ['nickname'],
                'user_email_key'         => ['email'],
                'user_incomingemail_key' => ['incomingemail'],
                'user_sms_key'           => ['sms'],
                'user_uri_key'           => ['uri'],
            ],
            'foreign keys' => [
                'user_id_fkey'      => ['profile', ['id' => 'id']],
                'user_carrier_fkey' => ['sms_carrier', ['carrier' => 'id']],
            ],
            'indexes' => [
                'user_created_idx'  => ['created'],
                'user_smsemail_idx' => ['smsemail'],
            ],
        ];
    }
}
