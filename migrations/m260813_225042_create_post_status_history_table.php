<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post_status_history}}`.
 */
class m260813_225042_create_post_status_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post_status_history}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'changed_by' => $this->integer()->notNull(),
            'old_status' => $this->string(20)->null(),
            'new_status' => $this->string(20)->notNull(),
            'note' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_psh_post', '{{%post_status_history}}', 'post_id');
 
        $this->addForeignKey('fk_psh_post', '{{%post_status_history}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_psh_user', '{{%post_status_history}}', 'changed_by', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_psh_post', '{{%post_status_history}}');
        $this->dropForeignKey('fk_psh_user', '{{%post_status_history}}');
        $this->dropTable('{{%post_status_history}}');
    }
}
