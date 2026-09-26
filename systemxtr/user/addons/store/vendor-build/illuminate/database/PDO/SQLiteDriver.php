<?php

namespace Store\Dependency\Illuminate\Database\PDO;

use Store\Dependency\Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Store\Dependency\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class SQLiteDriver extends AbstractSQLiteDriver
{
    use ConnectsToDatabase;
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_sqlite';
    }
}
