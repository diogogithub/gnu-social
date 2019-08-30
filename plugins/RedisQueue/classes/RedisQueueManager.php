<?php

use Predis\Client;

class RedisQueueManager extends QueueManager
{
    protected $queue;
    protected $client = null;
    protected $server;

    public function __construct(string $server)
    {
        $this->server = $server;
        $this->queue = 'gnusocial:' . common_config('site', 'name');
    }

    private function _ensureConn()
    {
        if ($this->client === null) {
            $this->client = new Client($this->server);
        }
    }

    public function pollInterval()
    {
        return 10;
    }

    public function enqueue($object, $queue)
    {
        $this->_ensureConn();
        $ret = $this->client->rpush($this->queue, $this->encode([$queue, $object]));
        if (empty($ret)) {
            common_log(LOG_ERR, "Unable to insert object into Redis queue {$queue}");
        } else {
            common_debug("The Redis queue for {$queue} has length {$ret}");
        }
    }

    public function poll()
    {
        common_debug("STARTING POLL");
        try {
            $this->_ensureConn();
            $ret = $this->client->lpop($this->queue);
            if (!empty($ret)) {
                list($queue, $object) = $this->decode($ret);
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->_log(LOG_INFO, "[Queue {$queue}] Discarding: " . _ve($e->getMessage()));
            return false;
        }

        try {
            $handler = $this->getHandler($queue);
            $handler->handle($object);
            common_debug("Redis Queue handled item from {$queue} queue");
            return true;
        } catch (Exception $e) {
            $this->_log(LOG_ERR, "[Queue: {$queue}] `" . get_class($e) . '` thrown: ' . _ve($e->getMessage()));
            return false;
        }
    }
};
