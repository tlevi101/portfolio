<?php

namespace App\Observers;

use App\Models\Cv;
use App\Models\CvProject;
use App\Models\CvSkill;
use App\Models\Education;
use App\Models\Portfolio;
use App\Models\WorkExperience;
use App\Services\CvGeneratorService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CvDependencyObserver
{
    /**
     * CV ids directly affected this request.
     *
     * @var array<int, true>
     */
    private static array $cvIds = [];

    /**
     * Portfolio ids whose CVs are affected, expanded to CV ids at terminate.
     *
     * @var array<int, true>
     */
    private static array $portfolioIds = [];

    /**
     * The application instance the regeneration pass is already queued on.
     *
     * Not a plain flag: it is static, so it outlives the container it was set
     * for — a second request, or a second test, would find it already true and
     * never register its own callback, leaving those changes unrendered.
     */
    private static ?Application $scheduledFor = null;

    public function saved(Model $model): void
    {
        $this->track($model);
    }

    public function deleted(Model $model): void
    {
        $this->track($model);
    }

    /**
     * Map a changed dependency to the CV(s) that need regenerating.
     */
    protected function track(Model $model): void
    {
        if ($model instanceof Cv) {
            self::$cvIds[$model->id] = true;
        } elseif (
            $model instanceof WorkExperience
            || $model instanceof Education
            || $model instanceof CvSkill
            || $model instanceof CvProject
        ) {
            $this->rememberCv($model->cv_id);
            $this->rememberCv($model->getOriginal('cv_id'));
        } elseif ($model instanceof Portfolio) {
            // The CV owns its content now; only the portfolio itself still
            // feeds it, via the slug the QR code falls back to.
            self::$portfolioIds[$model->id] = true;
        }

        $this->scheduleRegeneration();
    }

    protected function rememberCv(?int $id): void
    {
        if ($id !== null) {
            self::$cvIds[$id] = true;
        }
    }

    protected function rememberPortfolio(?int $id): void
    {
        if ($id !== null) {
            self::$portfolioIds[$id] = true;
        }
    }

    /**
     * Coalesce every dependency change in the current request or command into a
     * single regeneration pass that runs once the response has been sent, so a
     * bulk edit or a full seed regenerates each affected CV exactly once.
     */
    protected function scheduleRegeneration(): void
    {
        $app = app();

        if (self::$scheduledFor === $app) {
            return;
        }

        self::$scheduledFor = $app;

        $app->terminating(function (): void {
            try {
                $this->regenerate();
            } finally {
                self::$cvIds = [];
                self::$portfolioIds = [];
                self::$scheduledFor = null;
            }
        });
    }

    protected function regenerate(): void
    {
        $cvIds = self::$cvIds;

        if (self::$portfolioIds !== []) {
            foreach (Cv::query()->whereIn('portfolio_id', array_keys(self::$portfolioIds))->pluck('id') as $id) {
                $cvIds[$id] = true;
            }
        }

        if ($cvIds === []) {
            return;
        }

        $service = app(CvGeneratorService::class);

        foreach (Cv::query()->whereKey(array_keys($cvIds))->get() as $cv) {
            try {
                $service->generateFor($cv);
            } catch (\Throwable $e) {
                Log::error("CV regeneration failed for cv #{$cv->id}: ".$e->getMessage());
            }
        }
    }
}
