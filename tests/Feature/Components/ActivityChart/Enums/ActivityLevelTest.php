<?php

namespace Tests\Feature\Components\ActivityChart\Enums;

use App\Components\ActivityChart\Enums\ActivityLevel;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ActivityLevelTest extends TestCase
{
    #[TestWith([0, ActivityLevel::NONE])]
    #[TestWith([1, ActivityLevel::LOW])]
    #[TestWith([4, ActivityLevel::LOW])]
    #[TestWith([5, ActivityLevel::MEDIUM])]
    #[TestWith([8, ActivityLevel::MEDIUM])]
    #[TestWith([9, ActivityLevel::HIGH])]
    #[TestWith([12, ActivityLevel::HIGH])]
    #[TestWith([13, ActivityLevel::VERY_HIGH])]
    #[TestWith([100, ActivityLevel::VERY_HIGH])]
    public function testFromCount(int $count, ActivityLevel $expected): void
    {
        $this->assertSame($expected, ActivityLevel::fromCount($count));
    }
}
