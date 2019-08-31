<?php

use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Network\Connection;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;
use Stomp\StatefulStomp;

class StompQueueManager extends QueueManager
{
    protected $pl = null;
    protected $stomps = null;
    protected $transaction;
    protected $transactionCount;
    protected $activeGroups;

    public function __construct($pl)
    {
        parent::__construct();
        $this->pl = $pl;
    }

    /**
     * Initialize our connection and subscribe to all the queues
     * we're going to need to handle... If multiple queue servers
     * are configured for failover, we'll listen to all of them.
     *
     * Side effects: in multi-site mode, may reset site configuration.
     *
     * @param IoMaster $master process/event controller
     * @return bool return false on failure
     * @throws ServerException
     */
    public function start($master)
    {
        parent::start($master);
        common_debug("Starting STOMP queue manager");
        return $this->_ensureConn();
    }

    /**
     * Close out any active connections.
     *
     * @return bool return false on failure
     * @throws Exception
     */
    public function finish()
    {
        foreach ($this->stomps as $idx => $stomp) {
            common_log(LOG_INFO, "Unsubscribing on: " . $stomp->getClient()->getConnection()->getHost());
            $stomp->unsubscribe();
        }
        // If there are any outstanding delivered messages we haven't processed,
        // free them for another thread to take.
        foreach ($this->stomps as $i => $st) {
            if ($st) {
                $this->rollback($i);
                $st->getClient()->disconnect();
                $this->stomps[$i] = null;
            }
        }
        return true;
    }


    /**
     * Lazy open connections to all STOMP servers, if in manual failover
     * mode. This means the queue servers don't speak to each other, so
     * we have to listen to all of them to make sure we get all events.
     */
    private function _ensureConn()
    {
        if ($this->stomps === null) {
            if (!$this->pl->manualFailover) {
                $list = $this->pl->servers;
                if (count($list) > 1) {
                    shuffle($list); // Randomize to spread load
                    $url = 'failover://(' . implode(',', $list) . ')';
                } else {
                    $url = $list[0];
                }
                $st = $this->_doConnect($url);
                $this->stomps = [$st];
                $this->transactionCount = [0];
                $this->transaction = [null];
                $this->disconnect = [null];
            } else {
                $this->stomps = [];
                $this->transactionCount = [];
                $this->transaction = [];
                foreach ($this->pl->servers as $_ => $url) {
                    try {
                        $this->stomps[] = $this->_doConnect($url);
                        $this->disconnect[] = null; // If the previous succeeded
                    } catch (Exception $e) {
                        // s'okay, we'll live
                        $this->stomps[] = null;
                        $this->disconnect[] = time();
                    }
                    $this->transactionCount[] = 0;
                    $this->transaction[] = null;
                }
            }
            // Done attempting connections
            if (empty($this->stomps)) {
                throw new ServerException("No STOMP queue servers reachable");
            } else {
                foreach ($this->stomps as $i => $st) {
                    if ($st) {
                        $this->subscribe($st);
                        $this->begin($i);
                    }
                }
            }
        }
        return true;
    }

    protected function _doConnect($server_url)
    {
        common_debug("STOMP: connecting to '{$server_url}' as '{$this->pl->username}'...");
        $cl = new Client($server_url);
        $cl->setLogin($this->pl->username, $this->pl->password);
        $cl->setVhostname($this->pl->vhost);

        try {
            $cl->connect();
            common_debug("STOMP connected.");
        } catch (StompException $e) {
            common_log(LOG_ERR, 'Failed to connect to STOMP queue server');
            throw new ServerException('Failed to connect to STOMP queue server');
        }

        return new StatefulStomp($cl);
    }

    /**
     * Grab a full list of stomp-side queue subscriptions.
     * Will include:
     *  - control broadcast channel
     *  - shared group queues for active groups
     *  - per-handler and per-site breakouts that are rooted in the active groups.
     *
     * @return array of strings
     */
    protected function subscriptions(): array
    {
        $subs = [];
        $subs[] = $this->pl->control;

        foreach ($this->activeGroups as $group) {
            $subs[] = $this->pl->basename . $group;
        }

        foreach ($this->pl->breakout as $spec) {
            $parts = explode(':', $spec);
            if (count($parts) < 2 || count($parts) > 3) {
                common_log(LOG_ERR, "Bad queue breakout specifier '{$spec}'");
            }
            if (in_array($parts[0], $this->activeGroups)) {
                $subs[] = $this->pl->basename . $spec;
            }
        }

        return array_unique($subs);
    }

