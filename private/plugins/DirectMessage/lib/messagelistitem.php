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
 * A single item in a message list
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
abstract class MessageListItem extends Widget
{
    protected $message;

    /**
     * Constructor.
     *
     * @param HTMLOutputter $out     Output context
     * @param Notice        $message Message to show
     */
    function __construct(HTMLOutputter $out, $message)
    {
        parent::__construct($out);
        $this->message = $message;
    }

    /**
     * Show the widget.
     *
     * @return void
     */
    function show()
    {
        $profile = $this->getMessageProfile();
        if (is_null($profile)) {
            // null most probably because there are no attention profiles and
            // the UI below isn't ready for that, yet.
            return;
        }

        $this->out->elementStart('li', ['class' => 'h-entry notice',
                                        'id'    => 'message-' . $this->message->getID()]);

        $this->out->elementStart('a', ['href'  => $profile->getUrl(),
                                       'class' => 'p-author']);
        $avatarUrl = $profile->avatarUrl(AVATAR_STREAM_SIZE);
        $this->out->element('img', ['src'    => $avatarUrl,
                                    'class'  => 'avatar u-photo',
                                    'width'  => AVATAR_STREAM_SIZE,
                                    'height' => AVATAR_STREAM_SIZE,
                                    'alt'    => $profile->getBestName()]);
        $this->out->element('span', ['class' => 'nickname fn'], $profile->getNickname());
        $this->out->elementEnd('a');

        // FIXME: URL, image, video, audio
        $this->out->elementStart('div', ['class' => 'e-content']);
        $this->out->raw($this->message->getRendered());
        $this->out->elementEnd('div');

        $messageurl = common_local_url('showmessage',
                                       ['message' => $this->message->getID()]);

        $this->out->elementStart('div', 'entry-metadata');
        $this->out->elementStart('a', ['rel'   => 'bookmark',
                                       'class' => 'timestamp',
                                       'href'  => $messageurl]);
        $dt = common_date_iso8601($this->message->getCreated());
        $this->out->element('time', 
                            ['class'    => 'dt-published',
                             'datetime' => common_date_iso8601($this->message->getCreated()),
                             // TRANS: Timestamp title (tooltip text) for NoticeListItem
                             'title'    => common_exact_date($this->message->getCreated())],
                            common_date_string($this->message->getCreated()));
        $this->out->elementEnd('a');

        if ($this->message->source) {
            $this->out->elementStart('span', 'source');
            // FIXME: bad i18n. Device should be a parameter (from %s).
            // TRANS: Followed by notice source (usually the client used to send the notice).
            $this->out->text(_('from'));
            $this->showSource($this->message->source);
            $this->out->elementEnd('span');
        }
        $this->out->elementEnd('div');

        $this->out->elementEnd('li');
    }

    /**
     * Dummy method. Serves no other purpose than to make strings available used
     * in self::showSource() through xgettext.
     *
     * @return void
     */
    function messageListItemDummyMessages()
    {
        // A dummy array with messages. These will get extracted by xgettext and
        // are used in self::showSource().
        $dummy_messages = [
            // TRANS: A possible notice source (web interface).
            _m('SOURCE','web'),
            // TRANS: A possible notice source (XMPP).
            _m('SOURCE','xmpp'),
            // TRANS: A possible notice source (e-mail).
            _m('SOURCE','mail'),
            // TRANS: A possible notice source (OpenMicroBlogging).
            _m('SOURCE','omb'),
            // TRANS: A possible notice source (Application Programming Interface).
            _m('SOURCE','api')
        ];
    }

    /**
     * Show the source of the message.
     *
     * Returns either the name (and link) of the API client that posted the notice,
     * or one of other other channels.
     *
     * @param string $source the source of the message
     *
     * @return void
     */
    function showSource(string $source)
    {
        $source_name = _m('SOURCE',$source);
        switch ($source) {
        case 'web':
        case 'xmpp':
        case 'mail':
        case 'omb':
        case 'api':
            $this->out->element('span', 'device', $source_name);
            break;
        default:
            $ns = Notice_source::getKV($source);
            if ($ns) {
                $this->out->elementStart('span', 'device');
                $this->out->element('a', 
                                    ['href' => $ns->url,
                                     'rel'  => 'external'],
                                    $ns->name);
                $this->out->elementEnd('span');
            } else {
                $this->out->element('span', 'device', $source_name);
            }
            break;
        }
        return;
    }

    /**
     * Return the profile to show in the message item.
     * 
     * Overridden in sub-classes to show sender, receiver, or whatever.
     *
     * @return Profile profile to show avatar and name of
     */
    abstract function getMessageProfile(): ?Profile;
}
