<?php

use yii\db\Migration;

class m260814_132103_create_team_registration_and_dataset_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%team}}', 'registered_at', $this->integer()->null());
        $this->addColumn('{{%competition}}', 'dataset_file_path', $this->string(255)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%team}}', 'registered_at');
        $this->dropColumn('{{%competition}}', 'dataset_file_path');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_132103_create_team_registration_and_dataset_columns cannot be reverted.\n";

        return false;
    }
    */
}
