<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%team_membership}}`.
 */
class m260813_225145_create_team_membership_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%team_membership}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'invite_status' => $this->string(20)->notNull()->defaultValue('invited')->comment('invited, consented, declined, left'),
            'conflict_status' => $this->string(20)->null()->comment('clear, conflicted, removed'),
            'invited_at' => $this->integer()->notNull(),
            'responded_at' => $this->integer()->null(),
        ]);
 
        $this->createIndex('idx_membership_team_user', '{{%team_membership}}', ['team_id', 'user_id'], true);
 
        $this->addForeignKey('fk_membership_team', '{{%team_membership}}', 'team_id', '{{%team}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_membership_user', '{{%team_membership}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_membership_team', '{{%team_membership}}');
        $this->dropForeignKey('fk_membership_user', '{{%team_membership}}');
        $this->dropTable('{{%team_membership}}');
    }
}
