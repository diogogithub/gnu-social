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
 * Direct messaging implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Adrian Lang <mail@adrianlang.de>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Robin Millette <robin@millette.info>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009, 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Show a list of direct messages from or to the authenticating user
 *
 * @category Plugin
 * @package  GNUsocial
 * @author   Adrian Lang <mail@adrianlang.de>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <robin@millette.info>
 * @author   Zach Copley <zach@status.net>
 * @license  https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ApiDirectMessageAction extends ApiAuthAction
{
    public $messages     = null;
    public $title        = null;
    public $subtitle     = null;
    public $link         = null;
    public $selfuri_base = null;
    public $id           = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        if (!$this->scoped instanceof Profile) {
            // TRANS: Client error given when a user was not found (404).
            $this->clientError(_('No such user.'), 404);
        }

        $server = common_root_url();
        $taguribase = TagURI::base();

        if ($this->arg('sent')) {

            // Action was called by /api/direct_messages/sent.format

            $this->title = sprintf(
                // TRANS: Title. %s is a user nickname.
                _("Direct messages from %s"),
                $this->scoped->getNickname()
            );
            $this->subtitle = sprintf(
                // TRANS: Subtitle. %s is a user nickname.
                _("All the direct messages sent from %s"),
                $this->scoped->getNickname()
            );
            $this->link = $server . $this->scoped->getNickname() . '/outbox';
            $this->selfuri_base = common_root_url() . 'api/direct_messages/sent';
            $this->id = "tag:$taguribase:SentDirectMessages:" . $this->scoped->getID();
        } else {
            $this->title = sprintf(
                // TRANS: Title. %s is a user nickname.
                _("Direct messages to %s"),
                $this->scoped->getNickname()
            );
            $this->subtitle = sprintf(
                // TRANS: Subtitle. %s is a user nickname.
                _("All the direct messages sent to %s"),
                $this->scoped->getNickname()
            );
            $this->link = $server . $this->scoped->getNickname() . '/inbox';
            $this->selfuri_base = common_root_url() . 'api/direct_messages';
            $this->id = "tag:$taguribase:DirectMessages:" . $this->scoped->getID();
        }

        $this->messages = $this->getMessages();

        return true;
    }

    protected function handle()
    {
        parent::handle();
        $this->showMessages();
    }

    /**
     * Show the messages
     *
     * @return void
     */
    public function showMessages()
    {
        switch ($this->format) {
            case 'xml':
                $this->showXmlDirectMessages();
                break;
            case 'rss':
                $this->showRssDirectMessages();
                break;
            case 'atom':
                $this->showAtomDirectMessages();
                break;
            case 'json':
                $this->showJsonDirectMessages();
                break;
            default:
                // TRANS: Client error displayed when coming across a non-supported API method.
                $this->clientError(_('API method not found.'), $code = 404);
                break;
        }
    }

    /**
     * Get messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        $message = $this->arg('sent')
            ? $this->getOutboxMessages()
            : $this->getInboxMessages();

        $ret = [];

        if (!is_null($message)) {
            while ($message->fetch()) {
                $ret[] = clone $message;
            }
        }

        return $ret;
    }

    /**
     * Return data object of the messages received by some user.
     *
     * @return Notice data object
     */
    private function getInboxMessages()
    {
        // fetch all notice IDs related to the user
        $attention = new Attention();
        $attention->selectAdd('notice_id');
        $attention->whereAdd('profile_id = ' . $this->scoped->getID());

        $ids = $attention->find() ? $attention->fetchAll('notice_id') : [];

        // get the messages
        $message = new Notice();
        $message->whereAdd('scope = ' . NOTICE::MESSAGE_SCOPE);

        if (!empty($this->max_id)) {
            $message->whereAdd('id <= ' . $this->max_id);
        }

        if (!empty($this->since_id)) {
            $message->whereAdd('id > ' . $this->since_id);
        }

        $message->whereAddIn('id', $ids, 'int');
        $message->orderBy('created DESC, id DESC');
        $message->limit((($this->page - 1) * $this->count), $this->count);

        return $message->find() ? $message : null;
    }

    /**
     * Return data object of the messages sent by some user.
     *
     * @return Notice data object
     */
    private function getOutboxMessages()
    {
        $message = new Notice();

        $message->profile_id = $this->scoped->getID();
        $message->whereAdd('scope = ' . NOTICE::MESSAGE_SCOPE);

        if (!empty($this->max_id)) {
            $message->whereAdd('id <= ' . $this->max_id);
        }

        if (!empty($this->since_id)) {
            $message->whereAdd('id > ' . $this->since_id);
        }

        $message->orderBy('created DESC, id DESC');
        $message->limit((($this->page - 1) * $this->count), $this->count);

        return $message->find() ? $message : null;
    }

    /**
     * Is this action read only?
     *
     * @param array $args other arguments
     *
     * @return boolean true
     */
    public function isReadOnly($args)
    {
        return true;
    }

    /**
     * When was this notice last modified?
     *
     * @return string datestamp of the latest notice in the stream
     */
    public function lastModified()
    {
        if (!empty($this->messages)) {
            return strtotime($this->messages[0]->created);
        }

        return null;
    }

    // BEGIN import from lib/apiaction.php

    public function showSingleXmlDirectMessage($message)
    {
        $this->initDocument('xml');
        $dmsg = $this->directMessageArray($message);
        $this->showXmlDirectMessage($dmsg, true);
        $this->endDocument('xml');
    }

    public function showSingleJsonDirectMessage($message)
    {
        $this->initDocument('json');
        $dmsg = $this->directMessageArray($message);
        $this->showJsonObjects($dmsg);
        $this->endDocument('json');
    }

    public function showXmlDirectMessage($dm, $namespaces = false)
    {
        $attrs = [];
        if ($namespaces) {
            $attrs['xmlns:statusnet'] = 'http://status.net/schema/api/1/';
        }
        $this->elementStart('direct_message', $attrs);
        foreach ($dm as $element => $value) {
            if ($element === 'text') {
                $this->element($element, null, common_xml_safe_str($value));
            } elseif (
                $element === 'sender'
                || preg_match('/recipient$|recipient_[0-9]+/', $element) == 1
            ) {
                $this->showTwitterXmlUser($value, $element);
            } else {
                $this->element($element, null, $value);
            }
        }
        $this->elementEnd('direct_message');
    }

    public function directMessageArray($message)
    {
        $dmsg = [];

        $from = $message->getProfile();
        $to   = $message->getAttentionProfiles();

        $dmsg['id'] = intval($message->id);
        $dmsg['sender_id'] = (int) $from->id;
        $dmsg['text'] = trim($message->content);

        $dmsg['total_recipients'] = (int) count($to);
        $dmsg['recipient_id'] = (int) $to[0]->id;

        for ($i = 1; $i < count($to); ++$i) {
            $dmsg['recipient_id_' . $i] = (int) $to[$i]->id;
        }

        $dmsg['created_at'] = $this->dateTwitter($message->created);
        $dmsg['sender_screen_name'] = $from->nickname;
        $dmsg['recipient_screen_name'] = $to[0]->nickname;

        for ($i = 1; $i < count($to); ++$i) {
            $dmsg['recipient_screen_name_' . $i] = $to[$i]->nickname;
        }

        $dmsg['sender'] = $this->twitterUserArray($from);
        $dmsg['recipient'] = $this->twitterUserArray($to[0]);

        for ($i = 1; $i < count($to); ++$i) {
            $dmsg['recipient_' . $i] = $this->twitterUserArray($to[$i]);
        }

        return $dmsg;
    }

    public function rssDirectMessageArray($message)
    {
        $entry = [];

        $from = $message->getProfile();
        $to   = $message->getAttentionProfiles();

        $entry['title'] = 'Message from ' . $from->nickname . ' to ';
        $entry['title'] .= (count($to) == 1) ? $to[0]->nickname : 'many';

        $entry['content'] = common_xml_safe_str($message->rendered);
        $entry['link'] = common_local_url(
            'showmessage',
            ['message' => $message->id]
        );
        $entry['published'] = common_date_iso8601($message->created);

        $taguribase = TagURI::base();

        $entry['id'] = "tag:$taguribase:$entry[link]";
        $entry['updated'] = $entry['published'];

        $entry['author-name'] = $from->getBestName();
        $entry['author-uri'] = $from->homepage;

        $entry['avatar'] = $from->avatarUrl(AVATAR_STREAM_SIZE);
        try {
            $avatar = $from->getAvatar(AVATAR_STREAM_SIZE);
            $entry['avatar-type'] = $avatar->mediatype;
        } catch (Exception $e) {
            $entry['avatar-type'] = 'image/png';
        }

        // RSS item specific

        $entry['description'] = $entry['content'];
        $entry['pubDate'] = common_date_rfc2822($message->created);
        $entry['guid'] = $entry['link'];

        return $entry;
    }

    // END import from lib/apiaction.php

    /**
     * Shows a list of direct messages as Twitter-style XML array
     *
     * @return void
     */
    public function showXmlDirectMessages()
    {
        $this->initDocument('xml');
        $this->elementStart('direct-messages', [
            'type'            => 'array',
            'xmlns:statusnet' => 'http://status.net/schema/api/1/',
        ]);

        foreach ($this->messages as $m) {
            $dm_array = $this->directMessageArray($m);
            $this->showXmlDirectMessage($dm_array);
        }

        $this->elementEnd('direct-messages');
        $this->endDocument('xml');
    }

    /**
     * Shows a list of direct messages as a JSON encoded array
     *
     * @return void
     */
    public function showJsonDirectMessages()
    {
        $this->initDocument('json');

        $dmsgs = [];

        foreach ($this->messages as $m) {
            $dm_array = $this->directMessageArray($m);
            array_push($dmsgs, $dm_array);
        }

        $this->showJsonObjects($dmsgs);
        $this->endDocument('json');
    }

    /**
     * Shows a list of direct messages as RSS items
     *
     * @return void
     */
    public function showRssDirectMessages()
    {
        $this->initDocument('rss');

        $this->element('title', null, $this->title);

        $this->element('link', null, $this->link);
        $this->element('description', null, $this->subtitle);
        $this->element('language', null, 'en-us');

        $this->element(
            'atom:link',
            [
                'type' => 'application/rss+xml',
                'href' => $this->selfuri_base . '.rss',
                'rel'  => self,
            ],
            null
        );
        $this->element('ttl', null, '40');

        foreach ($this->messages as $m) {
            $entry = $this->rssDirectMessageArray($m);
            $this->showTwitterRssItem($entry);
        }

        $this->endTwitterRss();
    }

    /**
     * Shows a list of direct messages as Atom entries
     *
     * @return void
     */
    public function showAtomDirectMessages()
    {
        $this->initDocument('atom');

        $this->element('title', null, $this->title);
        $this->element('id', null, $this->id);

        $selfuri = common_root_url() . 'api/direct_messages.atom';

        $this->element(
            'link',
            [
                'href' => $this->link,
                'rel'  => 'alternate',
                'type' => 'text/html',
            ],
            null
        );
        $this->element(
            'link',
            [
                'href' => $this->selfuri_base . '.atom',
                'rel'  => 'self',
                'type' => 'application/atom+xml',
            ],
            null
        );
        $this->element('updated', null, common_date_iso8601('now'));
        $this->element('subtitle', null, $this->subtitle);

        foreach ($this->messages as $m) {
            $entry = $this->rssDirectMessageArray($m);
            $this->showTwitterAtomEntry($entry);
        }

        $this->endDocument('atom');
    }

    /**
     * An entity tag for this notice
     *
     * Returns an Etag based on the action name, language, and
     * timestamps of the notice
     *
     * @return string etag
     */
    public function etag()
    {
        if (!empty($this->messages)) {
            $last = count($this->messages) - 1;

            return '"' . implode(
                ':',
                [
                    $this->arg('action'),
                    common_user_cache_hash($this->auth_user),
                    common_language(),
                    strtotime($this->messages[0]->created),
                    strtotime($this->messages[$last]->created),
                ]
            )
            . '"';
        }

        return null;
    }
}
