<?php

use App\Models\TrainingLog;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * There is no TrainingLog factory; this fills in the required evaluation columns.
 */
function createTrainingLog(array $overrides = []): TrainingLog
{
    return TrainingLog::create(array_merge([
        'session_date' => now()->toDateString(),
        'position' => 'EDDF_TWR',
        'type' => 'O',
        'theory' => 3,
        'phraseology' => 3,
        'coordination' => 3,
        'tag_management' => 3,
        'situational_awareness' => 3,
        'problem_recognition' => 3,
        'traffic_planning' => 3,
        'reaction' => 3,
        'separation' => 3,
        'efficiency' => 3,
        'ability_to_work_under_pressure' => 3,
        'motivation' => 3,
        'result' => true,
    ], $overrides));
}
