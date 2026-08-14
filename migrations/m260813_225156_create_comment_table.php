<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%comment}}`.
 */
class m260813_225156_create_comment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%comment}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'parent_id' => $this->integer()->null(),
            'body' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_comment_post', '{{%comment}}', 'post_id');
        $this->createIndex('idx_comment_parent', '{{%comment}}', 'parent_id');
 
        $this->addForeignKey('fk_comment_post', '{{%comment}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_comment_user', '{{%comment}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_comment_parent', '{{%comment}}', 'parent_id', '{{%comment}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_comment_post', '{{%comment}}');
        $this->dropForeignKey('fk_comment_user', '{{%comment}}');
        $this->dropForeignKey('fk_comment_parent', '{{%comment}}');
        $this->dropTable('{{%comment}}');
    }
}
