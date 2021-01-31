<?php

namespace Tests\Services;

use App\Services\SolutionChecker;
use Tests\TestCase;

class SolutionCheckerTest extends TestCase
{
    private SolutionChecker $checker;

    public function setUp(): void
    {
        parent::setUp();
        $this->checker = app()->get(SolutionChecker::class);
    }

    /**
     * @i
     */
    public function testCheck()
    {
        $this->markTestIncomplete();
        $exercise = '1.3';

        $code = <<<'CODE'
(define (square x) (* x x))

(define (sum-of-squares x y) (+ (square x) (square y)))

(define (solution a b c)
(let ((biggest (max a b c)))
(cond ((and (< a b) (< a c)) (sum-of-squares b c))
((and (< b a) (< b c)) (sum-of-squares a c))
((and (< c b) (< c a)) (sum-of-squares a b))
(else (* 2 (square biggest))))))
CODE;

        $result = $this->checker->check($exercise, $code);

        $this->assertEquals(0, $result['exit_code'], $result['output']);
    }
}
