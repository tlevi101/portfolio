<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How long a visitor may go quiet before their next hit counts as a new
     * visit. Thirty minutes is the web-analytics convention, and it is well
     * clear of someone reading a page slowly.
     */
    private const GAP_MINUTES = 30;

    /**
     * Depth of each landing-page section, mirroring App\Enums\PortfolioSection.
     * Spelled out rather than imported: a migration has to keep working after
     * the enum it was written against has moved on.
     *
     * @var array<string, int>
     */
    private const SECTION_DEPTH = [
        'top' => 0,
        'projects' => 1,
        'experiments' => 2,
        'about' => 3,
        'experience' => 4,
        'contact' => 5,
    ];

    public function up(): void
    {
        Schema::create('visit_sessions', function (Blueprint $table) {
            $table->id();
            // Same salted hash the visits carry, so a session belongs to a
            // visitor without either of them storing an address.
            $table->string('ip_hash', 64)->index();
            // The CV whose printed link brought them here, if one did.
            $table->unsignedBigInteger('cv_id')->nullable()->index();

            $table->string('slug')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('country', 2)->nullable();
            // The referrer that started the session, before internal navigation
            // overwrites it with our own address.
            $table->string('referer', 1024)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('is_bot')->default(false)->index();

            // Rolled up as events arrive, rather than counted on every render:
            // this table is read as a list and sorted by these columns.
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('cv_downloads')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('deepest_section', 32)->nullable();

            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->index();
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_session_id')->nullable()->after('id')->index();
            $table->unsignedBigInteger('cv_id')->nullable()->after('ip_hash')->index();
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['visit_session_id', 'cv_id']);
        });

        Schema::dropIfExists('visit_sessions');
    }

    /**
     * Group the hits already recorded into sessions, so the redesigned admin
     * has something to show on the day it ships rather than starting empty.
     *
     * Walked in PHP over (ip_hash, created_at): the gap rule is sequential, and
     * expressing it in MariaDB would cost far more than it saves on a log this
     * size.
     */
    private function backfill(): void
    {
        $open = null;
        $previousHash = null;

        DB::table('visits')
            ->whereNotNull('ip_hash')
            ->orderBy('ip_hash')
            ->orderBy('created_at')
            ->orderBy('id')
            ->each(function (object $visit) use (&$open, &$previousHash): void {
                $at = $visit->created_at !== null ? strtotime((string) $visit->created_at) : time();

                $continues = $open !== null
                    && $previousHash === $visit->ip_hash
                    && $at - $open['ended_at'] <= self::GAP_MINUTES * 60;

                if (! $continues) {
                    $this->flush($open);
                    $open = $this->start($visit, $at);
                }

                $this->accumulate($open, $visit, $at);
                $previousHash = $visit->ip_hash;
            });

        $this->flush($open);
    }

    /**
     * @return array<string, mixed>
     */
    private function start(object $visit, int $at): array
    {
        return [
            'ip_hash' => $visit->ip_hash,
            'cv_id' => null,
            'slug' => $visit->slug,
            'locale' => $visit->locale,
            'country' => $visit->country,
            'referer' => $visit->referer,
            'user_agent' => $visit->user_agent,
            'is_bot' => (bool) $visit->is_bot,
            'page_views' => 0,
            'clicks' => 0,
            'cv_downloads' => 0,
            'duration_seconds' => 0,
            'deepest_section' => null,
            'started_at' => $at,
            'ended_at' => $at,
            'visit_ids' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function accumulate(array &$session, object $visit, int $at): void
    {
        $session['ended_at'] = max($session['ended_at'], $at);
        $session['visit_ids'][] = $visit->id;
        $session['slug'] ??= $visit->slug;
        $session['locale'] ??= $visit->locale;
        $session['country'] ??= $visit->country;
        // A session counts as human as soon as any one hit in it does.
        $session['is_bot'] = $session['is_bot'] && (bool) $visit->is_bot;

        match ($visit->event) {
            'page_view' => $session['page_views']++,
            'click' => $session['clicks']++,
            'cv_download' => $session['cv_downloads']++,
            'duration' => $session['duration_seconds'] += (int) $visit->value,
            'section' => $session['deepest_section'] = $this->deeper($session['deepest_section'], $visit->label),
            default => null,
        };
    }

    private function deeper(?string $current, ?string $candidate): ?string
    {
        if ($candidate === null || ! array_key_exists($candidate, self::SECTION_DEPTH)) {
            return $current;
        }

        if ($current === null || self::SECTION_DEPTH[$candidate] > (self::SECTION_DEPTH[$current] ?? -1)) {
            return $candidate;
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>|null  $session
     */
    private function flush(?array $session): void
    {
        if ($session === null) {
            return;
        }

        $visitIds = $session['visit_ids'];
        unset($session['visit_ids']);

        $session['started_at'] = date('Y-m-d H:i:s', $session['started_at']);
        $session['ended_at'] = date('Y-m-d H:i:s', $session['ended_at']);

        $id = DB::table('visit_sessions')->insertGetId($session);

        DB::table('visits')->whereIn('id', $visitIds)->update(['visit_session_id' => $id]);
    }
};
