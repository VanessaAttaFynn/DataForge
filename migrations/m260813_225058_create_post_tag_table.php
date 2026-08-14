<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post_tag}}`.
 */
class m260813_225058_create_post_tag_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post_tag}}', [
            'post_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
        ]);
 
        $this->addPrimaryKey('pk_post_tag', '{{%post_tag}}', ['post_id', 'tag_id']);
 
        $this->addForeignKey('fk_post_tag_post', '{{%post_tag}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_post_tag_tag', '{{%post_tag}}', 'tag_id', '{{%tag}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_post_tag_post', '{{%post_tag}}');
        $this->dropForeignKey('fk_post_tag_tag', '{{%post_tag}}');
        $this->dropTable('{{%post_tag}}');
    }
}
