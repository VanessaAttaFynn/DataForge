<?php

use yii\db\Migration;

class m260814_204236_create_notebook_linked_post_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%notebook}}', 'linked_post_id', $this->integer()->null());
        $this->addForeignKey('fk_notebook_linked_post', '{{%notebook}}', 'linked_post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_notebook_linked_post', '{{%notebook}}');
        $this->dropColumn('{{%notebook}}', 'linked_post_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_204236_create_notebook_linked_post_column cannot be reverted.\n";

        return false;
    }
    */
}
