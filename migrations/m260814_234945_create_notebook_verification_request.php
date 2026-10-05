<?php

use yii\db\Migration;

class m260814_234945_create_notebook_verification_request extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%notebook}}', 'verification_requested_at', $this->integer()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%notebook}}', 'verification_requested_at');
    }
}
