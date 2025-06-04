<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    // use RefreshDatabase;
    use WithFaker;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }
}
