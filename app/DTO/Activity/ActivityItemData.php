<?php

namespace App\DTO\Activity;

use App\Helpers\ChapterHelper;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Exercise;
use App\Models\Solution;
use App\Services\ActivityService;
use Spatie\LaravelData\Data;

class ActivityItemData extends Data
{
    /**
     * @param array<int, ActivityLinkData> $links
     */
    public function __construct(
        public int $id,
        public ?string $causerName,
        public ?string $causerUrl,
        public string $description,
        public array $links,
        public string $createdAt,
    ) {
    }

    /**
     * Ждёт causer и subject, загруженные заранее (см. ActivityController): иначе запрос на каждую запись.
     * Subject бывает удалён — тогда запись остаётся, но без ссылки.
     */
    public static function fromModel(Activity $activity): self
    {
        $causer = $activity->causer;
        $subject = $activity->subject;
        $exerciseUrl = fn() => route('exercises.show', $activity->getProperty('exercise_id'));

        [$description, $links] = match ($activity->description) {
            ActivityService::ACTIVITY_CHAPTER_ADDED, ActivityService::ACTIVITY_CHAPTER_REMOVED => [
                $activity->getDescription(),
                array_map(
                    fn(string $path) => new ActivityLinkData(
                        label: ChapterHelper::fullChapterName($path),
                        href: ChapterHelper::getChapterOriginLinkForNumber($path),
                    ),
                    $activity->getProperty('chapters') ?? [],
                ),
            ],
            ActivityService::COMMENTED => [
                $activity->getDescription(),
                $subject instanceof Comment
                    ? [new ActivityLinkData($subject->getCommentableName() ?? '', $activity->getProperty('url'))]
                    : [],
            ],
            ActivityService::ACTIVITY_EXERCISE_COMPLETED, ActivityService::ACTIVITY_EXERCISE_REMOVED => [
                $activity->getDescription(),
                $subject instanceof Exercise ? [new ActivityLinkData($subject->getFullTitle(), $exerciseUrl())] : [],
            ],
            ActivityService::ACTIVITY_SOLUTION_ADDED => [
                $activity->getDescription(),
                [new ActivityLinkData(
                    $subject instanceof Solution && $subject->exercise
                        ? $subject->exercise->getFullTitle()
                        : (string) $activity->getProperty('exercise_path'),
                    $exerciseUrl(),
                )],
            ],
            default => [__('activitylog.action_unknown'), []],
        };

        return new self(
            id: $activity->id,
            causerName: $causer?->name,
            causerUrl: $causer ? route('users.show', $causer) : null,
            description: $description,
            links: $links,
            createdAt: $activity->created_at->toDateTimeString(),
        );
    }
}
