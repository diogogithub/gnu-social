<?php
/**
 * GNU social queue-manager-on-visit class
 *
 * Will run events for a certain time, or until finished.
 *
 * Configure remote key if wanted with $config['opportunisticqm']['qmkey'] and
 * use with /main/runqueue?qmkey=abc123
 *
 * @category  Cron
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2013 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class OpportunisticQueueManager extends QueueManager
{
    protected $qmkey = false;
    protected $max_execution_time = null;
    protected $max_execution_margin = null; // margin to PHP's max_execution_time
    protected $max_queue_items = null;

    protected $started_at = null;
    protected $handled_items = 0;

    protected $verbosity = null;

    // typically just used for the /main/cron action, only used if php.ini max_execution_time is 0
    const MAXEXECTIME = 20;

    public function __construct(array $args = []) {
        foreach (get_class_vars(get_class($this)) as $key=>$val) {
            if (array_key_exists($key, $args)) {
                $this->$key = $args[$key];
            }
        }
        $this->verifyKey();

        if ($this->started_at === null) {
            $this->started_at = time();
        }

        if ($this->max_execution_time === null) {
            $this->max_execution_time = ini_get('max_execution_time') ?: self::MAXEXECTIME;
        }

        if ($this->max_execution_margin === null) {
            // think PHP's max exec time, minus this value to have time for timeouts etc.
            $this->max_execution_margin = common_config('http', 'connect_timeout') + 1;
        }

        return parent::__construct();
    }


    protected function verifyKey()
    {
        if ($this->qmkey !== common_config('opportunisticqm', 'qmkey')) {
            throw new RunQueueBadKeyException($this->qmkey);
        }
    }

    public function enqueue($object, $key)
    {
        // Nothing to do, should never get called
        throw new ServerException('OpportunisticQueueManager::enqueue should never be called');
    }

    public function canContinue()
    {
        $time_passed = time() - $this->started_at;

        // Only continue if limit values are sane
        if ($time_passed <= 0 && (!is_null($this->max_queue_items) && $this->max_queue_items <= 0)) {
            return false;
        }
        // If too much time has passed, stop
        if ($time_passed >= $this->max_execution_time ||
            $time_passed > ini_get('max_execution_time') - $this->max_execution_margin) {
            return false;
        }
        // If we have a max-item-limit, check if it has been passed
        if (!is_null($this->max_queue_items) && $this->handled_items >= $this->max_queue_items) {
            return false;
        }

        return true;
    }

    public function poll()
    {
        $qm = $this->get();
        if ($qm->pollInterval() <= 0 && ! $qm instanceof UnQueueManager) {
            // Special case for UnQueueManager as it is a default plugin
            // and does not require queues, since it does everything immediately
            throw new ServerException('OpportunisticQM cannot work together' .
                                      'with queues that do not implement polling');
        }
        ++$this->handled_items;
        return $qm->poll();
    }

    /**
     * Takes care of running through the queue items, returning when
     * the limits setup in __construct are met.
     *
     * @return true on workqueue finished, false if there are still items in the queue
     */
    public function runQueue()
    {
        while ($this->canContinue()) {
            if (!$this->poll()) {
                // Out of work
                return true;
            }
        }

        if ($this->handled_items > 0) {
            common_debug('Opportunistic queue manager passed execution time/item ' .
                         'handling limit without being out of work.');
            return true;
        } elseif ($this->verbosity > 1) {
            common_debug('Opportunistic queue manager did not have time to start ' .
                         'on this action (max: ' . $this->max_execution_time .
                         ' exceeded: ' . abs(time() - $this->started_at) . ').');
        }

        return false;
    }
}
