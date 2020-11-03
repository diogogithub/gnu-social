<?php

// {{{ License

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

// }}}

namespace App\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

class Poll extends Entity
{
    // {{{ Autocode

    private int $id;
    private string $uri;
    private ?int $profile_id;
    private ?string $question;
    private ?string $options;
    private DateTimeInterface $created;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setProfileId(?int $profile_id): self
    {
        $this->profile_id = $profile_id;
        return $this;
    }

    public function getProfileId(): ?int
    {
        return $this->profile_id;
    }

    public function setQuestion(?string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setOptions(?string $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): ?string
    {
        return $this->options;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    // }}} Autocode

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef(): array
    {
        return [
            'name'        => 'poll',
            'description' => 'Per-notice poll data for Poll plugin',
            'fields'      => [
                'id'         => ['type' => 'int', 'not null' => true],
                'uri'        => ['type' => 'varchar', 'length' => 191, 'not null' => true],
                'profile_id' => ['type' => 'int'], //-> gsactor id?
                'question'   => ['type' => 'text'],
                'options'    => ['type' => 'text'],
                'created'    => ['type' => 'datetime', 'not null' => true],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'poll_uri_key' => ['uri'],
            ],
        ];
    }

    public static function getFromId(int $id): ?self
    {
        return DB::find('poll', ['id' => $id]);
    }

    //from old version
    /**
     * Get a bookmark based on a notice
     *
     * @param Notice $notice Notice to check for
     *
     * @return get_called_class found poll or null
     */
    /*
    public static function getByNotice($notice)
    {
        return self::getKV('uri', $notice->uri);
    }
    */
    /*
    public function getOptions()
    {
        return explode("\n", $this->options);
    }
    */

    /**
     * Is this a valid selection index?
     *
     * @param int $selection (1-based)
     *
     * @return bool
     */
    /*
    public function isValidSelection($selection)
    {
        if ($selection != intval($selection)) {
            return false;
        }
        if ($selection < 1 || $selection > count($this->getOptions())) {
            return false;
        }
        return true;
    }
    */

    /*
    public function getNotice()
    {
        return Notice::getKV('uri', $this->uri);
    }

    public function getUrl()
    {
        return $this->getNotice()->getUrl();
    }*/

    /**
     * Get the response of a particular user to this poll, if any.
     *
     * @param Profile $profile
     *
     * @return get_called_class object or null
     */
    /*
    public function getResponse(Profile $profile)
    {
        $pr = Poll_response::pkeyGet(array('poll_id' => $this->id,
            'profile_id' => $profile->id));
        return $pr;
    }
*/
    /*
    public function countResponses()
    {
        $pr = new Poll_response();
        $pr->poll_id = $this->id;
        $pr->groupBy('selection');
        $pr->selectAdd('count(profile_id) as votes');
        $pr->find();

        $raw = array();
        while ($pr->fetch()) {
            // Votes list 1-based
            // Array stores 0-based
            $raw[$pr->selection - 1] = $pr->votes;
        }

        $counts = array();
        foreach (array_keys($this->getOptions()) as $key) {
            if (isset($raw[$key])) {
                $counts[$key] = $raw[$key];
            } else {
                $counts[$key] = 0;
            }
        }
        return $counts;
    }*/

    /**
     * Save a new poll notice
     *
     * @param Profile $profile
     * @param string  $question
     * @param array   $opts     (poll responses)
     * @param null    $options
     *
     * @throws ClientException
     *
     * @return Notice saved notice
     */
    /*
    public static function saveNew($profile, $question, $opts, $options = null)
    {
        if (empty($options)) {
            $options = array();
        }

        $p = new Poll();

        $p->id = UUID::gen();
        $p->profile_id = $profile->id;
        $p->question = $question;
        $p->options = implode("\n", $opts);

        if (array_key_exists('created', $options)) {
            $p->created = $options['created'];
        } else {
            $p->created = common_sql_now();
        }

        if (array_key_exists('uri', $options)) {
            $p->uri = $options['uri'];
        } else {
            $p->uri = common_local_url(
                'showpoll',
                array('id' => $p->id)
            );
        }

        common_log(LOG_DEBUG, "Saving poll: $p->id $p->uri");
        $p->insert();

        // TRANS: Notice content creating a poll.
        // TRANS: %1$s is the poll question, %2$s is a link to the poll.
        $content = sprintf(
            _m('Poll: %1$s %2$s'),
            $question,
            $p->uri
        );
        $link = '<a href="' . htmlspecialchars($p->uri) . '">' . htmlspecialchars($question) . '</a>';
        // TRANS: Rendered version of the notice content creating a poll.
        // TRANS: %s is a link to the poll with the question as link description.
        $rendered = sprintf(_m('Poll: %s'), $link);

        $tags = array('poll');
        $replies = array();

        $options = array_merge(
            array('urls' => array(),
                'rendered' => $rendered,
                'tags' => $tags,
                'replies' => $replies,
                'object_type' => PollPlugin::POLL_OBJECT),
            $options
        );

        if (!array_key_exists('uri', $options)) {
            $options['uri'] = $p->uri;
        }

        $saved = Notice::saveNew(
            $profile->id,
            $content,
            array_key_exists('source', $options) ?
                $options['source'] : 'web',
            $options
        );

        return $saved;
    }
    */
}
