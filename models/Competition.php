<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $post_id
 * @property string $metric
 * @property string|null $answer_key_path
 * @property string $accepts
 * @property int|null $team_size_limit
 * @property int $submission_cap_per_day
 * @property string $reward_type
 * @property string|null $reward_details
 * @property string|null $registration_deadline
 * @property string $deadline
 */
class Competition extends ActiveRecord
{
    const METRIC_ACCURACY = 'accuracy';
    const METRIC_RMSE = 'rmse';

    const ACCEPTS_INDIVIDUAL = 'individual';
    const ACCEPTS_TEAM = 'team';
    const ACCEPTS_BOTH = 'both';

    const REWARD_NONE = 'none';
    const REWARD_CASH = 'cash';
    const REWARD_PRIZE = 'prize';
    const REWARD_CERTIFICATE = 'certificate';
    const REWARD_POINTS = 'points';
    const REWARD_OTHER = 'other';

    // Shown as a hint in the form, not enforced — the creator sets the real cap.
    const RECOMMENDED_SUBMISSION_CAP_MIN = 5;
    const RECOMMENDED_SUBMISSION_CAP_MAX = 10;

    public static function tableName()
    {
        return '{{%competition}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'metric', 'accepts', 'submission_cap_per_day', 'reward_type', 'deadline'], 'required'],
            [['post_id', 'team_size_limit', 'submission_cap_per_day'], 'integer'],
            [['reward_details'], 'string'],
            [['registration_deadline', 'deadline'], 'safe'],
            [['metric'], 'in', 'range' => [self::METRIC_ACCURACY, self::METRIC_RMSE]],
            [['accepts'], 'in', 'range' => [self::ACCEPTS_INDIVIDUAL, self::ACCEPTS_TEAM, self::ACCEPTS_BOTH]],
            [['reward_type'], 'in', 'range' => [
                self::REWARD_NONE, self::REWARD_CASH, self::REWARD_PRIZE,
                self::REWARD_CERTIFICATE, self::REWARD_POINTS, self::REWARD_OTHER,
            ]],
            [['answer_key_path'], 'string', 'max' => 255],
            [['dataset_file_path'], 'string', 'max' => 255],
            [['dataset_description'], 'string'],
            [['dataset_target_column'], 'string', 'max' => 100],
            [['dataset_license'], 'string', 'max' => 150],
            [['dataset_rows', 'dataset_columns', 'dataset_sheets'], 'integer'],
            ['submission_cap_per_day', 'default', 'value' => self::RECOMMENDED_SUBMISSION_CAP_MIN],
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    /** Sort direction the leaderboard should use for this competition's metric. */
    public function scoreSortDirection(): string
    {
        return $this->metric === self::METRIC_RMSE ? SORT_ASC : SORT_DESC;
    }
}