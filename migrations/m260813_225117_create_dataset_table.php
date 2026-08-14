<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%dataset}}`.
 */
class m260813_225117_create_dataset_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%dataset}}', [
            'post_id' => $this->integer()->notNull(),
            'file_path' => $this->string(255)->notNull(),
            'file_size' => $this->integer()->notNull()->comment('bytes'),
            'row_count' => $this->integer()->null(),
            'column_count' => $this->integer()->null(),
            'license' => $this->string(100)->null(),
            'download_count' => $this->integer()->notNull()->defaultValue(0),
        ]);
 
        $this->addPrimaryKey('pk_dataset', '{{%dataset}}', 'post_id');
        $this->addForeignKey('fk_dataset_post', '{{%dataset}}', 'post_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_dataset_post', '{{%dataset}}');
        $this->dropTable('{{%dataset}}');
    }
}
