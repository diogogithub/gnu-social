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
 * An item in a notice list
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * widget for displaying a single notice
 *
 * This widget has the core smarts for showing a single notice: what to display,
 * where, and under which circumstances. Its key method is show(); this is a recipe
 * that calls all the other show*() methods to build up a single notice. The
 * ProfileNoticeListItem subclass, for example, overrides showAuthor() to skip
 * author info (since that's implicit by the data in the page).
 *
 * @category  UI
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @see       NoticeList
 * @see       ProfileNoticeListItem
 */
class NoticeListItem extends Widget
{
    /** The notice this item will show. */
    public $notice = null;

    /** The notice that was repeated. */
    public $repeat = null;

    /** The profile of the author of the notice, extracted once for convenience. */
    public $profile = null;

    protected $addressees = true;
    protected $attachments = true;
    protected $id_prefix = null;
    protected $options = true;
    protected $maxchars = 0;   // if <= 0 it means use full posts
    protected $item_tag = 'li';
    protected $pa = null;

    /**
     * constructor
     *
     * Also initializes the profile attribute.
     *
     * @param Notice $notice The notice we'll display
     * @param Action|null $out
     * @param array $prefs
     */
    public function __construct(Notice $notice, Action $out = null, array $prefs = [])
    {
        parent::__construct($out);
        if (!empty($notice->repeat_of)) {
            $original = Notice::getKV('id', $notice->repeat_of);
            if (!$original instanceof Notice) { // could have been deleted
                $this->notice = $notice;
            } else {
                $this->notice = $original;
                $this->repeat = $notice;
            }
        } else {
            $this->notice = $notice;
        }

        $this->profile = $this->notice->getProfile();

        // integer preferences
        foreach (['maxchars'] as $key) {
            if (array_key_exists($key, $prefs)) {
                $this->$key = (int)$prefs[$key];
            }
        }
        // boolean preferences
        foreach (['addressees', 'attachments', 'options'] as $key) {
            if (array_key_exists($key, $prefs)) {
                $this->$key = (bool)$prefs[$key];
            }
        }
        // string preferences
        foreach (['id_prefix', 'item_tag'] as $key) {
            if (array_key_exists($key, $prefs)) {
                $this->$key = $prefs[$key];
            }
        }
    }

    /**
     * recipe function for displaying a single notice.
     *
     * This uses all the other methods to correctly display a notice. Override
     * it or one of the others to fine-tune the output.
     *
     * @return void
     */
    public function show()
    {
        if (empty($this->notice)) {
            common_log(LOG_WARNING, "Trying to show missing notice; skipping.");
            return;
        } elseif (empty($this->profile)) {
            common_log(LOG_WARNING, "Trying to show missing profile (" . $this->notice->profile_id . "); skipping.");
            return;
        }

        $this->showStart();
        if (Event::handle('StartShowNoticeItem', array($this))) {
            $this->showNotice();
            Event::handle('EndShowNoticeItem', array($this));
        }
        $this->showEnd();
    }

    public function showNotice()
    {
        if (Event::handle('StartShowNoticeItemNotice', array($this))) {
            $this->showNoticeHeaders();
            $this->showContent();
            $this->showNoticeFooter();
            Event::handle('EndShowNoticeItemNotice', array($this));
        }
    }

    public function showNoticeHeaders()
    {
        $this->elementStart('section', ['class' => 'notice-headers']);
        $this->showNoticeTitle();
        $this->showAuthor();

        if (!empty($this->notice->reply_to) || count($this->getProfileAddressees()) > 0) {
            $this->elementStart('div', array('class' => 'parents'));
            try {
                $this->showParent();
            } catch (NoParentNoticeException $e) {
                // no parent notice
            } catch (InvalidUrlException $e) {
                // parent had an invalid URL so we can't show it
            }
            if ($this->addressees) {
                $this->showAddressees();
            }
            $this->elementEnd('div');
        }
        $this->elementEnd('section');
    }

    public function showNoticeFooter()
    {
        $this->elementStart('footer');
        $this->showNoticeInfo();
        if ($this->options) {
            $this->showNoticeOptions();
        }
        if ($this->attachments) {
            $this->showNoticeAttachments();
        }
        $this->elementEnd('footer');
    }

    public function showNoticeTitle()
    {
        if (Event::handle('StartShowNoticeTitle', array($this))) {
            $nameClass = $this->notice->getTitle(false) ? 'p-name ' : '';
            $this->element(
                'a',
                [
                    'href'  => $this->notice->getUri(),
                    'class' => $nameClass . 'u-uid',
                ],
                $this->notice->getTitle()
            );
            Event::handle('EndShowNoticeTitle', array($this));
        }
    }

    public function showNoticeInfo()
    {
        if (Event::handle('StartShowNoticeInfo', array($this))) {
            $this->showContextLink();
            $this->showNoticeLink();
            $this->showNoticeSource();
            $this->showNoticeLocation();
            $this->showPermalink();
            Event::handle('EndShowNoticeInfo', array($this));
        }
    }

    public function showNoticeOptions()
    {
        if (Event::handle('StartShowNoticeOptions', array($this))) {
            $user = common_current_user();
            if ($user) {
                $this->out->elementStart('div', 'notice-options');
                if (Event::handle('StartShowNoticeOptionItems', array($this))) {
                    $this->showReplyLink();
                    $this->showDeleteLink();
                    Event::handle('EndShowNoticeOptionItems', array($this));
                }
                $this->out->elementEnd('div');
            }
            Event::handle('EndShowNoticeOptions', array($this));
        }
    }

    /**
     * start a single notice.
     *
     * @return void
     */
    public function showStart()
    {
        if (Event::handle('StartOpenNoticeListItemElement', [$this])) {
            $id = (empty($this->repeat)) ? $this->notice->id : $this->repeat->id;
            $class = 'h-entry notice';
            if ($this->notice->scope != 0 && $this->notice->scope != 1) {
                $class .= ' limited-scope';
            }
            try {
                $class .= ' notice-source-' . common_to_alphanumeric($this->notice->source);
            } catch (Exception $e) {
                // either source or what we filtered out was a zero-length string
            }
            $id_prefix = (strlen($this->id_prefix) ? $this->id_prefix . '-' : '');
            $this->out->elementStart(
                $this->item_tag,
                [
                    'class' => $class,
                    'id'    => "${id_prefix}notice-${id}",
                ]
            );
            Event::handle('EndOpenNoticeListItemElement', [$this]);
        }
    }

    /**
     * show the author of a notice
     *
     * By default, this shows the avatar and (linked) nickname of the author.
     *
     * @return void
     */

    public function showAuthor()
    {
        $attrs = [
            'href'  => $this->profile->getUrl(),
            'class' => 'h-card',
            'title' => $this->profile->getHtmlTitle(),
        ];
        if (empty($this->repeat)) {
            $attrs['class'] .= ' p-author';
        }

        if (Event::handle('StartShowNoticeItemAuthor', array($this->profile, $this->out, &$attrs))) {
            $this->out->elementStart('a', $attrs);
            $this->showAvatar($this->profile);
            $this->out->text($this->profile->getStreamName());
            $this->out->elementEnd('a');
            Event::handle('EndShowNoticeItemAuthor', array($this->profile, $this->out));
        }
    }

    public function showParent()
    {
        $this->out->element(
            'a',
            array(
                'href' => $this->notice->getParent()->getUrl(),
                'class' => 'u-in-reply-to',
                'rel' => 'in-reply-to'
            ),
            'in reply to'
        );
    }

    public function showAddressees()
    {
        $pa = $this->getProfileAddressees();

        if (!empty($pa)) {
            $this->out->elementStart('ul', 'addressees');
            $first = true;
            foreach ($pa as $addr) {
                $this->out->elementStart('li');
                $text = $addr['text'];
                unset($addr['text']);
                $this->out->element('a', $addr, $text);
                $this->out->elementEnd('li');
            }
            $this->out->elementEnd('ul', 'addressees');
        }
    }

    public function getProfileAddressees()
    {
        if ($this->pa) {
            return $this->pa;
        }
        $this->pa = array();

        $attentions = $this->getAttentionProfiles();

        foreach ($attentions as $attn) {
            if ($attn->isGroup()) {
                $class = 'group';
                $profileurl = common_local_url('groupbyid', array('id' => $attn->getGroup()->getID()));
            } else {
                $class = 'account';
                $profileurl = common_local_url('userbyid', array('id' => $attn->getID()));
            }
            $this->pa[] = [
                'href'  => $profileurl,
                'title' => $attn->getHtmlTitle(),
                'class' => "addressee {$class} p-name u-url",
                'text'  => $attn->getStreamName(),
            ];
        }

        return $this->pa;
    }

    public function getAttentionProfiles()
    {
        return $this->notice->getAttentionProfiles();
    }

    /**
     * show the nickname of the author
     *
     * Links to the author's profile page
     *
     * @return void
     */
    public function showNickname()
    {
        $this->out->raw(
            '<span class="p-name">' .
            htmlspecialchars($this->profile->getNickname()) .
            '</span>'
        );
    }

    /**
     * show the content of the notice
     *
     * Shows the content of the notice. This is pre-rendered for efficiency
     * at save time. Some very old notices might not be pre-rendered, so
     * they're rendered on the spot.
     *
     * @return void
     */
    public function showContent()
    {
        // FIXME: URL, image, video, audio
        $nameClass = $this->notice->getTitle(false) ? '' : 'p-name ';
        $this->out->elementStart('article', array('class' => $nameClass . 'e-content'));
        if (Event::handle('StartShowNoticeContent', array($this->notice, $this->out, $this->out->getScoped()))) {
            if ($this->maxchars > 0 && mb_strlen($this->notice->content) > $this->maxchars) {
                $this->out->text(mb_substr($this->notice->content, 0, $this->maxchars) . '[…]');
            } else {
                $this->out->raw($this->notice->getRendered());
            }
            Event::handle('EndShowNoticeContent', array($this->notice, $this->out, $this->out->getScoped()));
        }
        $this->out->elementEnd('article');
    }

    public function showNoticeAttachments()
    {
        if (common_config('attachments', 'show_thumbs')) {
            $al = new InlineAttachmentList($this->notice, $this->out);
            $al->show();
        }
    }

    /**
     * show the link to the main page for the notice
     *
     * Displays a local link to the rendered notice, with "relative" time.
     *
     * @return void
     */
    public function showNoticeLink()
    {
        $this->out->element(
            'time',
            [
                'class' => 'dt-published',
                'datetime' => common_date_iso8601($this->notice->created),
                'title' => common_exact_date($this->notice->created),
            ],
            common_date_string($this->notice->created)
        );
    }

    /**
     * show the notice location
     *
     * shows the notice location in the correct language.
     *
     * If an URL is available, makes a link. Otherwise, just a span.
     *
     * @return void
     */
    public function showNoticeLocation()
    {
        try {
            $location = Notice_location::locFromStored($this->notice);
        } catch (NoResultException $e) {
            return;
        } catch (ServerException $e) {
            return;
        }

        $name = $location->getName();

        $lat = $location->lat;
        $lon = $location->lon;
        $latlon = (!empty($lat) && !empty($lon)) ? $lat . ';' . $lon : '';

        if (empty($name)) {
            $latdms = $this->decimalDegreesToDMS(abs($lat));
            $londms = $this->decimalDegreesToDMS(abs($lon));
            // TRANS: Used in coordinates as abbreviation of north.
            $north = _('N');
            // TRANS: Used in coordinates as abbreviation of south.
            $south = _('S');
            // TRANS: Used in coordinates as abbreviation of east.
            $east = _('E');
            // TRANS: Used in coordinates as abbreviation of west.
            $west = _('W');
            $name = sprintf(
                // TRANS: Coordinates message.
                // TRANS: %1$s is lattitude degrees, %2$s is lattitude minutes,
                // TRANS: %3$s is lattitude seconds, %4$s is N (north) or S (south) depending on lattitude,
                // TRANS: %5$s is longitude degrees, %6$s is longitude minutes,
                // TRANS: %7$s is longitude seconds, %8$s is E (east) or W (west) depending on longitude,
                _('%1$u°%2$u\'%3$u"%4$s %5$u°%6$u\'%7$u"%8$s'),
                $latdms['deg'],
                $latdms['min'],
                $latdms['sec'],
                ($lat > 0 ? $north : $south),
                $londms['deg'],
                $londms['min'],
                $londms['sec'],
                ($lon > 0 ? $east : $west)
            );
        }

        $url = $location->getUrl();

        $this->out->text(' ');
        $this->out->elementStart('span', array('class' => 'location'));
        // TRANS: Followed by geo location.
        $this->out->text(_('at'));
        $this->out->text(' ');
        if (empty($url)) {
            $this->out->element(
                'abbr',
                [
                    'class' => 'geo',
                    'title' => $latlon,
                ],
                $name
            );
        } else {
            $xstr = new XMLStringer(false);
            $xstr->elementStart(
                'a',
                ['href' => $url, 'rel' => 'external']
            );
            $xstr->element(
                'abbr',
                ['class' => 'geo', 'title' => $latlon],
                $name
            );
            $xstr->elementEnd('a');
            $this->out->raw($xstr->getString());
        }
        $this->out->elementEnd('span');
    }

    /**
     * @param number $dec decimal degrees
     * @return array split into 'deg', 'min', and 'sec'
     */
    public function decimalDegreesToDMS($dec)
    {
        $deg = intval($dec);
        $tempma = abs($dec) - abs($deg);

        $tempma = $tempma * 3600;
        $min = floor($tempma / 60);
        $sec = $tempma - ($min * 60);

        return ['deg' => $deg, 'min' => $min, 'sec' => $sec];
    }

    /**
     * Show the source of the notice
     *
     * Either the name (and link) of the API client that posted the notice,
     * or one of other other channels.
     *
     * @return void
     * @throws Exception
     */
    public function showNoticeSource()
    {
        $ns = $this->notice->getSource();

        if (!$ns instanceof Notice_source) {
            return;
        }

        // TRANS: A possible notice source (web interface).
        $source_name = (empty($ns->name)) ? ($ns->code ? _($ns->code) : _m('SOURCE', 'web')) : _($ns->name);
        $this->out->text(' ');
        $this->out->elementStart('span', 'source');
        // @todo FIXME: probably i18n issue. If "from" is followed by text, that should be a parameter to "from" (from %s).
        // TRANS: Followed by notice source.
        $this->out->text(_('from'));
        $this->out->text(' ');

        $name = $source_name;
        $url = $ns->url;
        $title = null;

        if (Event::handle('StartNoticeSourceLink', array($this->notice, &$name, &$url, &$title))) {
            $name = $source_name;
            $url = $ns->url;
        }
        Event::handle('EndNoticeSourceLink', array($this->notice, &$name, &$url, &$title));

        // if $ns->name and $ns->url are populated we have
        // configured a source attr somewhere
        if (!empty($name) && !empty($url)) {
            $this->out->elementStart('span', 'device');

            $attrs = array(
                'href' => $url,
                'rel' => 'external'
            );

            if (!empty($title)) {
                $attrs['title'] = $title;
            }

            $this->out->element('a', $attrs, $name);
            $this->out->elementEnd('span');
        } else {
            $this->out->element('span', 'device', $name);
        }

        $this->out->elementEnd('span');
    }

    /**
     * show link to single-notice view for this notice item
     *
     * A permalink that goes to this specific object and nothing else
     *
     * @return void
     */
    public function showPermalink()
    {
        $class = 'permalink u-url';
        if (!$this->notice->isLocal()) {
            $class .= ' external';
        }

        try {
            if ($this->repeat) {
                $this->out->element(
                    'a',
                    [
                        'href'  => $this->repeat->getUrl(),
                        'class' => 'u-url',
                    ],
                    ''
                );
                $class = str_replace('u-url', 'u-repost-of', $class);
            }
        } catch (InvalidUrlException $e) {
            // no permalink available
        }

        try {
            $this->out->element(
                'a',
                [
                    'href'  => $this->notice->getUrl(true),
                    'class' => $class,
                ],
                // TRANS: Addition in notice list item for single-notice view.
                _('permalink')
            );
        } catch (InvalidUrlException $e) {
            // no permalink available
        }
    }

    /**
     * Show link to conversation view.
     */
    public function showContextLink()
    {
        $this->out->element(
            'a',
            [
                'rel'   => 'bookmark',
                'class' => 'timestamp',
                'href'  => Conversation::getUrlFromNotice($this->notice),
            ],
            // TRANS: A link to the conversation view of a notice, on the local server.
            _('In conversation')
        );
    }

    /**
     * show a link to reply to the current notice
     *
     * Should either do the reply in the current notice form (if available), or
     * link out to the notice-posting form. A little flakey, doesn't always work.
     *
     * @return void
     */
    public function showReplyLink()
    {
        if (common_logged_in()) {
            $this->out->text(' ');
            $reply_url = common_local_url(
                'newnotice',
                [
                    'replyto'   => $this->profile->getNickname(),
                    'inreplyto' => $this->notice->id,
                ]
            );
            $this->out->elementStart(
                'a',
                [
                    'href' => $reply_url,
                    'class' => 'notice_reply',
                    // TRANS: Link title in notice list item to reply to a notice.
                    'title' => _('Reply to this notice.'),
                ]
            );
            // TRANS: Link text in notice list item to reply to a notice.
            $this->out->text(_('Reply'));
            $this->out->text(' ');
            $this->out->element('span', 'notice_id', $this->notice->id);
            $this->out->elementEnd('a');
        }
    }

    /**
     * if the user is the author, let them delete the notice
     *
     * @return void
     * @throws Exception
     */
    public function showDeleteLink()
    {
        $user = common_current_user();

        $todel = (empty($this->repeat)) ? $this->notice : $this->repeat;

        if (!empty($user) &&
            !$this->notice->isVerb([ActivityVerb::DELETE]) &&
            ($todel->profile_id == $user->id || $user->hasRight(Right::DELETEOTHERSNOTICE))) {
            $this->out->text(' ');
            $deleteurl = common_local_url(
                'deletenotice',
                ['notice' => $todel->id]
            );
            $this->out->element(
                'a',
                [
                    'href'  => $deleteurl,
                    'class' => 'notice_delete popup',
                    // TRANS: Link title in notice list item to delete a notice.
                    'title' => _m('Delete this notice from the timeline.'),
                ],
                // TRANS: Link text in notice list item to delete a notice.
                _m('Delete')
            );
        }
    }

    /**
     * finish the notice
     *
     * Close the last elements in the notice list item
     *
     * @return void
     */
    public function showEnd()
    {
        if (Event::handle('StartCloseNoticeListItemElement', [$this])) {
            $this->out->elementEnd('li');
            Event::handle('EndCloseNoticeListItemElement', [$this]);
        }
    }

    /**
     * Get the notice in question
     *
     * For hooks, etc., this may be useful
     *
     * @return Notice The notice we're showing
     */

    public function getNotice()
    {
        return $this->notice;
    }
}
