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
 * User profile page
 *
 * @category  Personal
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * User profile page
 *
 * When I created this page, "show stream" seemed like the best name for it.
 * Now, it seems like a really bad name.
 *
 * It shows a stream of the user's posts, plus lots of profile info, links
 * to subscriptions and stuff, etc.
 *
 * @category  Personal
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ShowstreamAction extends NoticestreamAction
{
    public function getStream()
    {
        if (empty($this->tag)) {
            $stream = new ProfileNoticeStream($this->target, $this->scoped);
        } else {
            $stream = new TaggedProfileNoticeStream($this->target, $this->tag, $this->scoped);
        }

        return $stream;
    }

    public function title()
    {
        $base = $this->target->getFancyName();
        if (!empty($this->tag)) {
            if ($this->page == 1) {
                // TRANS: Page title showing tagged notices in one user's timeline.
                // TRANS: %1$s is the username, %2$s is the hash tag.
                return sprintf(_('Notices by %1$s tagged %2$s'), $base, $this->tag);
            } else {
                // TRANS: Page title showing tagged notices in one user's timeline.
                // TRANS: %1$s is the username, %2$s is the hash tag, %3$d is the page number.
                return sprintf(_('Notices by %1$s tagged %2$s, page %3$d'), $base, $this->tag, $this->page);
            }
        } else {
            if ($this->page == 1) {
                return sprintf(_('Notices by %s'), $base);
            } else {
                // TRANS: Extended page title showing tagged notices in one user's timeline.
                // TRANS: %1$s is the username, %2$d is the page number.
                return sprintf(
                    _('Notices by %1$s, page %2$d'),
                    $base,
                    $this->page
                );
            }
        }
    }

    protected function showContent()
    {
        $this->showNotices();
    }

    public function showProfileBlock()
    {
        $block = new AccountProfileBlock($this, $this->target);
        $block->show();
    }

    public function showPageNoticeBlock()
    {
        return;
    }

    public function getFeeds()
    {
        if (!empty($this->tag)) {
            return [new Feed(
                Feed::RSS1,
                common_local_url(
                    'userrss',
                    [
                        'nickname' => $this->target->getNickname(),
                        'tag'      => $this->tag,
                    ]
                ),
                // TRANS: Title for link to notice feed.
                // TRANS: %1$s is a user nickname, %2$s is a hashtag.
                sprintf(
                    _('Notice feed for %1$s tagged %2$s (RSS 1.0)'),
                    $this->target->getNickname(),
                    $this->tag
                )
            )];
        }

        if (!$this->target->isLocal()) {
            // remote profiles at least have Atom, but we can't guarantee anything else
            return [new Feed(
                Feed::ATOM,
                $this->target->getAtomFeed(),
                // TRANS: Title for link to notice feed.
                // TRANS: %s is a user nickname.
                sprintf(
                    _('Notice feed for %s (Atom)'),
                    $this->target->getNickname()
                )
            )];
        }

        return [
            new Feed(
                Feed::JSON,
                common_local_url(
                    'ApiTimelineUser',
                    [
                        'id'     => $this->target->getID(),
                        'format' => 'as',
                    ]
                ),
                // TRANS: Title for link to notice feed.
                // TRANS: %s is a user nickname.
                sprintf(
                    _('Notice feed for %s (Activity Streams JSON)'),
                    $this->target->getNickname()
                )
            ),
            new Feed(
                Feed::RSS1,
                common_local_url(
                    'userrss',
                    ['nickname' => $this->target->getNickname()]
                ),
                // TRANS: Title for link to notice feed.
                // TRANS: %s is a user nickname.
                sprintf(
                    _('Notice feed for %s (RSS 1.0)'),
                    $this->target->getNickname()
                )
            ),
            new Feed(
                Feed::RSS2,
                common_local_url(
                    'ApiTimelineUser',
                    [
                        'id'     => $this->target->getID(),
                        'format' => 'rss',
                    ]
                ),
                // TRANS: Title for link to notice feed.
                // TRANS: %s is a user nickname.
                sprintf(
                    _('Notice feed for %s (RSS 2.0)'),
                    $this->target->getNickname()
                )
            ),
            new Feed(
                Feed::ATOM,
                $this->target->getAtomFeed(),
                // TRANS: Title for link to notice feed.
                // TRANS: %s is a user nickname.
                sprintf(
                    _('Notice feed for %s (Atom)'),
                    $this->target->getNickname()
                )
            ),
            new Feed(
                Feed::FOAF,
                common_local_url(
                    'foaf',
                    ['nickname' => $this->target->getNickname()]
                ),
                // TRANS: Title for link to notice feed. FOAF stands for Friend of a Friend.
                // TRANS: More information at http://www.foaf-project.org. %s is a user nickname.
                sprintf(_('FOAF for %s'), $this->target->getNickname())
            )
        ];
    }

    public function extraHeaders()
    {
        parent::extraHeaders();
        // Publish all the rel="me" in the HTTP headers on our main profile page
        if (get_class($this) == 'ShowstreamAction') {
            foreach ($this->target->getRelMes() as $relMe) {
                header('Link: <' . htmlspecialchars($relMe['href']) . '>; rel="me"', false);
            }
        }
    }

    public function extraHead()
    {
        if ($this->target->isSilenced() || !$this->target->isLocal()) {
            $this->element(
                'meta',
                [
                    'name'    => 'robots',
                    'content' => 'noindex',
                ]
            );
        }

        if ($this->target->bio) {
            $this->element(
                'meta',
                [
                    'name'    => 'description',
                    'content' => $this->target->getDescription(),
                ]
            );
        }

        $rsd = common_local_url(
            'rsd',
            ['nickname' => $this->target->getNickname()]
        );

        // RSD, http://tales.phrasewise.com/rfc/rsd
        $this->element(
            'link',
            [
                'rel'  => 'EditURI',
                'type' => 'application/rsd+xml',
                'href' => $rsd,
            ]
        );

        if ($this->page != 1) {
            $this->element(
                'link',
                [
                    'rel' => 'canonical',
                    'href' => $this->target->getUrl(),
                ]
            );
        }
    }

    public function showEmptyListMessage()
    {
        // TRANS: First sentence of empty list message for a timeline. $1%s is a user nickname.
        $message = sprintf(_('This is the timeline for %1$s, but %1$s hasn\'t posted anything yet.'), $this->target->getNickname()) . ' ';

        if ($this->scoped instanceof Profile) {
            if ($this->target->getID() === $this->scoped->getID()) {
                // TRANS: Second sentence of empty list message for a stream for the user themselves.
                $message .= _('Seen anything interesting recently? You haven\'t posted any notices yet, now would be a good time to start :)');
            } else {
                // TRANS: Second sentence of empty  list message for a non-self timeline. %1$s is a user nickname, %2$s is a part of a URL.
                // TRANS: This message contains a Markdown link. Keep "](" together.
                $message .= sprintf(_('You can try to nudge %1$s or [post something to them](%%%%action.newnotice%%%%?status_textarea=%2$s).'), $this->target->getNickname(), '@' . $this->target->getNickname());
            }
        } else {
            // TRANS: Second sentence of empty message for anonymous users. %s is a user nickname.
            // TRANS: This message contains a Markdown link. Keep "](" together.
            $message .= sprintf(_('Why not [register an account](%%%%action.register%%%%) and then nudge %s or post a notice to them.'), $this->target->getNickname());
        }

        $this->elementStart('div', 'guide');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('div');
    }

    public function showNotices()
    {
        $pnl = new PrimaryNoticeList($this->notice, $this);
        $cnt = $pnl->show();
        if (0 == $cnt) {
            $this->showEmptyListMessage();
        }

        // either nickname or id will be used, depending on which action (showstream, userbyid...)
        $args = array('nickname' => $this->target->getNickname(), 'id' => $this->target->getID());
        if (!empty($this->tag)) {
            $args['tag'] = $this->tag;
        }
        $this->pagination(
            $this->page > 1,
            $cnt > NOTICES_PER_PAGE,
            $this->page,
            $this->getActionName(),
            $args
        );
    }

    public function showAnonymousMessage()
    {
        if (!(common_config('site', 'closed') || common_config('site', 'inviteonly'))) {
            // TRANS: Announcement for anonymous users showing a timeline if site registrations are open.
            // TRANS: This message contains a Markdown link. Keep "](" together.
            $m = sprintf(
                _('**%s** has an account on %%%%site.name%%%%, a [micro-blogging](http://en.wikipedia.org/wiki/Micro-blogging) service ' .
                  'based on the Free Software [StatusNet](http://status.net/) tool. ' .
                  '[Join now](%%%%action.register%%%%) to follow **%s**\'s notices and many more! ([Read more](%%%%doc.help%%%%))'),
                $this->target->getNickname(),
                $this->target->getNickname()
            );
        } else {
            // TRANS: Announcement for anonymous users showing a timeline if site registrations are closed or invite only.
            // TRANS: This message contains a Markdown link. Keep "](" together.
            $m = sprintf(
                _('**%s** has an account on %%%%site.name%%%%, a [micro-blogging](http://en.wikipedia.org/wiki/Micro-blogging) service ' .
                  'based on the Free Software [StatusNet](http://status.net/) tool.'),
                $this->target->getNickname(),
                $this->target->getNickname()
            );
        }
        $this->elementStart('div', array('id' => 'anon_notice'));
        $this->raw(common_markup_to_html($m));
        $this->elementEnd('div');
    }

    public function noticeFormOptions()
    {
        $options = parent::noticeFormOptions();

        if (!$this->scoped instanceof Profile || !$this->scoped->sameAs($this->target)) {
            $options['to_profile'] = $this->target;
        }

        return $options;
    }
}
