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
 * Widget for displaying a list of notices.
 *
 * @category  Search
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Widget-like class for showing JSON search results.
 *
 * @category  Search
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 */

class JSONSearchResultsList
{
    protected $notice;  // protected attrs invisible to json_encode()
    protected $rpp;

    // The below attributes are carefully named so the JSON output from
    // this obj matches the output from search.twitter.com

    public $results;
    public $since_id;
    public $max_id;
    public $refresh_url;
    public $results_per_page;
    public $completed_in;
    public $page;
    public $query;

    /**
     * constructor
     *
     * @param Notice $notice   stream of notices from DB_DataObject
     * @param string $query    the original search query
     * @param int    $rpp      the number of results to display per page
     * @param int    $page     a page offset
     * @param int    $since_id only display notices newer than this
     */

    public function __construct($notice, $query, $rpp, $page, $since_id = 0)
    {
        $this->notice           = $notice;
        $this->query            = urlencode($query);
        $this->results_per_page = $rpp;
        $this->rpp              = $rpp;
        $this->page             = $page;
        $this->since_id         = $since_id;
        $this->results          = array();
    }

    /**
     * show the list of search results
     *
     * @return int $count of the search results listed.
     */

    public function show()
    {
        $cnt = 0;
        $this->max_id = 0;

        $time_start = hrtime(true);

        while ($this->notice->fetch() && $cnt <= $this->rpp) {
            $cnt++;

            // XXX: Hmmm. this depends on desc sort order
            if (!$this->max_id) {
                $this->max_id = (int)$this->notice->id;
            }

            if ($this->since_id && $this->notice->id <= $this->since_id) {
                break;
            }

            if ($cnt > $this->rpp) {
                break;
            }

            $profile = $this->notice->getProfile();

            // Don't show notices from deleted users

            if (!empty($profile)) {
                $item = new ResultItem($this->notice);
                array_push($this->results, $item);
            }
        }

        $time_end           = hrtime(true);
        $this->completed_in = ($time_end - $time_start) / 1000000000;

        // Set other attrs

        $this->refresh_url = '?since_id=' . $this->max_id .
            '&q=' . $this->query;

        // pagination stuff

        if ($cnt > $this->rpp) {
            $this->next_page = '?page=' . ($this->page + 1) .
                '&max_id=' . $this->max_id;
            if ($this->rpp != 15) {
                $this->next_page .= '&rpp=' . $this->rpp;
            }
            $this->next_page .= '&q=' . $this->query;
        }

        if ($this->page > 1) {
            $this->previous_page = '?page=' . ($this->page - 1) .
                '&max_id=' . $this->max_id;
            if ($this->rpp != 15) {
                $this->previous_page .= '&rpp=' . $this->rpp;
            }
            $this->previous_page .= '&q=' . $this->query;
        }

        print json_encode($this);

        return $cnt;
    }
}

/**
 * Widget for displaying a single JSON search result.
 *
 * @category  UI
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @see       JSONSearchResultsList
 */

class ResultItem
{
    /** The notice this item is based on. */

    protected $notice;  // protected attrs invisible to json_encode()

    /** The profile associated with the notice. */

    protected $profile;

    // The below attributes are carefully named so the JSON output from
    // this obj matches the output from search.twitter.com

    public $text;
    public $to_user_id;
    public $to_user;
    public $from_user;
    public $id;
    public $from_user_id;
    public $iso_language_code;
    public $source = null;
    public $source_link = null;
    public $profile_image_url;
    public $created_at;

    /**
     * constructor
     *
     * Also initializes the profile attribute.
     *
     * @param Notice $notice The notice we'll display
     */

    public function __construct($notice)
    {
        $this->notice  = $notice;
        $this->profile = $notice->getProfile();
        $this->buildResult();
    }

    /**
     * Build a search result object
     *
     * This populates the the result in preparation for JSON encoding.
     *
     * @return void
     */

    public function buildResult()
    {
        $this->text      = $this->notice->content;
        $replier_profile = null;

        if ($this->notice->reply_to) {
            $reply = Notice::getKV(intval($this->notice->reply_to));
            if ($reply) {
                $replier_profile = $reply->getProfile();
            }
        }

        $this->to_user_id = ($replier_profile) ?
            intval($replier_profile->id) : null;
        $this->to_user    = ($replier_profile) ?
            $replier_profile->nickname : null;

        $this->from_user    = $this->profile->nickname;
        $this->id           = $this->notice->id;
        $this->from_user_id = $this->profile->id;

        $this->iso_language_code = Profile_prefs::getConfigData($this->profile, 'site', 'language');
        
        // set source and source_link
        $this->setSourceData();

        $this->profile_image_url = $this->profile->avatarUrl(AVATAR_STREAM_SIZE);

        $this->created_at = common_date_rfc2822($this->notice->created);
    }

    /**
     * Set the notice's source data (api/app name and URL)
     *
     * Either the name (and link) of the API client that posted the notice,
     * or one of other other channels. Uses the local notice object.
     *
     * @return void
     */
    public function setSourceData()
    {
        $source = null;
        $source_link = null;

        switch ($source) {
        case 'web':
        case 'xmpp':
        case 'mail':
        case 'omb':
        case 'api':
            // Gettext translations for the below source types are available.
            $source = _($this->notice->source);
            break;

        default:
            $ns = Notice_source::getKV($this->notice->source);
            if ($ns instanceof Notice_source) {
                $source = $ns->code;
                if (!empty($ns->url)) {
                    $source_link = $ns->url;
                    if (!empty($ns->name)) {
                        $source = $ns->name;
                    }
                }
            }
            break;
        }

        $this->source = $source;
        $this->source_link = $source_link;
    }
}
