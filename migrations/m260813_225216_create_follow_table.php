<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%follow}}`.
 */
class m260813_225216_create_follow_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%follow}}', [
            'id' => $this->primaryKey(),
            'follower_id' => $this->integer()->notNull(),
            'followed_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_follow_pair', '{{%follow}}', ['follower_id', 'followed_id'], true);
 
        $this->addForeignKey('fk_follow_follower', '{{%follow}}', 'follower_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_follow_followed', '{{%follow}}', 'followed_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_follow_follower', '{{%follow}}');
        $this->dropForeignKey('fk_follow_followed', '{{%follow}}');
        $this->dropTable('{{%follow}}');
    }
}
