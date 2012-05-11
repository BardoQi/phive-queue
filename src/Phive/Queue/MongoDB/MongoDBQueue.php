<?php

namespace Phive\Queue\MongoDB;

use Phive\Queue\AdvancedQueueInterface;
use Phive\Queue\AbstractQueue;
use Phive\CallbackIterator;

class MongoDBQueue extends AbstractQueue implements AdvancedQueueInterface
{
    /**
     * @var \MongoCollection
     */
    protected $collection;

    /**
     * Constructor.
     *
     * @param \MongoCollection $collection
     */
    public function __construct(\MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Retrieves \MongoCollection instance.
     *
     * @return \MongoCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @see QueueInterface::push()
     */
    public function push($item, $eta = null)
    {
        $eta = $eta ? $this->normalizeEta($eta) : time();

        $data = array(
            'eta'  => $eta,
            'item' => $item,
        );

        $result = $this->collection->insert($data, array('safe' => true));
        if (!$result['ok']) {
            throw new \RuntimeException($result['errmsg']);
        }
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        $command = array(
            'findandmodify' => $this->collection->getName(),
            'remove'        => 1,
            'fields'        => array('item' => 1),
            'query'         => array('eta' => array('$lte' => time())),
            'sort'          => array('eta' => 1),
        );

        $result = $this->collection->db->command($command);
        if (!$result['ok']) {
            throw new \RuntimeException($result['errmsg']);
        }

        $data = $result['value'];

        return $data ? $data['item'] : false;
    }

    /**
     * @see AdvancedQueueInterface::peek()
     */
    public function peek($limit = 1, $skip = 0)
    {
        if ($limit <= 0) {
            // Parameter limit must either be -1 or a value greater than or equal 0
            throw new \OutOfRangeException('Parameter limit must be greater than 0.');
        }
        if ($skip < 0) {
            throw new \OutOfRangeException('Parameter skip must be greater than or equal 0.');
        }

        $cursor = $this->collection->find(array('eta' => array('$lte' => time())));
        $cursor->sort(array('eta' => 1));

        if ($limit) {
            $cursor->limit($limit);
        }

        if ($skip) {
            $cursor->skip($skip);
        }

        return new CallbackIterator($cursor, function ($data) {
            return $data['item'];
        });
    }

    /**
     * @see AdvancedQueueInterface::count()
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * @see AdvancedQueueInterface::clear()
     */
    public function clear()
    {
        $this->collection->remove(array(), array('safe' => true));
    }
}