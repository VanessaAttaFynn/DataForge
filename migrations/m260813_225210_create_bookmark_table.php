<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%bookmark}}`.
 */
class m260813_225210_create_bookmark_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%bookmark}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'post_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_bookmark_user_post', '{{%bookmark}}', ['user_id', 'post_id'], true);
 
        $this->addForeignKey('fk_bookmark_user', '{{%bookmark}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_bookmark_post', '{{%bookmark}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_bookmark_user', '{{%bookmark}}');
        $this->dropForeignKey('fk_bookmark_post', '{{%bookmark}}');
        $this->dropTable('{{%bookmark}}');
    }
}
