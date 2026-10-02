<?php

namespace Tests\Feature\Components\ActivityChart\Enums;

use App\Components\ActivityChart\Enums\ActivityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityLevelTest extends TestCase
{
    #[DataProvider('dataCountToLevel')]
    public function testFromCount(int $count, ActivityLevel $expected): void
    {
        $this->assertSame($expected, ActivityLevel::fromCount($count));
    }

    public static function dataCountToLevel(): array
    {
        return [
            'no activity' => [0, ActivityLevel::NONE],
            'low lower bound' => [1, ActivityLevel::LOW],
            'low upper bound' => [4, ActivityLevel::LOW],
            'medium lower bound' => [5, ActivityLevel::MEDIUM],
            'medium upper bound' => [8, ActivityLevel::MEDIUM],
            'high lower bound' => [9, ActivityLevel::HIGH],
            'high upper bound' => [12, ActivityLevel::HIGH],
            'very high lower bound' => [13, ActivityLevel::VERY_HIGH],
            'very high far above bound' => [100, ActivityLevel::VERY_HIGH],
        ];
    }
}
