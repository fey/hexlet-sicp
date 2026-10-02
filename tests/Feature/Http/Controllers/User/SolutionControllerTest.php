<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\Solution;
use App\Models\User;
use Database\Seeders\ChaptersTableSeeder;
use Database\Seeders\ExercisesTableSeeder;
use Tests\ControllerTestCase;

class SolutionControllerTest extends ControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ChaptersTableSeeder::class,
            ExercisesTableSeeder::class,
        ]);

        $this->user->solutions()->saveMany(
            Solution::factory()->count(5)->make()
        );

        $this->actingAs($this->user);
    }

    public function testShow(): void
    {
        $solution = $this->user->solutions()->first();

        $response = $this->get(route('users.solutions.show', [$this->user, $solution]));
        $response->assertOk();
    }

    public function testShowOfForeignSolutionIsForbidden(): void
    {
        $this->withExceptionHandling();
        $owner = User::factory()->create();
        $solution = Solution::factory()->for($owner)->create();

        $response = $this->get(route('users.solutions.show', [$owner, $solution]));

        $response->assertForbidden();
    }

    public function testShowOfForeignSolutionIsAllowedForAdmin(): void
    {
        $owner = User::factory()->create();
        $solution = Solution::factory()->for($owner)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('users.solutions.show', [$owner, $solution]));

        $response->assertOk();
    }
}
