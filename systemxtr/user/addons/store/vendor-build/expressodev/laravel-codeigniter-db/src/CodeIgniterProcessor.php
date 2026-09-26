<?php

namespace Store\Dependency\Illuminate\CodeIgniter;

use Store\Dependency\Illuminate\Database\Query\Builder;
use Store\Dependency\Illuminate\Database\Query\Processors\Processor;
class CodeIgniterProcessor extends Processor
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);
        return $query->getConnection()->insert_id();
    }
}
