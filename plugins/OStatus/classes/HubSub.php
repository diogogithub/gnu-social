<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * WebSub (previously PuSH) feed subscription record
 * @package Hub
 * @author Brion Vibber <brion@status.net>
 */
class HubSub extends Managed_DataObject
{
    public $__table = 'hubsub';

    public $hashkey; // sha1(topic . '|' . $callback); (topic, callback) key is too long for myisam in utf8
    public $topic;      // varchar(191)   not 255 because utf8mb4 takes more space
    public $callback;   // varchar(191)   not 255 because utf8mb4 takes more space
    public $secret;
    public $sub_start;
    public $sub_end;
    public $created;
    public $modified;

    static function hashkey($topic, $callback)
    {
        return sha1($topic . '|' . $callback);
    }

    public static function getByHashkey($topic, $callback)
    {
        return self::getKV('hashkey', self::hashkey($topic, $callback));
    }

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'hashkey' => array('type' => 'char', 'not null' => true, 'length' => 40, 'description' => 'HubSub hashkey'),
                'topic' => array('type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'HubSub topic'),
                'callback' => array('type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'HubSub callback'),
                'secret' => array('type' => 'text', 'description' => 'HubSub stored secret'),
                'sub_start' => array('type' => 'datetime', 'description' => 'subscription start'),
                'sub_end' => array('type' => 'datetime', 'description' => 'subscription end'),
                'errors' => array('type' => 'int', 'not null' => true, 'default' => 0, 'description' => 'Queue handling error count, is reset on success.'),
                'error_start' => array('type' => 'datetime', 'default' => null, 'description' => 'time of first error since latest success, should be null if no errors have been counted'),
                'last_error' => array('type' => 'datetime', 'default' => null, 'description' => 'time of last failure, if ever'),
                'last_error_msg' => array('type' => 'text', 'default' => null, 'description' => 'Last error _message_'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('hashkey'),
            'indexes' => array(
                'hubsub_callback_idx' => array('callback'),
                'hubsub_topic_idx' => array('topic'),
            ),
        );
    }

    function getErrors()
    {
        return intval($this->errors);
    }

    // $msg is only set if $error_count is 0
    function setErrors($error_count, $msg=null)
    {
        assert(is_int($error_count));
        if (!is_int($error_count) || $error_count < 0) {
            common_log(LOG_ERR, 'HubSub->setErrors was given a bad value: '._ve($error_count));
            throw new ServerException('HubSub error count must be an integer higher or equal to 0.');
        }

        $orig = clone($this);
        $now = common_sql_now();

        if ($error_count === 1) {
            // Record when the errors started
            $this->error_start = $now;
        }
        if ($error_count > 0) {
            // Record this error's occurrence in time
            $this->last_error = $now;
            $this->last_error_msg = $msg;
        } else {
            $this->error_start = null;
            $this->last_error = null;
            $this->last_error_msg = null;
        }

        $this->errors = $error_count;
        $this->update($orig);
    }

    function resetErrors()
    {
        return $this->setErrors(0);
    }

    function incrementErrors($msg=null)
    {
        return $this->setErrors($this->getErrors()+1, $msg);
    }

    /**
     * Validates a requested lease length, sets length plus
     * subscription start & end dates.
     *
     * Does not save to database -- use before insert() or update().
     *
     * @param int $length in seconds
     */
    function setLease($length)
    {
        common_debug('WebSub hub got requested lease_seconds=='._ve($length));
        assert(is_int($length));

        $min = 86400;   // 3600*24 (one day)
        $max = 86400 * 30;

        if ($length == 0) {
            // We want to garbage collect dead subscriptions!
            $length = $max;
        } elseif( $length < $min) {
            $length = $min;
        } else if ($length > $max) {
            $length = $max;
        }

        common_debug('WebSub hub after sanitation: lease_seconds=='._ve($length));
        $this->sub_start = common_sql_now();
        $this->sub_end = common_sql_date(time() + $length);
    }

    function getLeaseTime()
    {
        if (empty($this->sub_start) || empty($this->sub_end)) {
            return null;
        }
        $length = strtotime($this->sub_end) - strtotime($this->sub_start);
        assert($length > 0);
        return $length;
    }

    function getLeaseRemaining()
    {
        if (empty($this->sub_end)) {
            return null;
        }
        return strtotime($this->sub_end) - time();
    }

    /**
     * Schedule a future verification ping to the subscriber.
     * If queues are disabled, will be immediate.
     *
     * @param string $mode 'subscribe' or 'unsubscribe'
     * @param string $token hub.verify_token value, if provided by client
     */
    function scheduleVerify($mode, $token=null, $retries=null)
    {
        if ($retries === null) {
            $retries = intval(common_config('ostatus', 'hub_retries'));
        }
        $data = array('sub' => clone($this),
                      'mode' => $mode,
                      'token' => $token,    // let's put it in there if remote uses PuSH <0.4
                      'retries' => $retries);
        $qm = QueueManager::get();
        $qm->enqueue($data, 'hubconf');
    }

    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Send a verification ping to subscriber, and if confirmed apply the changes.
     * This may create, update, or delete the database record.
     *
     * @param string $mode 'subscribe' or 'unsubscribe'
     * @param string $token hub.verify_token value, if provided by client
     * @throws ClientException on failure
     */
    function verify($mode, $token=null)
    {
        assert($mode == 'subscribe' || $mode == 'unsubscribe');

        $challenge = common_random_hexstr(32);
        $params = array('hub.mode' => $mode,
                        'hub.topic' => $this->getTopic(),
                        'hub.challenge' => $challenge);
        if ($mode == 'subscribe') {
            $params['hub.lease_seconds'] = $this->getLeaseTime();
        }
        if ($token !== null) {  // TODO: deprecated in PuSH 0.4
            $params['hub.verify_token'] = $token;   // let's put it in there if remote uses PuSH <0.4
        }

        // Any existing query string parameters must be preserved
        $url = $this->callback;
        if (strpos($url, '?') !== false) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        $url .= http_build_query($params, '', '&');

        $request = new HTTPClient();
        $response = $request->get($url);
        $status = $response->getStatus();

        if ($status >= 200 && $status < 300) {
            common_log(LOG_INFO, "Verified {$mode} of {$this->callback}:{$this->getTopic()}");
        } else {
            // TRANS: Client exception. %s is a HTTP status code.
            throw new ClientException(sprintf(_m('Hub subscriber verification returned HTTP %s.'),$status));
        }

        $old = HubSub::getByHashkey($this->getTopic(), $this->callback);
        if ($mode == 'subscribe') {
            if ($old instanceof HubSub) {
                $this->update($old);
            } else {
                $ok = $this->insert();
            }
        } else if ($mode == 'unsubscribe') {
            if ($old instanceof HubSub) {
                $old->delete();
            } else {
                // That's ok, we're already unsubscribed.
            }
        }
    }

    // set the hashkey automagically on insert
    protected function onInsert()
    {
        $this->setHashkey();
        $this->created = common_sql_now();
        $this->modified = common_sql_now();
    }

    // update the hashkey automagically if needed
    protected function onUpdateKeys(Managed_DataObject $orig)
    {
        if ($this->topic !== $orig->topic || $this->callback !== $orig->callback) {
            $this->setHashkey();
        }
    }

    protected function setHashkey()
    {
        $this->hashkey = self::hashkey($this->topic, $this->callback);
    }

    /**
     * Schedule delivery of a 'fat ping' to the subscriber's callback
     * endpoint. If queues are disabled, this will run immediately.
     *
     * @param string $atom well-formed Atom feed
     * @param int $retries optional count of retries if POST fails; defaults to hub_retries from config or 0 if unset
     */
    function distribute($atom, $retries=null)
    {
        if ($retries === null) {
            $retries = intval(common_config('ostatus', 'hub_retries'));
        }

        $data = array('topic' => $this->getTopic(),
                      'callback' => $this->callback,
                      'atom' => $atom,
                      'retries' => $retries);
        common_log(LOG_INFO, sprintf('Queuing WebSub: %s to %s', _ve($data['topic']), _ve($data['callback'])));
        $qm = QueueManager::get();
        $qm->enqueue($data, 'hubout');
    }

    /**
     * Queue up a large batch of pushes to multiple subscribers
     * for this same topic update.
     *
     * If queues are disabled, this will run immediately.
     *
     * @param string $atom well-formed Atom feed
     * @param array $pushCallbacks list of callback URLs
     */
    function bulkDistribute($atom, array $pushCallbacks)
    {
        if (empty($pushCallbacks)) {
            common_log(LOG_ERR, 'Callback list empty for bulkDistribute.');
            return false;
        }
        $data = array('atom' => $atom,
                      'topic' => $this->getTopic(),
                      'pushCallbacks' => $pushCallbacks);
        common_log(LOG_INFO, "Queuing WebSub batch: {$this->getTopic()} to ".count($pushCallbacks)." sites");
        $qm = QueueManager::get();
        $qm->enqueue($data, 'hubprep');
        return true;
    }

    /**
     * @return  boolean     true/false for HTTP response
     * @throws  Exception   for lower-than-HTTP errors (such as NS lookup failure, connection refused...)
     */
    public static function pushAtom($topic, $callback, $atom, $secret=null, $hashalg='sha1')
    {
        $headers = array('Content-Type: application/atom+xml');
        if ($secret) {
            $hmac = hash_hmac($hashalg, $atom, $secret);
            $headers[] = "X-Hub-Signature: {$hashalg}={$hmac}";
        } else {
            $hmac = '(none)';
        }
        common_log(LOG_INFO, sprintf('About to WebSub-push feed to %s for %s, HMAC %s', _ve($callback), _ve($topic), _ve($hmac)));

        $request = new HTTPClient();
        $request->setConfig(array('follow_redirects' => false));
        $request->setBody($atom);

        // This will throw exception on non-HTTP failures
        try {
            $response = $request->post($callback, $headers);
        } catch (Exception $e) {
            common_debug(sprintf('WebSub callback to %s for %s failed with exception %s: %s', _ve($callback), _ve($topic), _ve(get_class($e)), _ve($e->getMessage())));
            throw $e;
        }

        return $response->isOk();
    }

    /**
     * Send a 'fat ping' to the subscriber's callback endpoint
     * containing the given Atom feed chunk.
     *
     * Determination of which feed items to send should be done at
     * a higher level; don't just shove in a complete feed!
     *
     * FIXME: Add 'failed' incremental count.
     *
     * @param string $atom well-formed Atom feed
     * @return  boolean     Whether the PuSH was accepted or not.
     * @throws Exception (HTTP or general)
     */
    function push($atom)
    {
        try {
            $success = self::pushAtom($this->getTopic(), $this->callback, $atom, $this->secret);
            if ($success) {
                return true;
            } elseif ('https' === parse_url($this->callback, PHP_URL_SCHEME)) {
                // Already HTTPS, no need to check remote http/https migration issues
                return false;
            }
            // if pushAtom returned false and we didn't try an HTTPS endpoint,
            // let's try HTTPS too (assuming only http:// and https:// are used ;))

        } catch (Exception $e) {
            if ('https' === parse_url($this->callback, PHP_URL_SCHEME)) {
                // Already HTTPS, no need to check remote http/https migration issues
                throw $e;
            }
        }


        // We failed the WebSub push, but it might be that the remote site has changed their configuration to HTTPS
        common_debug('WebSub HTTPSFIX: push failed, so we need to see if it can be the remote http->https migration issue.');

        // XXX: DO NOT trust a Location header here, _especially_ from 'http' protocols,
        // but not 'https' either at least if we don't do proper CA verification. Trust that
        // the most common change here is simply switching 'http' to 'https' and we will
        // solve 99% of all of these issues for now. There should be a proper mechanism
        // if we want to change the callback URLs, preferrably just manual resubscriptions
        // from the remote side, combined with implemented WebSub subscription timeouts.

        // Test if the feed callback for this node has already been migrated to HTTPS in our database
        // (otherwise we'd get collisions when inserting it further down)
        $httpscallback = preg_replace('/^http/', 'https', $this->callback, 1);
        $alreadyreplaced = self::getByHashKey($this->getTopic(), $httpscallback);
        if ($alreadyreplaced instanceof HubSub) {
            // Let's remove the old HTTP callback object.
            $this->delete();

            // XXX: I think this means we might lose a message or two when 
            //      remote side migrates to HTTPS because we only try _once_ 
            //      for _one_ WebSub push. The rest of the posts already 
            //      stored in our queue (if any) will not find a HubSub
            //      object. This could maybe be fixed by handling migration 
            //      in HubOutQueueHandler while handling the item there.
            common_debug('WebSub HTTPSFIX: Pushing Atom to HTTPS callback instead of HTTP, because of switch to HTTPS since enrolled in queue.');
            return self::pushAtom($this->getTopic(), $httpscallback, $atom, $this->secret);
        }

        common_debug('WebSub HTTPSFIX: callback to '._ve($this->callback).' for '._ve($this->getTopic()).' trying HTTPS callback: '._ve($httpscallback));
        $success = self::pushAtom($this->getTopic(), $httpscallback, $atom, $this->secret);
        if ($success) {
            // Yay, we made a successful push to https://, let's remember this in the future!
            $orig = clone($this);
            $this->callback = $httpscallback;
            // NOTE: hashkey will be set in $this->onUpdateKeys($orig) through updateWithKeys
            $this->updateWithKeys($orig);
            return true;
        }

        // If there have been any exceptions thrown before, they're handled 
        // higher up. This function's return value is just whether the WebSub 
        // push was accepted or not.
        return $success;
    }
}
