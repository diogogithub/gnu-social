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
 * GNUsocial implementation of Direct Messages
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Table definition for message.
 * 
 * Since the new updates this class only has the necessary
 * logic to upgrade te plugin.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Message extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'message';             // table name
    public $id;                              // int(4)  primary_key not_null
    public $uri;                             // varchar(191)  unique_key   not 255 because utf8mb4 takes more space
    public $from_profile;                    // int(4)   not_null
    public $to_profile;                      // int(4)   not_null
    public $content;                         // text()
    public $rendered;                        // text()
    public $url;                             // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP
    public $source;                          // varchar(32)

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'serial', 'not null' => true, 'description' => 'unique identifier'),
                'uri' => array('type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier'),
                'from_profile' => array('type' => 'int', 'not null' => true, 'description' => 'who the message is from'),
                'to_profile' => array('type' => 'int', 'not null' => true, 'description' => 'who the message is to'),
                'content' => array('type' => 'text', 'description' => 'message content'),
                'rendered' => array('type' => 'text', 'description' => 'HTML version of the content'),
                'url' => array('type' => 'varchar', 'length' => 191, 'description' => 'URL of any attachment (image, video, bookmark, whatever)'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
                'source' => array('type' => 'varchar', 'length' => 32, 'description' => 'source of comment, like "web", "im", or "clientname"'),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'message_uri_key' => array('uri'),
            ),
            'foreign keys' => array(
                'message_from_profile_fkey' => array('profile', array('from_profile' => 'id')),
                'message_to_profile_fkey' => array('profile', array('to_profile' => 'id')),
            ),
            'indexes' => array(
                // @fixme these are really terrible indexes, since you can only sort on one of them at a time.
                // looks like we really need a (to_profile, created) for inbox and a (from_profile, created) for outbox
                'message_from_idx' => array('from_profile'),
                'message_to_idx' => array('to_profile'),
                'message_created_idx' => array('created'),
            ),
        );
    }

    function getFrom()
    {
        return Profile::getKV('id', $this->from_profile);
    }

    function getTo()
    {
        return Profile::getKV('id', $this->to_profile);
    }

    function getSource()
    {
        if (empty($this->source)) {
            return false;
        }

        $ns = new Notice_source();
        switch ($this->source) {
        case 'web':
        case 'xmpp':
        case 'mail':
        case 'omb':
        case 'system':
        case 'api':
            $ns->code = $this->source;
            break;
        default:
            $ns = Notice_source::getKV($this->source);
            if (!$ns instanceof Notice_source) {
                $ns = new Notice_source();
                $ns->code = $this->source;
                $app = Oauth_application::getKV('name', $this->source);
                if ($app) {
                    $ns->name = $app->name;
                    $ns->url  = $app->source_url;
                }
            }
            break;
        }
        return $ns;
    }

    function asActivity()
    {
        $act = new Activity();

        if (Event::handle('StartMessageAsActivity', array($this, &$act))) {
            $act->verb = ActivityVerb::POST;
            $act->time = strtotime($this->created);

            $actor_profile = $this->getFrom();
            if (is_null($actor_profile)) {
                throw new Exception(sprintf("Sender profile not found: %d", $this->from_profile));
            }
            $act->actor = $actor_profile->asActivityObject();
            
            $act->context = new ActivityContext();
            $options = ['source' => $this->source,
                        'uri'    => TagURI::mint(sprintf('activity:message:%d', $this->id)),
                        'url'    => $this->uri,
                        'scope'  => Notice::MESSAGE_SCOPE];

            $to_profile = $this->getTo();
            if (is_null($to_profile)) {
                throw new Exception(sprintf("Receiver profile not found: %d", $this->to_profile));
            }
            $act->context->attention[$to_profile->getUri()] = ActivityObject::PERSON;

            $act->objects[] = ActivityObject::fromMessage($this);

            $source = $this->getSource();
            if ($source instanceof Notice_source) {
                $act->generator = ActivityObject::fromNoticeSource($source);
            }

            Event::handle('EndMessageAsActivity', array($this, &$act));
        }

        return $act;
    }
}
