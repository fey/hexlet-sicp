<?php

namespace Tests\Feature\Services;

use App\DTO\Progress\ChapterProgressData;
use App\Models\Chapter;
use App\Models\ChapterMember;
use App\Models\Exercise;
use App\Models\ExerciseMember;
use App\Models\User;
use App\Services\ChapterProgressService;
use Tests\TestCase;

class ChapterProgressServiceTest extends TestCase
{
    private User $user;
    private Chapter $root;
    private Chapter $firstChild;
    private Chapter $secondChild;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->root = Chapter::factory()->create(['path' => '1']);
        $this->firstChild = Chapter::factory()->create(['path' => '1.1', 'parent_id' => $this->root->id]);
        $this->secondChild = Chapter::factory()->create(['path' => '1.2', 'parent_id' => $this->root->id]);
    }

    public function testParentIsCompletedWhenEveryChildIsFinished(): void
    {
        $this->finishChapter($this->firstChild);
        $this->finishChapter($this->secondChild);

        $progress = $this->buildRootProgress();

        $this->assertTrue($progress->isCompleted);
        $this->assertFalse($progress->isStarted());
        $this->assertSame(2, $progress->getCompletedChildrenCount());
        $this->assertSame(2, $progress->getTotalChildrenCount());
    }

    public function testParentIsStartedButNotCompletedWhenOneChildIsUnfinished(): void
    {
        $this->finishChapter($this->firstChild);
        ChapterMember::factory()->user($this->user)->chapter($this->secondChild)->create();

        $progress = $this->buildRootProgress();

        $this->assertFalse($progress->isCompleted);
        $this->assertTrue($progress->isStarted());
        $this->assertSame(1, $progress->getCompletedChildrenCount());
        $this->assertSame(
            [true, false],
            $progress->childrenProgress->map(fn($child) => $child->isCompleted)->values()->all()
        );
    }

    public function testExerciseProgressDistinguishesFinishedAndNotStarted(): void
    {
        $finished = Exercise::factory()->create(['chapter_id' => $this->firstChild->id, 'path' => '1.1']);
        $notStarted = Exercise::factory()->create(['chapter_id' => $this->firstChild->id, 'path' => '1.2']);
        ExerciseMember::factory()->user($this->user)->exercise($finished)->create();

        $progress = $this->buildRootProgress()->childrenProgress->first();

        $exercises = $progress->exercisesProgress->keyBy(fn($item) => $item->exercise->id);
        $this->assertTrue($exercises[$finished->id]->isCompleted());
        $this->assertTrue($exercises[$notStarted->id]->isNotStarted());
        $this->assertNull($this->buildRootProgress()->exercisesProgress);
    }

    private function finishChapter(Chapter $chapter): void
    {
        ChapterMember::factory()->user($this->user)->chapter($chapter)->create([
            'state' => ChapterMember::STATE_FINISHED,
        ]);
    }

    private function buildRootProgress(): ChapterProgressData
    {
        $this->user->load('chapterMembers', 'exerciseMembers');

        return app(ChapterProgressService::class)->buildChapterProgress(
            Chapter::with(['children', 'exercises'])->findOrFail($this->root->id),
            $this->user->chapterMembers->keyBy('chapter_id'),
            $this->user->exerciseMembers->keyBy('exercise_id'),
        );
    }
}
