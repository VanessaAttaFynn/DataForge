<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%vote}}`.
 */
class m260813_225203_create_vote_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%vote}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'value' => $this->smallInteger()->notNull()->comment('+1 or -1'),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_vote_post_user', '{{%vote}}', ['post_id', 'user_id'], true);
 
        $this->addForeignKey('fk_vote_post', '{{%vote}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_vote_user', '{{%vote}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_vote_post', '{{%vote}}');
        $this->dropForeignKey('fk_vote_user', '{{%vote}}');
        $this->dropTable('{{%vote}}');
    }
}
