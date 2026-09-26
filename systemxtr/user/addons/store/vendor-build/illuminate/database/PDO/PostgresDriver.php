<?php

namespace Store\Dependency\Illuminate\Database\PDO;

use Store\Dependency\Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Store\Dependency\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class PostgresDriver extends AbstractPostgreSQLDriver
{
    use ConnectsToDatabase;
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_pgsql';
    }
}
