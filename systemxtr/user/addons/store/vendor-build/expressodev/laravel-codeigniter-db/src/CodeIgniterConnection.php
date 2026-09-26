<?php

namespace Store\Dependency\Illuminate\CodeIgniter;

use PDO;
use Store\Dependency\Illuminate\Database\Connection;
use Store\Dependency\Illuminate\Database\Query\Builder;
use Store\Dependency\Illuminate\Database\Schema\Builder as SchemaBuilder;
class CodeIgniterConnection extends Connection
{
    public $ci;
    /**
     * Create a new database connection instance.
     *
     * @param  object $ci
     * @return void
     */
    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->tablePrefix = $this->ci->db->dbprefix;
        $this->useDefaultQueryGrammar();
        $this->useDefaultPostProcessor();
    }
    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        $driver = $this->ci->db->dbdriver;
        switch ($driver) {
            case 'mysql':
            case 'mysqli':
                if (class_exists('Store\Dependency\Illuminate\Database\Query\Grammars\MySqlGrammar')) {
                    return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Query\Grammars\MySqlGrammar());
                }
                return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Schema\Grammars\MySqlGrammar());
            case 'postgresql':
            case 'pgsql':
                return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Query\Grammars\PostgresGrammar());
            case 'sqlite':
                return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Query\Grammars\SQLiteGrammar());
        }
        throw new \InvalidArgumentException("Unknown CI database driver '{$driver}'");
    }
    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        $driver = $this->ci->db->dbdriver;
        switch ($driver) {
            case 'mysql':
            case 'mysqli':
                return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Schema\Grammars\MySqlGrammar());
            case 'postgresql':
            case 'pgsql':
                return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Schema\Grammars\PostgresGrammar());
            case 'sqlite':
                return $this->withTablePrefix(new \Store\Dependency\Illuminate\Database\Schema\Grammars\SQLiteGrammar());
        }
        throw new \InvalidArgumentException("Unsupported CodeIgniter database driver '{$driver}'");
    }
    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new CodeIgniterProcessor();
    }
    /**
     * Get the currently used PDO connection.
     *
     * @return PDO
     */
    public function getPdo()
    {
        if (method_exists($this->ci->db, 'getPdo')) {
            return $this->ci->db->getPdo();
        }
        throw new \BadMethodCallException('PDO is not supported by CodeIgniter database driver');
    }
    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    public function reconnectIfMissingConnection()
    {
        //Reconnection is not supported by CodeIgniter database driver, do nothing
    }
    /**
     * Run a select statement against the database.
     *
     * @param  string $query
     * @param  array $bindings
     * @param bool $useReadPdo
     *
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = \true)
    {
        $self = $this;
        return $this->run($query, $bindings, function ($query, $bindings) use ($self) {
            if ($self->pretending()) {
                return [];
            }
            // pass query to CodeIgniter database layer
            $bindings = $self->prepareBindings($bindings);
            return $self->fetchResult($self->ci->db->query($query, $bindings));
        });
    }
    /**
     * Fetch a CodeIgniter result set as an array or object, emulating current PDO fetch mode
     *
     * @param  object $result
     * @return mixed
     */
    public function fetchResult($result)
    {
        $fetchMode = $this->fetchMode;
        switch ($fetchMode) {
            case PDO::FETCH_ASSOC:
                return $result->result_array();
            case PDO::FETCH_OBJ:
                return $result->result();
        }
        throw new \BadMethodCallException("Unsupported fetch mode '{$fetchMode}'.");
    }
    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        $self = $this;
        return $this->run($query, $bindings, function ($query, $bindings) use ($self) {
            if ($self->pretending()) {
                return \true;
            }
            // pass query to CodeIgniter database layer
            $bindings = $self->prepareBindings($bindings);
            return (bool) $self->ci->db->query($query, $bindings);
        });
    }
    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        $self = $this;
        return $this->run($query, $bindings, function ($query, $bindings) use ($self) {
            if ($self->pretending()) {
                return 0;
            }
            // pass query to CodeIgniter database layer
            $bindings = $self->prepareBindings($bindings);
            $self->ci->db->query($query, $bindings);
            // return number of rows affected
            return $self->ci->db->affected_rows();
        });
    }
    /**
     * Get the last insert id from
     */
    public function lastInsertId()
    {
        return $this->ci->db->insert_id();
    }
    /**
     * Get the last insert id from CodeIgniter
     */
    public function insert_id()
    {
        return $this->ci->db->insert_id();
    }
    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }
        return new SchemaBuilder($this);
    }
    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return 'codeigniter';
    }
    /**
     * Get an option from the configuration options.
     *
     * @param  string|null  $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $option ? null : [];
    }
}
