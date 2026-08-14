<?php

use yii\db\Migration;

class m260814_154318_create_dataset_meta_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%competition}}', 'dataset_description', $this->text()->null());
        $this->addColumn('{{%competition}}', 'dataset_target_column', $this->string(100)->null());
        $this->addColumn('{{%competition}}', 'dataset_license', $this->string(150)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%competition}}', 'dataset_description');
        $this->dropColumn('{{%competition}}', 'dataset_target_column');
        $this->dropColumn('{{%competition}}', 'dataset_license');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_154318_create_dataset_meta_columns cannot be reverted.\n";

        return false;
    }
    */
}
