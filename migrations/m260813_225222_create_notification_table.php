<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notification}}`.
 */
class m260813_225222_create_notification_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notification}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string(30)->notNull(),
            'message' => $this->string(255)->notNull(),
            'link' => $this->string(255)->null(),
            'is_read' => $this->boolean()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_notification_user', '{{%notification}}', ['user_id', 'is_read']);
 
        $this->addForeignKey('fk_notification_user', '{{%notification}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_notification_user', '{{%notification}}');
        $this->dropTable('{{%notification}}');
    }
}
