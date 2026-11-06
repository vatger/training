<?php

use App\Integrations\Vatger\VatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Jobs\UpdateTeamOverviewPost;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\Examiner;
use App\Models\LeadingMentor;
use App\Models\Role;
use App\Models\TeamOverviewTemplate;
use App\Models\User;
use App\Services\TeamOverview\TeamOverviewData;
use App\Services\TeamOverview\TeamOverviewRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('renders the post template with the current examiners in a code block', function () {
    Examiner::create(['user_id' => User::factory()->create(['first_name' => 'Ann', 'last_name' => 'First'])->id, 'callsign' => 'ATDGER9', 'positions' => ['TWR']]);

    $post = app(TeamOverviewRenderer::class)->render();

    expect($post)
        ->toContain("[B]TD-Prüfer[/B]\n[CODE]\nATDGER9 - Ann First, TWR\n[/CODE]")
        ->not->toContain('{{');
});

test('job posts the rendered overview to the configured forum post', function () {
    config([
        'services.vatger.api_url' => 'https://forum.test/api',
        'services.vatger.api_key' => 'secret',
    ]);
    app()->bind(VatgerClientInterface::class, VatgerClient::class);
    Http::fake(['*' => Http::response('1', 200)]);

    UpdateTeamOverviewPost::dispatchSync();

    Http::assertSent(fn ($request) => $request->url() === 'https://forum.test/api/board/post/atd-team-overview'
        && $request->hasHeader('Authorization', 'Token secret')
        && str_contains($request['text_data'], 'Übersicht des ATC-TD Teams'));
});

test('renderer uses the saved template instead of the default', function () {
    TeamOverviewTemplate::create(['content' => "Custom\n{{pruefer}}{{bremen}}{{langen}}{{muenchen}}"]);

    expect(app(TeamOverviewRenderer::class)->render())->toStartWith('Custom');
});

test('an empty 200 from the api is reported as a failed update', function () {
    config(['services.vatger.api_url' => 'https://forum.test/api', 'services.vatger.api_key' => 'secret']);
    Http::fake(['*' => Http::response('', 200)]);

    expect((new VatgerClient)->postAtdTeamOverview('text'))->toMatchArray(['success' => false]);
});

test('any non-empty 200 body from the api counts as a successful update', function (string $body) {
    config(['services.vatger.api_url' => 'https://forum.test/api', 'services.vatger.api_key' => 'secret']);
    Http::fake(['*' => Http::response($body, 200)]);

    expect((new VatgerClient)->postAtdTeamOverview('text'))->toBe(['success' => true]);
})->with(['1', 'true', '{"success":true}']);

test('a false body from the api is reported as a failed update', function (string $body) {
    config(['services.vatger.api_url' => 'https://forum.test/api', 'services.vatger.api_key' => 'secret']);
    Http::fake(['*' => Http::response($body, 200)]);

    expect((new VatgerClient)->postAtdTeamOverview('text'))->toMatchArray(['success' => false]);
})->with(['false', '0']);

test('examiners list their approved stations in a fixed order', function () {
    $user = fn ($first) => User::factory()->create(['first_name' => $first, 'last_name' => 'X'])->id;
    Examiner::create(['user_id' => $user('Ann'), 'callsign' => 'ATDGER1', 'positions' => ['CTR', 'TWR']]);
    Examiner::create(['user_id' => $user('Bob'), 'callsign' => 'ATDGER2', 'positions' => []]);

    expect(app(TeamOverviewRenderer::class)->render())
        ->toContain("ATDGER1 - Ann X, TWR/CTR\nATDGER2 - Bob X\n");
});

test('FIR mentors are grouped by importance and sorted alphabetically within each group', function () {
    $role = Role::create(['name' => 'EDWW Mentor']);
    $course = Course::factory()->create(['airport_icao' => 'EDDH', 'position' => 'TWR']);
    $mentor = function (string $first) use ($role) {
        $user = User::factory()->create(['first_name' => $first, 'last_name' => 'X']);
        $user->roles()->attach($role);

        return $user;
    };

    $mentor('Zora');
    $mentor('Önder');
    $mentor('Anna');
    Examiner::create(['user_id' => $mentor('Yves')->id, 'callsign' => 'ATDGER1', 'positions' => ['TWR']]);
    Examiner::create(['user_id' => $mentor('Bert')->id, 'callsign' => 'ATDGER2', 'positions' => ['TWR']]);
    ChiefOfTraining::create(['user_id' => $mentor('Xaver')->id, 'course_id' => $course->id]);
    LeadingMentor::create(['user_id' => $mentor('Walt')->id, 'fir' => 'EDWW']);

    $names = collect((new TeamOverviewData)->sections()['bremen'])
        ->map(fn ($line) => strtok($line, ' '));

    expect($names->all())->toBe(['Walt', 'Xaver', 'Bert', 'Yves', 'Anna', 'Önder', 'Zora']);
});
