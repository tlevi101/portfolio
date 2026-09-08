<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\Visit;
use App\Models\Visitor;
use App\Models\VisitSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

class VisitRecorder
{
    /**
     * User-Agent fragments that mark a request as automated. Most crawlers and
     * link-preview fetchers announce themselves here; the JS beacon already
     * filters the silent ones, so this is a secondary, best-effort flag.
     */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|embedly|quora|pinterest|whatsapp|telegram|flipboard|skypeuripreview|nuzzel|discord|preview|headless|phantomjs|python-requests|curl|wget|libwww|http[-_]?client|scrapy|axios|go-http|java\/|okhttp|monitor|uptime|pingdom|lighthouse|gtmetrix|semrush|ahrefs|dotbot|mj12bot|petalbot|bytespider|dataforseo/i';

    /**
     * Record one hit. The raw IP is hashed with the app key as a secret salt so
     * it can never be reversed back to an address; nothing is stored on the
     * visitor's device.
     */
    public function record(Request $request, string $event, ?string $path = null, ?string $slug = null, ?string $locale = null, ?string $referer = null, ?string $label = null, ?int $value = null): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        $path ??= '/'.ltrim($request->path(), '/');
        [$pathOnly, $query] = $this->splitPath($path);

        $userAgent = (string) $request->userAgent();

        $visit = Visit::query()->create([
            'event' => $event,
            'label' => $label,
            'value' => $value,
            'ip_hash' => $this->hashIp($request->ip()),
            'cv_id' => $this->cvFromToken($query[Cv::TRACKING_PARAMETER] ?? null),
            'path' => Str::limit($path, 1024, ''),
            'slug' => $slug ?? $this->slugFromPath($pathOnly),
            'locale' => $locale ?? ($query['lang'] ?? null),
            'country' => $this->normaliseCountry($request->header('CF-IPCountry')),
            'referer' => $referer !== null ? Str::limit($referer, 1024, '') : null,
            'user_agent' => $userAgent !== '' ? Str::limit($userAgent, 512, '') : null,
            'is_bot' => $this->looksLikeBot($userAgent),
        ]);

        $this->attachToSession($visit);
        $this->touchVisitor($visit);
    }

    /**
     * Whether this hit is worth recording at all.
     *
     * All three gates exist to keep the site's owner out of their own numbers.
     * The one that does the real work is the session check: the beacon is a
     * same-origin POST through the `web` group, so an admin who is logged in is
     * recognisable without knowing anything about their network.
     */
    public function shouldTrack(Request $request): bool
    {
        if (Auth::check()) {
            return false;
        }

        if ($request->is(...config('analytics.ignored_paths', []))) {
            return false;
        }

        $ignored = config('analytics.ignored_ips', []);
        $ip = $request->ip();

        return $ignored === [] || $ip === null || ! IpUtils::checkIp($ip, $ignored);
    }

    /**
     * Place the hit in a browsing session, opening one if the visitor has been
     * quiet for longer than the gap.
     *
     * The session is saved once, after absorbing the hit, so opening a session
     * costs one insert rather than an insert and an update.
     */
    private function attachToSession(Visit $visit): void
    {
        if ($visit->ip_hash === null) {
            return;
        }

        $at = $visit->created_at ?? now();

        $session = VisitSession::query()
            ->where('ip_hash', $visit->ip_hash)
            ->where('ended_at', '>=', $at->copy()->subMinutes(VisitSession::GAP_MINUTES))
            ->orderByDesc('ended_at')
            ->first()
            // Whatever sent them here, captured before internal navigation
            // overwrites the referrer with our own address.
            ?? VisitSession::open($visit, $this->externalReferer($visit->referer));

        $session->absorb($visit);
        $session->save();

        $visit->visit_session_id = $session->getKey();
        $visit->save();
    }

    /**
     * Keep the one-row-per-visitor aggregate in sync with the event log.
     */
    private function touchVisitor(Visit $visit): void
    {
        if ($visit->ip_hash === null) {
            return;
        }

        $visitor = Visitor::query()->firstOrNew(['ip_hash' => $visit->ip_hash]);

        $seenAt = $visit->created_at ?? now();
        $visitor->first_seen_at ??= $seenAt;
        $visitor->last_seen_at = $seenAt;
        $visitor->country ??= $visit->country;
        // Once any event from this IP looks human, treat the visitor as human.
        $visitor->is_bot = $visitor->exists
            ? ($visitor->is_bot && $visit->is_bot)
            : $visit->is_bot;

        $visitor->save();
    }

    /**
     * The CV a tracking token belongs to.
     *
     * An unknown token is simply not attributed — the link may be from a CV
     * that has since been deleted, or someone may have typed it wrong.
     */
    private function cvFromToken(mixed $token): ?int
    {
        if (! is_string($token) || blank($token)) {
            return null;
        }

        return Cv::query()->where('tracking_token', $token)->value('id');
    }

    /**
     * A referrer worth keeping: our own pages are where visitors come from once
     * they are already here, which says nothing about how they found us.
     */
    private function externalReferer(?string $referer): ?string
    {
        if (blank($referer)) {
            return null;
        }

        $host = parse_url((string) $referer, PHP_URL_HOST);

        return $host !== null && $host !== parse_url((string) config('app.url'), PHP_URL_HOST)
            ? $referer
            : null;
    }

    private function hashIp(?string $ip): ?string
    {
        if (blank($ip)) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    private function looksLikeBot(string $userAgent): bool
    {
        return $userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function splitPath(string $path): array
    {
        $parsed = parse_url($path);
        $query = [];
        parse_str($parsed['query'] ?? '', $query);

        /** @var array<string, string> $query */
        return [$parsed['path'] ?? $path, $query];
    }

    private function slugFromPath(string $path): ?string
    {
        $segment = trim($path, '/');

        if ($segment === '' || str_contains($segment, '/')) {
            // Home page (default portfolio) or a non-portfolio route.
            return $segment === '' ? null : Str::before($segment, '/');
        }

        return $segment;
    }

    private function normaliseCountry(?string $country): ?string
    {
        $country = strtoupper(trim((string) $country));

        // Cloudflare sends "XX"/"T1" for unknown or Tor-exit clients.
        return preg_match('/^[A-Z]{2}$/', $country) === 1 && $country !== 'XX'
            ? $country
            : null;
    }
}
