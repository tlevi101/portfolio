<?php

namespace App\Http\Controllers;

use App\Services\VisitRecorder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BeaconController extends Controller
{
    public function __construct(private readonly VisitRecorder $visits) {}

    /**
     * Record a human page view. Fired by a small script after the page loads,
     * so requests that never execute JavaScript (most bots) leave no row.
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'event' => ['nullable', 'string', 'in:page_view,click,section,duration'],
            'label' => ['nullable', 'string', 'max:40'],
            'value' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'path' => ['nullable', 'string', 'max:1024'],
            'referrer' => ['nullable', 'string', 'max:1024'],
        ]);

        $this->visits->record(
            $request,
            $validated['event'] ?? 'page_view',
            path: $validated['path'] ?? null,
            referer: $validated['referrer'] ?? null,
            label: $validated['label'] ?? null,
            value: $validated['value'] ?? null,
        );

        return response()->noContent();
    }
}
