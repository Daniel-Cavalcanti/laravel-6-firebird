<?php

namespace Firebird;

use Illuminate\Database\Query\Builder;

class FirebirdQueryBuilder extends Builder
{
    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        return parent::count() > 0;
    }

    /**
     * Implements lastInsertId.
     *
     * @return bool
     */
    public function insertGetId(array $values, $column = null)
    {
        //$this->applyBeforeQueryCallbacks();
        if ($column === null) {
            dd($column);
            throw new Error("Firebird needs a column when using insertGetId");
        }

        $sql = $this->grammar->compileInsertGetId($this, $values, $column);

        $values = $this->cleanBindings($values);

        $firebird_processor = new FirebirdProcessor();

        return $firebird_processor->processInsertGetId($this, $sql, $values, $column);
    }
}
