<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%competition}}`.
 */
class m260813_225132_create_competition_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%competition}}', [
            'post_id' => $this->integer()->notNull(),
            'metric' => $this->string(20)->notNull()->comment('accuracy, rmse'),
            'answer_key_path' => $this->string(255)->null(),
            'accepts' => $this->string(20)->notNull()->defaultValue('both')->comment('individual, team, both'),
            'team_size_limit' => $this->integer()->null(),
            'submission_cap_per_day' => $this->integer()->notNull()->defaultValue(5),
            'reward_type' => $this->string(20)->notNull()->defaultValue('none')->comment('none, cash, prize, certificate, points, other'),
            'reward_details' => $this->text()->null(),
            'registration_deadline' => $this->dateTime()->null(),
            'deadline' => $this->dateTime()->notNull(),
        ]);
 
        $this->addPrimaryKey('pk_competition', '{{%competition}}', 'post_id');
        $this->addForeignKey('fk_competition_post', '{{%competition}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_competition_post', '{{%competition}}');
        $this->dropTable('{{%competition}}');
    }
}
