<?php

namespace Tests\Feature;

use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
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

    public function test_visits_admin_page_renders_with_tabs_and_stats(): void
    {
        Visit::query()->create([
            'event' => 'click',
            'label' => 'linkedin',
            'ip_hash' => str_repeat('a', 64),
            'is_bot' => false,
        ]);

        $this->actingAs(User::sole());

        Livewire::test(ListVisits::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Visit::all());
    }
}
