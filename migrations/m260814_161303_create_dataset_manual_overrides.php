<?php

use yii\db\Migration;

class m260814_161303_create_dataset_manual_overrides extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%competition}}', 'dataset_rows', $this->integer()->null());
        $this->addColumn('{{%competition}}', 'dataset_columns', $this->integer()->null());
        $this->addColumn('{{%competition}}', 'dataset_sheets', $this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%competition}}', 'dataset_rows');
        $this->dropColumn('{{%competition}}', 'dataset_columns');
        $this->dropColumn('{{%competition}}', 'dataset_sheets');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_161303_create_dataset_manual_overrides cannot be reverted.\n";

        return false;
    }
    */
}
