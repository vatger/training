<?php

use App\Integrations\Vatger\VatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Jobs\UpdateTeamOverviewPost;
use App\Models\Examiner;
use App\Models\TeamOverviewTemplate;
use App\Models\User;
use App\Services\TeamOverview\TeamOverviewRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('renders the post template with the current examiners in a code block', function () {
    Examiner::create(['user_id' => User::factory()->create(['first_name' => 'Ann', 'last_name' => 'First'])->id, 'callsign' => 'ATDGER9', 'positions' => ['TWR']]);

    $post = app(TeamOverviewRenderer::class)->render();

    expect($post)
        ->toContain("[B]TD-Prüfer[/B]\n[CODE]\nATDGER9 - Ann First\n[/CODE]")
        ->not->toContain('{{');
});

test('job posts the rendered overview to the configured forum post', function () {
    config([
        'services.vatger.api_url' => 'https://forum.test/api',
        'services.vatger.api_key' => 'secret',
    ]);
    app()->bind(VatgerClientInterface::class, VatgerClient::class);
    Http::fake(['*' => Http::response([], 200)]);

    UpdateTeamOverviewPost::dispatchSync();

    Http::assertSent(fn ($request) => $request->url() === 'https://forum.test/api/board/post/atd-team-overview'
        && $request->hasHeader('Authorization', 'Token secret')
        && str_contains($request['text_data'], 'Übersicht des ATC-TD Teams'));
});

test('renderer uses the saved template instead of the default', function () {
    TeamOverviewTemplate::create(['content' => "Custom\n{{pruefer}}{{bremen}}{{langen}}{{muenchen}}"]);

    expect(app(TeamOverviewRenderer::class)->render())->toStartWith('Custom');
});