    /**
     * Set up all our raw queue subscriptions on the given connection
     * @param Client $st
     */
    protected function subscribe(StatefulStomp $st)
    {
        $host = $st->getClient()->getConnection()->getHost();
        foreach ($this->subscriptions() as $sub) {
            if (!in_array($sub, $this->subscriptions)) {
                $this->_log(LOG_INFO, "Subscribing to '{$sub}' on '{$host}'");
                try {
                    $st->subscribe($sub);
                } catch (Exception $e) {
                    common_log(LOG_ERR, "STOMP received exception: " . get_class($e) .
                               " while trying to subscribe: " . $e->getMessage());
                    throw $e;
                }

                $this->subscriptions[] = $sub;
            }
        }
    }

    protected function begin($idx)
    {
        if ($this->pl->useTransactions) {
            if (!empty($this->transaction[$idx])) {
                throw new Exception("Tried to start transaction in the middle of a transaction");
            }
            $this->transactionCount[$idx]++;
            $this->transaction[$idx] = $this->master->id . '-' . $this->transactionCount[$idx] . '-' . time();
            $this->stomps[$idx]->begin($this->transaction[$idx]);
        }
    }

    protected function ack($idx, $frame)
    {
        if ($this->pl->useAcks) {
            if ($this->pl->useTransactions) {
                if (empty($this->transaction[$idx])) {
                    throw new Exception("Tried to ack but not in a transaction");
                }
                $this->stomps[$idx]->ack($frame, $this->transaction[$idx]);
            } else {
                $this->stomps[$idx]->ack($frame);
            }
        }
    }

    protected function commit($idx)
    {
        if ($this->useTransactions) {
            if (empty($this->transaction[$idx])) {
                throw new Exception("Tried to commit but not in a transaction");
            }
            $this->stomps[$idx]->commit($this->transaction[$idx]);
            $this->transaction[$idx] = null;
        }
    }

    protected function rollback($idx)
    {
        if ($this->useTransactions) {
            if (empty($this->transaction[$idx])) {
                throw new Exception("Tried to rollback but not in a transaction");
            }
            $this->stomps[$idx]->abort($this->transaction[$idx]);
            $this->transaction[$idx] = null;
        }
    }

    /**
     * Saves an object into the queue item table.
     *
     * @param mixed $object
     * @param string $queue
     * @param string $siteNickname optional override to drop into another site's queue
     * @throws Exception
     */
    public function enqueue($object, $queue, $siteNickname = null)
    {
        $this->_ensureConn();
        $idx = $this->pl->defaultIdx;
        $rep = $this->logrep($object);
        $envelope = ['site' => $siteNickname ?: common_config('site', 'nickname'),
                     'handler' => $queue,
                     'payload' => $this->encode($object)];
        $msg = base64_encode(serialize($envelope));

        $props = ['created' => common_sql_now()];
        if ($this->isPersistent($queue)) {
            $props['persistent'] = 'true';
        }

        $st = $this->stomps[$idx];
        $host = $st->getClient()->getConnection()->getHost();
        $target = $this->queueName($queue);

        $result = $st->send($target, new Message($msg), $props);

        if (!$result) {
            common_log(LOG_ERR, "STOMP error sending $rep to $queue queue on $host $target");
            return false;
        }

        common_debug("STOMP complete remote queueing $rep for queue `$queue` on host `$host` on channel `$target`");
        $this->stats('enqueued', $queue);
        return true;
    }

    /**
     * Determine whether messages to this queue should be marked as persistent.
     * Actual persistent storage depends on the queue server's configuration.
     * @param string $queue
     * @return bool
     */
    protected function isPersistent($queue)
    {
        $mode = $this->pl->persistent;
        if (is_array($mode)) {
            return in_array($queue, $mode);
        } else {
            return (bool)$mode;
        }
    }

    /**
     * Send any sockets we're listening on to the IO manager
     * to wait for input.
     *
     * @return array of resources
     */
    public function getSockets()
    {
        $sockets = [];
        foreach ($this->stomps as $st) {
            if ($st) {
                $sockets[] = $st->getClient()->getConnection();
            }
        }
        return $sockets;
    }

