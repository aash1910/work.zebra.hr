<?php

namespace Store\Dependency\Illuminate\CodeIgniter;

use Store\Dependency\Illuminate\Database\ConnectionResolverInterface;
use Store\Dependency\Illuminate\Database\Connection;
use RuntimeException;
class CodeIgniterConnectionResolver implements ConnectionResolverInterface
{
    protected $ci;
    protected $connection;
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        if (null !== $name) {
            throw new \InvalidArgumentException("Named connections are not supported.");
        }
        if (null === $this->connection) {
            $this->connection = new CodeIgniterConnection($this->ci);
        }
        return $this->connection;
    }
    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return 'codeigniter';
    }
    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        throw new RuntimeException("Setting default connection is not supported.");
    }
}
