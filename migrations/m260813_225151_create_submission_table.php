<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%submission}}`.
 */
class m260813_225151_create_submission_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%submission}}', [
            'id' => $this->primaryKey(),
            'competition_id' => $this->integer()->notNull(),
            'participant_type' => $this->string(20)->notNull()->comment('individual, team'),
            'user_id' => $this->integer()->null(),
            'team_id' => $this->integer()->null(),
            'file_path' => $this->string(255)->notNull(),
            'score' => $this->decimal(10, 4)->null(),
            'submitted_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_submission_competition', '{{%submission}}', 'competition_id');
        $this->createIndex('idx_submission_leaderboard', '{{%submission}}', ['competition_id', 'score']);
 
        $this->addForeignKey('fk_submission_competition', '{{%submission}}', 'competition_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_submission_user', '{{%submission}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_submission_team', '{{%submission}}', 'team_id', '{{%team}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_submission_competition', '{{%submission}}');
        $this->dropForeignKey('fk_submission_user', '{{%submission}}');
        $this->dropForeignKey('fk_submission_team', '{{%submission}}');
        $this->dropTable('{{%submission}}');
    }
}
