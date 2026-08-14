<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%team}}`.
 */
class m260813_225138_create_team_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%team}}', [
            'id' => $this->primaryKey(),
            'competition_id' => $this->integer()->notNull()->comment('FK to post.id where type=competition/hackathon'),
            'name' => $this->string(100)->notNull(),
            'owner_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_team_competition', '{{%team}}', 'competition_id');
 
        $this->addForeignKey('fk_team_competition', '{{%team}}', 'competition_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_team_owner', '{{%team}}', 'owner_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%team}}');
    }
}
