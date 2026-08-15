<?php

use yii\db\Migration;

class m260814_194416_create_notebook_extra_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%notebook}}', 'notebook_file_path', $this->string(255)->null());
        $this->addColumn('{{%notebook}}', 'topic', $this->string(100)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%notebook}}', 'notebook_file_path');
        $this->dropColumn('{{%notebook}}', 'topic');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_194416_create_notebook_extra_columns cannot be reverted.\n";

        return false;
    }
    */
}
