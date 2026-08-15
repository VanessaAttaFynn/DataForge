<?php

use yii\db\Migration;

class m260814_212325_create_dataset_extra_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%dataset}}', 'topic', $this->string(100)->null());
        $this->addColumn('{{%dataset}}', 'linked_post_id', $this->integer()->null());
        $this->addColumn('{{%dataset}}', 'description', $this->text()->null());
        $this->addColumn('{{%dataset}}', 'target_column', $this->string(100)->null());
        $this->addColumn('{{%dataset}}', 'summary_stats', $this->text()->null());
 
        $this->addForeignKey('fk_dataset_linked_post', '{{%dataset}}', 'linked_post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_dataset_linked_post', '{{%dataset}}');
        $this->dropColumn('{{%dataset}}', 'topic');
        $this->dropColumn('{{%dataset}}', 'linked_post_id');
        $this->dropColumn('{{%dataset}}', 'description');
        $this->dropColumn('{{%dataset}}', 'target_column');
        $this->dropColumn('{{%dataset}}', 'summary_stats');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_212325_create_dataset_extra_columns cannot be reverted.\n";

        return false;
    }
    */
}
