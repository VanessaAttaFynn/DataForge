<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'sqlsrv:Server=localhost;Database=DataForge_DB',
    'username' => 'dataforger',
    'password' => 'password@123',
    'charset' => 'utf8',
    'attributes' => [\PDO::ATTR_EMULATE_PREPARES => true],

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
