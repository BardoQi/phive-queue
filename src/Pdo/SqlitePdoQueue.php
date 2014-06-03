<?php

namespace Phive\Queue\Pdo;

use Phive\Queue\NoItemAvailableException;

class SqlitePdoQueue extends PdoQueue
{
    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        $sql = sprintf(
            'SELECT id, item FROM %s WHERE eta <= %d ORDER BY eta LIMIT 1',
            $this->tableName,
            time()
        );

        $this->pdo->exec('BEGIN IMMEDIATE');

        try {
            $stmt = $this->pdo->query($sql);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($row) {
                $sql = sprintf('DELETE FROM %s WHERE id = %d', $this->tableName, $row['id']);
                $this->pdo->exec($sql);
            }

            $this->pdo->exec('COMMIT');
        } catch (\Exception $e) {
            $this->pdo->exec('ROLLBACK');
            throw $e;
        }

        if ($row) {
            return $row['item'];
        }

        throw new NoItemAvailableException($this);
    }

    public function getSupportedDrivers()
    {
        return ['sqlite'];
    }
}