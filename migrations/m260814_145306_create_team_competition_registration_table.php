<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%team_competition_registration}}`.
 */
class m260814_145306_create_team_competition_registration_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%team_competition_registration}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'competition_id' => $this->integer()->notNull()->comment('FK to post.id'),
            'registered_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_tcr_team_competition', '{{%team_competition_registration}}', ['team_id', 'competition_id'], true);
 
        $this->addForeignKey('fk_tcr_team', '{{%team_competition_registration}}', 'team_id', '{{%team}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_tcr_competition', '{{%team_competition_registration}}', 'competition_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_tcr_team', '{{%team_competition_registration}}');
        $this->dropForeignKey('fk_tcr_competition', '{{%team_competition_registration}}');
        $this->dropTable('{{%team_competition_registration}}');
    }
}
