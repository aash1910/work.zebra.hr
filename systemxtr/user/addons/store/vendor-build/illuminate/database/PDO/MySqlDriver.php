<?php

namespace Store\Dependency\Illuminate\Database\PDO;

use Store\Dependency\Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Store\Dependency\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_mysql';
    }
}
