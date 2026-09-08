<?php

namespace Tests\Feature;

use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visitors\Pages\ViewVisitor;
use App\Filament\Resources\Visitors\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\VisitSessions\Pages\ListVisitSessions;
use App\Filament\Resources\VisitSessions\Pages\ViewVisitSession;
use App\Filament\Resources\VisitSessions\RelationManagers\VisitsRelationManager;
use App\Models\Cv;
use App\Models\Portfolio;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Models\VisitSession;
use App\Services\CvGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VisitTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_beacon_records_a_page_view_with_hashed_ip(): void
    {
        $this->postJson('/beacon', [
            'event' => 'page_view',
            'path' => '/fullstack?lang=hu',
            'referrer' => 'https://www.linkedin.com/',
        ])->assertNoContent();

        $visit = Visit::sole();

        $this->assertSame('page_view', $visit->event);
        $this->assertSame('/fullstack?lang=hu', $visit->path);
        $this->assertSame('fullstack', $visit->slug);
        $this->assertSame('hu', $visit->locale);
        $this->assertSame('https://www.linkedin.com/', $visit->referer);
        $this->assertFalse($visit->is_bot);

        // The IP must be stored only as a salted hash, never raw.
        $this->assertNotNull($visit->ip_hash);
        $this->assertStringNotContainsString('127.0.0.1', (string) $visit->ip_hash);
        $this->assertSame(hash_hmac('sha256', '127.0.0.1', (string) config('app.key')), $visit->ip_hash);
    }

    public function test_beacon_records_clicks_sections_and_duration(): void
    {
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'linkedin'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'section', 'label' => 'about'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'duration', 'value' => 42])->assertNoContent();

        $this->assertSame('linkedin', Visit::where('event', 'click')->sole()->label);
        $this->assertSame('about', Visit::where('event', 'section')->sole()->label);
        $this->assertSame(42, Visit::where('event', 'duration')->sole()->value);
    }

    public function test_beacon_rejects_unknown_events(): void
    {
        $this->postJson('/beacon', ['event' => 'evil'])->assertUnprocessable();

        $this->assertSame(0, Visit::count());
    }

    public function test_bot_user_agents_are_flagged(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'], [
            'User-Agent' => 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
        ])->assertNoContent();

        $this->assertTrue(Visit::sole()->is_bot);
    }

    public function test_cv_download_is_recorded_server_side(): void
    {
        Storage::fake('public');

        $this->get('/cv/fullstack')->assertStatus(200);

        $visit = Visit::where('event', 'cv_download')->sole();

        $this->assertSame('fullstack', $visit->slug);
        $this->assertSame('hu', $visit->locale);
    }

    public function test_repeat_events_collapse_into_one_visitor_row(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'github'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'duration', 'value' => 30])->assertNoContent();

        $visitor = Visitor::sole();

        $this->assertSame(3, Visit::count());
        $this->assertSame($visitor->ip_hash, Visit::first()->ip_hash);
        $this->assertFalse($visitor->is_bot);
        $this->assertNotNull($visitor->first_seen_at);
        $this->assertNotNull($visitor->last_seen_at);
    }

    public function test_a_visitor_with_any_human_event_counts_as_human(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'], [
            'User-Agent' => 'Mozilla/5.0 (compatible; AhrefsBot/7.0)',
        ])->assertNoContent();

        $this->assertTrue(Visitor::sole()->is_bot);

        // Same IP later sends a normal browser event → reclassified as human.
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->assertSame(1, Visitor::count());
        $this->assertFalse(Visitor::sole()->is_bot);
    }

    public function test_visitors_admin_page_renders_with_aggregates(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'duration', 'value' => 90])->assertNoContent();

        $this->actingAs(User::sole());

        Livewire::test(ListVisitors::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Visitor::all());
    }

    public function test_visits_admin_page_lists_sessions_rather_than_events(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'linkedin'])->assertNoContent();

        $this->actingAs(User::sole());

        Livewire::test(ListVisitSessions::class)
            ->assertOk()
            ->assertCanSeeTableRecords(VisitSession::all());
    }

    public function test_a_session_page_renders_with_its_event_timeline(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'section', 'label' => 'contact'])->assertNoContent();

        $this->actingAs(User::sole());

        Livewire::test(ViewVisitSession::class, ['record' => VisitSession::sole()->getKey()])->assertOk();
    }

    public function test_a_visitor_page_renders_with_its_sessions(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->actingAs(User::sole());

        Livewire::test(ViewVisitor::class, ['record' => Visitor::sole()->getKey()])->assertOk();

        Livewire::test(SessionsRelationManager::class, [
            'ownerRecord' => Visitor::sole(),
            'pageClass' => ViewVisitor::class,
        ])->assertCanSeeTableRecords(VisitSession::all());
    }

    public function test_the_event_timeline_lists_the_hits_of_that_visit_only(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'github'])->assertNoContent();

        $session = VisitSession::sole();

        $this->actingAs(User::sole());

        Livewire::test(VisitsRelationManager::class, [
            'ownerRecord' => $session,
            'pageClass' => ViewVisitSession::class,
        ])->assertCanSeeTableRecords($session->visits);
    }

    public function test_clearing_a_visit_takes_its_hits_with_it(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'github'])->assertNoContent();

        VisitSession::sole()->delete();

        // No database-level foreign keys, so the hits would otherwise be left
        // pointing at a session that no longer exists.
        $this->assertSame(0, Visit::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Who is not counted
    |--------------------------------------------------------------------------
    */

    public function test_a_logged_in_admin_is_not_tracked(): void
    {
        $this->actingAs(User::sole());

        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();
        $this->get('/cv/fullstack');

        // Reading your own site from the admin session is not a visit, and on a
        // portfolio this quiet it would otherwise be most of the data.
        $this->assertSame(0, Visit::count());
        $this->assertSame(0, VisitSession::count());
    }

    public function test_an_ignored_address_is_not_tracked(): void
    {
        config(['analytics.ignored_ips' => ['127.0.0.0/8']]);

        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->assertSame(0, Visit::count());
    }

    public function test_an_address_outside_the_ignored_range_is_still_tracked(): void
    {
        config(['analytics.ignored_ips' => ['203.0.113.4']]);

        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->assertSame(1, Visit::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    */

    public function test_a_run_of_events_becomes_one_session(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view', 'path' => '/fullstack'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'section', 'label' => 'top'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'section', 'label' => 'contact'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'linkedin'])->assertNoContent();
        $this->postJson('/beacon', ['event' => 'duration', 'value' => 45])->assertNoContent();

        $session = VisitSession::sole();

        $this->assertSame(5, Visit::count());
        $this->assertSame(1, $session->page_views);
        $this->assertSame(1, $session->clicks);
        $this->assertSame(45, $session->duration_seconds);
        // The furthest section reached, not the last one reported.
        $this->assertSame('contact', $session->deepest_section);
        $this->assertSame('fullstack', $session->slug);
        $this->assertSame(5, $session->visits()->count());
    }

    public function test_a_visit_after_the_gap_starts_a_new_session(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->travel(VisitSession::GAP_MINUTES + 1)->minutes();

        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->assertSame(2, VisitSession::count());
        $this->assertSame(1, Visitor::count());
    }

    public function test_a_visit_inside_the_gap_continues_the_session(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->travel(VisitSession::GAP_MINUTES - 1)->minutes();

        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->assertSame(1, VisitSession::count());
        $this->assertSame(2, VisitSession::sole()->page_views);
    }

    public function test_a_session_counts_as_human_once_any_hit_does(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view'], [
            'User-Agent' => 'Mozilla/5.0 (compatible; AhrefsBot/7.0)',
        ])->assertNoContent();

        $this->assertTrue(VisitSession::sole()->is_bot);

        $this->postJson('/beacon', ['event' => 'page_view'])->assertNoContent();

        $this->assertFalse(VisitSession::sole()->is_bot);
    }

    /*
    |--------------------------------------------------------------------------
    | Attribution
    |--------------------------------------------------------------------------
    */

    public function test_a_visit_carrying_a_cv_token_is_attributed_to_that_cv(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $this->postJson('/beacon', [
            'event' => 'page_view',
            'path' => '/fullstack?r='.$cv->trackingToken(),
        ])->assertNoContent();

        $this->assertSame($cv->getKey(), Visit::sole()->cv_id);
        $this->assertSame($cv->getKey(), VisitSession::sole()->cv_id);
    }

    public function test_the_attribution_sticks_for_the_rest_of_the_session(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $this->postJson('/beacon', ['event' => 'page_view', 'path' => '/fullstack?r='.$cv->trackingToken()])->assertNoContent();
        // A later beacon from a page without the token must not clear it.
        $this->postJson('/beacon', ['event' => 'click', 'label' => 'linkedin', 'path' => '/fullstack'])->assertNoContent();

        $this->assertSame($cv->getKey(), VisitSession::sole()->cv_id);
    }

    public function test_an_unknown_token_attributes_to_nothing(): void
    {
        $this->postJson('/beacon', ['event' => 'page_view', 'path' => '/fullstack?r=nonsense'])->assertNoContent();

        $this->assertNull(Visit::sole()->cv_id);
        $this->assertNull(VisitSession::sole()->cv_id);
    }

    public function test_every_cv_has_its_own_unguessable_token(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $variant = $cv->createChild('Acme variant');

        $this->assertNotSame($cv->trackingToken(), $variant->trackingToken());
        $this->assertSame(10, strlen($variant->trackingToken()));
        // Nothing in it counts the CVs or points at this one.
        $this->assertStringNotContainsString((string) $cv->getKey(), $cv->trackingToken());

        // Seeded CVs are created with model events off, so they start without
        // one and are given theirs on first use.
        Cv::query()->get()->each->trackingToken();
        $this->assertSame(Cv::query()->count(), Cv::query()->distinct()->count('tracking_token'));
    }

    public function test_the_printed_portfolio_link_carries_the_token(): void
    {
        $cv = Portfolio::default('hu')->cv;

        $this->assertStringContainsString('r='.$cv->trackingToken(), (string) $cv->trackedPortfolioUrl());
        // The visible address stays clean; only the QR and the href are tagged.
        $this->assertStringNotContainsString('r=', (string) $cv->portfolioUrl());
    }

    public function test_the_rendered_cv_tags_the_link_but_not_the_text_under_it(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $token = $cv->trackingToken();

        $html = app(CvGeneratorService::class)->renderHtml($cv->fresh());

        // The href a reader clicks in the PDF, and the QR they scan, both carry
        // the token; the address printed beneath them does not, so the CV never
        // shows a tracking code to the person reading it.
        $this->assertStringContainsString('href="'.e($cv->trackedPortfolioUrl()).'"', $html);
        $this->assertStringContainsString('>'.rtrim(preg_replace('#^https?://#', '', (string) $cv->portfolioUrl()), '/').'</a>', $html);
        $this->assertSame(1, substr_count($html, $token));
    }

    public function test_deleting_a_cv_keeps_the_history_and_drops_the_link(): void
    {
        $cv = Portfolio::default('hu')->cv;
        $variant = $cv->createChild('Acme variant');

        $this->postJson('/beacon', ['event' => 'page_view', 'path' => '/fullstack?r='.$variant->trackingToken()])->assertNoContent();

        $variant->delete();

        // A visit that happened, happened; only the link to the deleted CV goes.
        $this->assertSame(1, VisitSession::count());
        $this->assertNull(VisitSession::sole()->cv_id);
        $this->assertNull(Visit::sole()->cv_id);
    }
}
