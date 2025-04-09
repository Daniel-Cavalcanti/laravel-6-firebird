<?php

namespace Firebird;

use Illuminate\Database\Query\Builder;

class FirebirdProcessor extends \Illuminate\Database\Query\Processors\Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $column = null)
    {
        $connection = $query->getConnection();

        $connection->recordsHaveBeenModified();

        $result = $connection->selectFromWriteConnection($sql, $values)[0];

        //$sequence = $sequence ?: 'id';

        $id = is_object($result) ? $result->{$column} : $result[$column];

        return is_numeric($id) ? (int) $id : $id;
    }
}