<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $post_id
 * @property string $metric
 * @property string|null $answer_key_path
 * @property string $accepts
 * @property int|null $team_size_min
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

    // In "register first, submit later" mode, submissions only open when registration
    // closes — so the final deadline must leave at least this much time to submit.
    const MIN_SUBMISSION_WINDOW = 86400; // 1 day

    public static function tableName()
    {
        return '{{%competition}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'metric', 'accepts', 'submission_cap_per_day', 'reward_type'], 'required'],
            [['post_id', 'team_size_min', 'team_size_limit', 'submission_cap_per_day'], 'integer'],
            [['team_size_min', 'team_size_limit'], 'integer', 'min' => 1, 'max' => Team::DEFAULT_CAP,
                'tooBig' => '{attribute} can\'t be more than ' . Team::DEFAULT_CAP . ' — teams hold at most ' . Team::DEFAULT_CAP . ' members.'],
            [['team_size_min'], 'validateTeamSizes'],
            [['reward_details'], 'string'],
            [['registration_deadline', 'deadline'], 'safe'],
            [['registration_deadline'], 'validateRegistrationBeforeDeadline'],
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

    // ---------- Lifecycle ----------
    // registration_deadline being SET is what turns on sequential mode:
    // register-then-submit, gated at that date. Left blank = fully
    // concurrent, today's original behavior, all the way to the final deadline.

    /** A competition with no final deadline never "ends" — stays open indefinitely. */
    public function hasEnded(): bool
    {
        return !empty($this->deadline) && strtotime($this->deadline) <= time();
    }

    public function registrationDeadlinePassed(): bool
    {
        return !empty($this->registration_deadline) && strtotime($this->registration_deadline) <= time();
    }

    /** Individual registration / team creation-registration allowed. */
    public function isRegistrationOpen(): bool
    {
        if ($this->hasEnded()) {
            return false;
        }
        if (empty($this->registration_deadline)) {
            return true; // no gate set — always open until the final deadline
        }
        return !$this->registrationDeadlinePassed();
    }

    /** Submissions allowed. */
    public function isSubmissionOpen(): bool
    {
        if ($this->hasEnded()) {
            return false;
        }
        if (empty($this->registration_deadline)) {
            return true; // concurrent mode — no gate between registering and submitting
        }
        return $this->registrationDeadlinePassed(); // sequential mode — only after registration closes
    }

    /** For messaging on the competition page. */
    /** Stable key for filtering — 'registration' / 'ongoing' / 'completed'. Use phaseLabel() for display text. */
    public function phaseKey(): string
    {
        if ($this->hasEnded()) {
            return 'completed';
        }
        if (!empty($this->registration_deadline) && !$this->registrationDeadlinePassed()) {
            return 'registration';
        }
        return 'ongoing';
    }

    public function phaseLabel(): string
    {
        if ($this->hasEnded()) {
            return 'Completed';
        }
        if (!empty($this->registration_deadline) && !$this->registrationDeadlinePassed()) {
            return 'Registration open — submissions start ' . date('M j, Y', strtotime($this->registration_deadline));
        }
        return 'Ongoing';
    }

    public function deadlineLabel(): string
    {
        return empty($this->deadline) ? 'None' : date('Y-m-d H:i', strtotime($this->deadline));
    }

    public function validateTeamSizes(): void
    {
        if ($this->team_size_min !== null && $this->team_size_min !== '' && $this->team_size_limit !== null && $this->team_size_limit !== ''
            && (int) $this->team_size_min > (int) $this->team_size_limit) {
            $this->addError('team_size_min', 'Minimum team size cannot be bigger than the maximum.');
        }
    }

    // ---------- Team size rules ----------

    /** Error message if a line-up of $size people isn't allowed, null if it's fine. */
    public function teamSizeError(int $size): ?string
    {
        if ($this->team_size_min !== null && $size < (int) $this->team_size_min) {
            return "This competition needs at least {$this->team_size_min} people per team (this line-up has {$size}).";
        }
        if ($this->team_size_limit !== null && $size > (int) $this->team_size_limit) {
            return "This competition allows at most {$this->team_size_limit} people per team (this line-up has {$size}).";
        }
        return null;
    }

    public function teamSizeLabel(): ?string
    {
        $min = $this->team_size_min;
        $max = $this->team_size_limit;
        if ($min === null && $max === null) return null;
        if ($min !== null && $max !== null) return $min == $max ? "exactly {$min}" : "{$min}–{$max} people";
        return $min !== null ? "at least {$min}" : "up to {$max}";
    }

    /** True once anyone (individual or team) has registered — key rules get locked from then on. */
    public function hasEntries(): bool
    {
        return CompetitionRegistration::find()->where(['competition_id' => $this->post_id])->exists()
            || TeamCompetitionRegistration::find()->where(['competition_id' => $this->post_id])->exists();
    }

    public function validateRegistrationBeforeDeadline(): void
    {
        if (!empty($this->registration_deadline) && !empty($this->deadline)
            && strtotime($this->deadline) - strtotime($this->registration_deadline) < self::MIN_SUBMISSION_WINDOW) {
            $this->addError('registration_deadline', 'The final deadline must be at least 1 day after the registration deadline — submissions only open once registration closes.');
        }
    }

    /** e.g. "Registration closes Oct 10, 16:06 · Submissions open then and close Oct 15, 16:06". */
    public function scheduleLabel(): string
    {
        $fmt = fn($d) => date('M j, Y H:i', strtotime($d));
        if ($this->hasEnded()) {
            return 'Completed · closed ' . $fmt($this->deadline);
        }
        $parts = [];
        if (!empty($this->registration_deadline) && !$this->registrationDeadlinePassed()) {
            $parts[] = 'Registration closes ' . $fmt($this->registration_deadline);
            $parts[] = 'Submissions open then and ' . (empty($this->deadline) ? 'have no end date' : 'close ' . $fmt($this->deadline));
        } else {
            $parts[] = empty($this->registration_deadline) ? 'Register and submit any time' : 'Registration closed';
            $parts[] = empty($this->deadline) ? 'Submissions have no end date' : 'Submissions close ' . $fmt($this->deadline);
        }
        return implode(' · ', $parts);
    }
}