<?php

use yii\db\Migration;

class m260814_145233_alter_team_make_generic extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk_team_competition', '{{%team}}');
        $this->dropIndex('idx_team_competition', '{{%team}}');
        $this->dropColumn('{{%team}}', 'competition_id');
        $this->dropColumn('{{%team}}', 'registered_at');
 
        $this->addColumn('{{%team}}', 'cap', $this->integer()->notNull()->defaultValue(10));
        $this->addColumn('{{%team}}', 'avatar_path', $this->string(255)->null());
 
        $this->addColumn('{{%team_membership}}', 'note', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%team}}', 'cap');
        $this->dropColumn('{{%team}}', 'avatar_path');
        $this->dropColumn('{{%team_membership}}', 'note');
        $this->addColumn('{{%team}}', 'competition_id', $this->integer()->notNull());
        $this->addColumn('{{%team}}', 'registered_at', $this->integer()->null());
        $this->createIndex('idx_team_competition', '{{%team}}', 'competition_id');
        $this->addForeignKey('fk_team_competition', '{{%team}}', 'competition_id', '{{%post}}', 'id', 'NO ACTION', 'NO ACTION');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_145233_alter_team_make_generic cannot be reverted.\n";

        return false;
    }
    */
}
