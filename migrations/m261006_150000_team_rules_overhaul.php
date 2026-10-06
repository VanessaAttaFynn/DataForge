<?php

use yii\db\Migration;

/**
 * Team rules overhaul:
 *  - competition.team_size_min  (owner-set minimum, alongside team_size_limit as the maximum)
 *  - team.archived_at           (last member left — team kept read-only instead of deleted)
 *  - team_competition_member    (the line-up a team entered a competition with; only these
 *                                people count for that competition)
 *
 * Existing team registrations get a line-up backfilled from the members who had
 * joined the team by the time it registered.
 */
class m261006_150000_team_rules_overhaul extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%competition}}', 'team_size_min', $this->integer()->null());
        $this->addColumn('{{%team}}', 'archived_at', $this->integer()->null());

        $this->createTable('{{%team_competition_member}}', [
            'id' => $this->primaryKey(),
            'registration_id' => $this->integer()->notNull()->comment('FK to team_competition_registration.id'),
            'user_id' => $this->integer()->notNull(),
            'added_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_tcm_registration_user', '{{%team_competition_member}}', ['registration_id', 'user_id'], true);
        $this->createIndex('idx_tcm_user', '{{%team_competition_member}}', 'user_id');
        $this->addForeignKey('fk_tcm_registration', '{{%team_competition_member}}', 'registration_id', '{{%team_competition_registration}}', 'id', 'NO ACTION', 'NO ACTION');
        $this->addForeignKey('fk_tcm_user', '{{%team_competition_member}}', 'user_id', '{{%user}}', 'id', 'NO ACTION', 'NO ACTION');

        // Backfill: line-up = members who had consented by the time the team registered.
        $this->execute("
            INSERT INTO {{%team_competition_member}} (registration_id, user_id, added_at)
            SELECT r.id, m.user_id, r.registered_at
            FROM {{%team_competition_registration}} r
            INNER JOIN {{%team_membership}} m ON m.team_id = r.team_id
            WHERE m.invite_status = 'consented'
              AND (m.responded_at IS NULL OR m.responded_at <= r.registered_at)
        ");

        // Old pending rows can't be told apart (invites and join requests were both
        // saved as 'invited'), so close them. People re-invite / re-request under the new rules.
        $this->update('{{%team_membership}}',
            ['invite_status' => 'declined', 'responded_at' => time()],
            ['invite_status' => 'invited']
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_tcm_registration', '{{%team_competition_member}}');
        $this->dropForeignKey('fk_tcm_user', '{{%team_competition_member}}');
        $this->dropTable('{{%team_competition_member}}');
        $this->dropColumn('{{%team}}', 'archived_at');
        $this->dropColumn('{{%competition}}', 'team_size_min');
    }
}
