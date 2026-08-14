<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post}}`.
 */
class m260813_225033_create_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string(20)->notNull()->comment('dataset, notebook, competition, hackathon, discussion'),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'body' => $this->text(),
            'cover_image_path' => $this->string(255)->null(),
            'author_id' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('draft')->comment('draft, pending, published, rejected'),
            'verified' => $this->boolean()->notNull()->defaultValue(0)->comment('dataset-only trust flag'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
 
        $this->createIndex('idx_post_slug', '{{%post}}', 'slug', true);
        $this->createIndex('idx_post_type_status', '{{%post}}', ['type', 'status']);
        $this->createIndex('idx_post_author', '{{%post}}', 'author_id');
 
        $this->addForeignKey('fk_post_author', '{{%post}}', 'author_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_post_author', '{{%post}}');
        $this->dropTable('{{%post}}');
    }
}
