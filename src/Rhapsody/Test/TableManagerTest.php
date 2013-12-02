<?php

namespace Rhapsody\Test;

use Rhapsody;

class TableManagerTest extends RhapsodyTestCase
{
    public function testTableCreation()
    {
        // $manager = Rhapsody::getTableManager();
        // $schemaManager = $manager->getSchemaManager();

        // if ($schemaManager->tablesExist('author')) {
        //     $schemaManager->dropTable('author');
        // }

        // $table = $schemaManager->listTableDetails('book');

        // // var_dump($table);

        // $authorTable = new \Doctrine\DBAL\Schema\Table('author');
        // $authorTable->addColumn('id', 'integer', array('autoincrement' => true));
        // $authorTable->addColumn('name', 'string', array('length' => 255, 'not_null' => true));
        // $authorTable->setPrimaryKey(array('id'));

        // $schemaManager->createTable($authorTable);
    }
}
