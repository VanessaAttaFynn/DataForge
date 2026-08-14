<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string|null $cover_image_path
 * @property int $author_id
 * @property string $status
 * @property bool $verified
 * @property int $created_at
 * @property int $updated_at
 */
class Post extends ActiveRecord
{
    // post.type values
    const TYPE_DATASET = 'dataset';
    const TYPE_NOTEBOOK = 'notebook';
    const TYPE_COMPETITION = 'competition';
    const TYPE_HACKATHON = 'hackathon';
    const TYPE_DISCUSSION = 'discussion';

    // post.status values
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED = 'rejected';

    // Types that require moderator/admin approval before going live.
    const TYPES_REQUIRING_APPROVAL = [self::TYPE_COMPETITION, self::TYPE_HACKATHON];

    public static function tableName()
    {
        return '{{%post}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['type', 'title', 'slug', 'author_id'], 'required'],
            [['body'], 'string'],
            [['author_id', 'verified'], 'integer'],
            [['type', 'status'], 'string', 'max' => 20],
            [['title', 'slug', 'cover_image_path'], 'string', 'max' => 255],
            ['type', 'in', 'range' => [
                self::TYPE_DATASET, self::TYPE_NOTEBOOK, self::TYPE_COMPETITION,
                self::TYPE_HACKATHON, self::TYPE_DISCUSSION,
            ]],
            ['status', 'in', 'range' => [
                self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_PUBLISHED, self::STATUS_REJECTED,
            ]],
            ['slug', 'unique'],
        ];
    }

    public function getAuthor()
    {
        return $this->hasOne(\app\models\User::class, ['id' => 'author_id']);
    }

    public function getStatusHistory()
    {
        return $this->hasMany(PostStatusHistory::class, ['post_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    public function getCompetition()
    {
        return $this->hasOne(Competition::class, ['post_id' => 'id']);
    }

    public function getDataset()
    {
        return $this->hasOne(Dataset::class, ['post_id' => 'id']);
    }

    /**
     * Whether this post's type requires moderator/admin approval before
     * it can go live — competitions/hackathons do, datasets/discussions don't.
     */
    public function requiresApproval(): bool
    {
        return in_array($this->type, self::TYPES_REQUIRING_APPROVAL, true);
    }

    /**
     * Called right after creation. Datasets/discussions publish immediately
     * (datasets start unverified); competitions/hackathons go to pending.
     */
    public function applyInitialStatus(): void
    {
        $this->status = $this->requiresApproval() ? self::STATUS_PENDING : self::STATUS_PUBLISHED;
    }

    /**
     * Approve a pending competition/hackathon. Logs history + notifies the author.
     */
    public function approve(int $moderatorId, string $note = ''): bool
    {
        return $this->transitionStatus(self::STATUS_PUBLISHED, $moderatorId, $note);
    }

    /**
     * Reject a pending competition/hackathon. A note explaining why is
     * expected — it's what the author sees in their notification.
     */
    public function reject(int $moderatorId, string $note): bool
    {
        return $this->transitionStatus(self::STATUS_REJECTED, $moderatorId, $note);
    }

    private function transitionStatus(string $newStatus, int $changedBy, string $note): bool
    {
        $oldStatus = $this->status;

        $transaction = self::getDb()->beginTransaction();
        try {
            $this->status = $newStatus;
            if (!$this->save(false)) {
                throw new \RuntimeException('Failed to save post status.');
            }

            $history = new PostStatusHistory([
                'post_id' => $this->id,
                'changed_by' => $changedBy,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
                'created_at' => time(),
            ]);
            if (!$history->save()) {
                throw new \RuntimeException('Failed to save status history.');
            }

            $message = $newStatus === self::STATUS_PUBLISHED
                ? "Your {$this->type} \"{$this->title}\" was approved and is now live."
                : "Your {$this->type} \"{$this->title}\" was rejected." . ($note ? " Reason: {$note}" : '');

            $notification = new Notification([
                'user_id' => $this->author_id,
                'type' => 'post_status_change',
                'message' => $message,
                'link' => "/post/view?id={$this->id}",
                'is_read' => 0,
                'created_at' => time(),
            ]);
            if (!$notification->save()) {
                throw new \RuntimeException('Failed to save notification.');
            }

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            return false;
        }
    }
}