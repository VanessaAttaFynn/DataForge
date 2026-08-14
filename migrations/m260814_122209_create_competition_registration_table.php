<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%competition_registration}}`.
 */
class m260814_122209_create_competition_registration_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%competition_registration}}', [
            'id' => $this->primaryKey(),
            'competition_id' => $this->integer()->notNull()->comment('FK to post.id'),
            'user_id' => $this->integer()->notNull(),
            'registered_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_reg_competition_user', '{{%competition_registration}}', ['competition_id', 'user_id'], true);
 
        $this->addForeignKey('fk_reg_competition', '{{%competition_registration}}', 'competition_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_reg_user', '{{%competition_registration}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_reg_competition', '{{%competition_registration}}');
        $this->dropForeignKey('fk_reg_user', '{{%competition_registration}}');
        $this->dropTable('{{%competition_registration}}');
    }
}
