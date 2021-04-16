<?php

namespace App\Doctrine\ORM\Hydration;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class ColumnHydrator extends AbstractHydrator
{
    protected function hydrateAllData()
    {
        $result = [];

        while ($data = $this->_stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = array_values($data);
        }

        return $result;
    }
}