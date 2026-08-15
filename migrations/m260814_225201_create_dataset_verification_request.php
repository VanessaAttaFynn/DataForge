<?php

use yii\db\Migration;

class m260814_225201_create_dataset_verification_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%dataset}}', 'verification_requested_at', $this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%dataset}}', 'verification_requested_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_225201_create_dataset_verification_request cannot be reverted.\n";

        return false;
    }
    */
}
