<?php

use yii\db\Migration;

class m260815_021836_create_deadline_nullable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%competition}}', 'deadline', $this->dateTime()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%competition}}', 'deadline', $this->dateTime()->notNull());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260815_021836_create_deadline_nullable cannot be reverted.\n";

        return false;
    }
    */
}
