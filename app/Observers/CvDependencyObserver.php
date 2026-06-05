<?php

namespace App\Observers;

use App\Services\CvGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CvDependencyObserver
{
    private static bool $scheduled = false;

    public function saved(Model $model): void
    {
        $this->scheduleRegeneration();
    }

    public function deleted(Model $model): void
    {
        $this->scheduleRegeneration();
    }

    /**
     * Coalesce every dependency change in the current request or command into
     * a single CV regeneration that runs once the response has been sent, so a
     * bulk edit or a full seed regenerates the PDF only once.
     */
    protected function scheduleRegeneration(): void
    {
        if (self::$scheduled) {
            return;
        }

        self::$scheduled = true;

        app()->terminating(function (): void {
            try {
                app(CvGeneratorService::class)->generate();
            } catch (\Throwable $e) {
                Log::error('CV regeneration failed: '.$e->getMessage());
            } finally {
                self::$scheduled = false;
            }
        });
    }
}
