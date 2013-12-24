<?php

namespace Phive\Queue\Queue;

use Phive\Queue\Exception\NoItemException;
use Phive\Queue\QueueUtils;

class InMemoryQueue implements QueueInterface
{
    /**
     * @var \SplPriorityQueue
     */
    private $queue;

    /**
     * @var int
     */
    private $queueOrder;

    public function __construct()
    {
        $this->queue = new \SplPriorityQueue();
        $this->queueOrder = PHP_INT_MAX;
    }

    /**
     * {@inheritdoc}
     */
    public function push($item, $eta = null)
    {
        $eta = QueueUtils::normalizeEta($eta);
        $this->queue->insert($item, array(-$eta, $this->queueOrder--));
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        if (!$this->queue->isEmpty()) {
            $this->queue->setExtractFlags(\SplPriorityQueue::EXTR_PRIORITY);
            $priority = $this->queue->top();

            if (time() + $priority[0] >= 0) {
                $this->queue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);

                return $this->queue->extract();
            }
        }

        throw new NoItemException();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->queue->count();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->queue = new \SplPriorityQueue();
        $this->queueOrder = PHP_INT_MAX;
    }
}