<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notebook}}`.
 */
class m260813_225125_create_notebook_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notebook}}', [
            'post_id' => $this->integer()->notNull(),
            'code_path' => $this->string(255)->null(),
            'language' => $this->string(30)->null(),
        ]);
 
        $this->addPrimaryKey('pk_notebook', '{{%notebook}}', 'post_id');
        $this->addForeignKey('fk_notebook_post', '{{%notebook}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_notebook_post', '{{%notebook}}');
        $this->dropTable('{{%notebook}}');
    }
}
