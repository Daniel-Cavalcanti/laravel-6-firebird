<?php

namespace Firebird;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class FirebirdQueryGrammar extends Grammar
{

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = array(
        'aggregate',
        'limit',
        'offset',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders'
    );

    /**
     * Compile the "select *" portion of the query.
     * As Firebird adds the "limit" and "offset" after the "select", this must not work this way.
     *
     * @param Builder $query
     * @param array   $columns
     * @return string
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (!is_null($query->aggregate)) return;
        $select = '';
        if (count($columns) > 0 && $query->limit == null && $query->aggregate == null) {
            $select = $query->distinct ? 'select distinct ' : 'select ';
        }

        return $select . $this->columnize($columns);
    }

    /**
     * Compile a select query into SQL.
     *
     * @param Builder $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (is_null($query->columns)) $query->columns = array('*');

        return trim($this->concatenate($this->compileComponents($query)));
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $query
     * @param array   $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as aggregate';
    }

    /**
     * Compile first instead of limit
     *
     * @param Builder $query
     * @param int     $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return 'select first ' . (int)$limit;
    }

    /**
     * Compile skip instead of offset
     *
     * @param Builder $query
     * @param int     $limit
     * @return string
     */
    protected function compileOffset(Builder $query, $limit)
    {
        return 'skip ' . (int)$limit;
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (empty($values)) {
            return "insert into {$table} default values";
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        $columns = $this->columnize(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same number of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = collect($values)->map(function ($record) {
            return '('.$this->parameterize($record).')';
        })->implode(', ');

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $column)
    {
        $query_returning = $this->compileInsert($query, $values) . ' returning ' . $column;
        return $query_returning;
    }
}