    /**
     * Get the Stomp connection object associated with the given socket.
     * @param resource $socket
     * @return int index into connections list
     * @throws Exception
     */
    protected function connectionFromSocket($socket)
    {
        foreach ($this->stomps as $i => $st) {
            if ($st && $st->getConnection() === $socket) {
                return $i;
            }
        }
        throw new Exception(__CLASS__ . " asked to read from unrecognized socket");
    }

    /**
     * Handle and acknowledge an event that's come in through a queue.
     *
     * If the queue handler reports failure, the message is requeued for later.
     * Missing notices or handler classes will drop the message.
     *
     * Side effects: in multi-site mode, may reset site configuration to
     * match the site that queued the event.
     *
     * @param Frame $frame
     * @return bool success
     * @throws ConfigException
     * @throws NoConfigException
     * @throws ServerException
     * @throws StompException
     */
    protected function handleItem($frame): bool
    {
        $host = $this->stomps[$this->pl->defaultIdx]->getHost();
        $message = unserialize(base64_decode($frame->body));

        if ($message === false) {
            common_log(LOG_ERR, "STOMP can't unserialize frame: {$frame->body}\n" .
                       'Unserializable frame length: ' . strlen($frame->body));
            return false;
        }

        $site = $message['site'];
        $queue = $message['handler'];

        if ($this->isDeadLetter($frame, $message)) {
            $this->stats('deadletter', $queue);
            return false;
        }

        // @fixme detect failing site switches
        $this->switchSite($site);

        try {
            $item = $this->decode($message['payload']);
        } catch (Exception $e) {
            common_log(LOG_ERR, "Skipping empty or deleted item in queue {$queue} from {$host}");
            $this->stats('baditem', $queue);
            return false;
        }

        $info = $this->logrep($item) . ' posted at ' .
                $frame->getHeaders()['created'] . " in queue {$queue} from {$host}";
        try {
            $handler = $this->getHandler($queue);
            $ok = $handler->handle($item);
        } catch (NoQueueHandlerException $e) {
            common_log(LOG_ERR, "Missing handler class; skipping $info");
            $this->stats('badhandler', $queue);
            return false;
        } catch (Exception $e) {
            common_log(LOG_ERR, "Exception on queue $queue: " . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            common_log(LOG_INFO, "Successfully handled $info");
            $this->stats('handled', $queue);
        } else {
            common_log(LOG_WARNING, "Failed handling $info");
            // Requeing moves the item to the end of the line for its next try.
            // @fixme add a manual retry count
            $this->enqueue($item, $queue);
            $this->stats('requeued', $queue);
        }

        return $ok;
    }

    /**
     * Check if a redelivered message has been run through enough
     * that we're going to give up on it.
     *
     * @param Frame $frame
     * @param array $message unserialized message body
     * @return bool true if we should discard
     */
    protected function isDeadLetter($frame, $message)
    {
        if (isset($frame->getHeaders()['redelivered']) && $frame->getHeaders()['redelivered'] == 'true') {
            // Message was redelivered, possibly indicating a previous failure.
            $msgId = $frame->getHeaders()['message-id'];
            $site = $message['site'];
            $queue = $message['handler'];
            $msgInfo = "message $msgId for $site in queue $queue";

            $deliveries = $this->incDeliveryCount($msgId);
            if ($deliveries > common_config('queue', 'max_retries')) {
                $info = "DEAD-LETTER FILE: Gave up after retry $deliveries on $msgInfo";

                $outdir = common_config('queue', 'dead_letter_dir');
                if ($outdir) {
                    $filename = $outdir . "/$site-$queue-" . rawurlencode($msgId);
                    $info .= ": dumping to $filename";
                    file_put_contents($filename, $message['payload']);
                }

                common_log(LOG_ERR, $info);
                return true;
            } else {
                common_log(LOG_INFO, "retry $deliveries on $msgInfo");
            }
        }
        return false;
    }

    /**
     * Update count of times we've re-encountered this message recently,
     * triggered when we get a message marked as 'redelivered'.
     *
     * Requires a CLI-friendly cache configuration.
     *
     * @param string $msgId message-id header from message
     * @return int number of retries recorded
     */
    function incDeliveryCount($msgId)
    {
        $count = 0;
        $cache = Cache::instance();
        if ($cache) {
            $key = 'gnusocial:stomp:message-retries:' . $msgId;
            $count = $cache->increment($key);
            if (!$count) {
                $count = 1;
                $cache->set($key, $count, null, 3600);
            }
        }
        return $count;
    }

    /**
     * Combines the queue_basename from configuration with the
     * group name for this queue to give eg:
     *
     * /queue/statusnet/main
     * /queue/statusnet/main/distrib
     * /queue/statusnet/xmpp/xmppout/site01
     *
     * @param string $queue
     * @return string
     * @throws Exception
     */
    protected function queueName(string $queue): string
    {
        $group = $this->queueGroup($queue);
        $site = GNUsocial::currentSite();

        foreach (["$group:$queue:$site", "$group:$queue"] as $spec) {
            if (in_array($spec, $this->breakout)) {
                return $this->pl->basename . $spec;
            }
        }
        return $this->pl->basename . $group;
    }

    /**
     * Get the breakout mode for the given queue on the current site.
     *
     * @param string $queue
     * @return string one of 'shared', 'handler', 'site'
     */
    protected function breakoutMode($queue)
    {
        if (isset($this->pl->breakout[$queue])) {
            return $this->pl->breakout[$queue];
        } else if (isset($this->pl->breakout['*'])) {
            return $this->pl->breakout['*'];
        } else {
            return 'shared';
        }
    }

    /**
     * Tell the i/o master we only need a single instance to cover
     * all sites running in this process.
     */
    public static function multiSite()
    {
        return IoManager::INSTANCE_PER_PROCESS;
    }

    /**
     * Optional; ping any running queue handler daemons with a notification
     * such as announcing a new site to handle or requesting clean shutdown.
     * This avoids having to restart all the daemons manually to update configs
     * and such.
     *
     * Currently only relevant for multi-site queue managers such as Stomp.
     *
     * @param string $event event key
     * @param string $param optional parameter to append to key
     * @return bool success
     */
    public function sendControlSignal($event, $param = '')
    {
        $message = $event;
        if ($param != '') {
            $message .= ':' . $param;
        }
        $this->_ensureConn();
        $st = $this->stomps[$this->pl->defaultIdx];
        $result = $st->send($this->pl->control, $message, ['created' => common_sql_now()]);
        if ($result) {
            common_log(LOG_INFO, "Sent control ping to STOMP queue daemons: $message");
            return true;
        } else {
            common_log(LOG_ERR, "Failed sending control ping to STOMP queue daemons: $message");
            return false;
        }
    }

    /**
     * Process a control signal broadcast.
     *
     * @param int $idx connection index
     * @param array $frame Stomp frame
     * @return bool true to continue; false to stop further processing.
     * @throws ConfigException
     * @throws NoConfigException
     * @throws ServerException
     */
    protected function handleControlSignal(int $idx, $frame): bool
    {
        $message = trim($frame->body);
        if (strpos($message, ':') !== false) {
            list($event, $param) = explode(':', $message, 2);
        } else {
            $event = $message;
            $param = '';
        }

        $shutdown = false;

        if ($event == 'shutdown') {
            $this->master->requestShutdown();
            $shutdown = true;
        } else if ($event == 'restart') {
            $this->master->requestRestart();
            $shutdown = true;
        } else if ($event == 'update') {
            $this->updateSiteConfig($param);
        } else {
            common_log(LOG_ERR, "Ignoring unrecognized control message: $message");
        }
        return $shutdown;
    }

    /**
     * Switch site, if necessary, and reset current handler assignments
     * @param string $site
     * @throws ConfigException
     * @throws NoConfigException
     * @throws ServerException
     */
    function switchSite($site)
    {
        if ($site != GNUsocial::currentSite()) {
            $this->stats('switch');
            GNUsocial::switchSite($site);
            $this->initialize();
        }
    }

    /**
     * (Re)load runtime configuration for a given site by nickname,
     * triggered by a broadcast to the 'statusnet-control' topic.
     *
     * Configuration changes in database should update, but config
     * files might not.
     *
     * @param $nickname
     * @return void true to continue; false to stop further processing.
     * @throws ConfigException
     * @throws NoConfigException
     * @throws ServerException
     */
    protected function updateSiteConfig($nickname)
    {
        $sn = Status_network::getKV('nickname', $nickname);
        if ($sn) {
            $this->switchSite($nickname);
            if (!in_array($nickname, $this->sites)) {
                $this->addSite();
            }
            $this->stats('siteupdate');
        } else {
            common_log(LOG_ERR, "Ignoring ping for unrecognized new site $nickname");
        }
    }
};
