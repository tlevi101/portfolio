<?php

namespace App\Observers;

use App\Models\Cv;
use App\Models\Education;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\Skill;
use App\Models\WorkExperience;
use App\Services\CvGeneratorService;
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

    private static bool $scheduled = false;

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
        } elseif ($model instanceof WorkExperience || $model instanceof Education) {
            $this->rememberCv($model->cv_id);
            $this->rememberCv($model->getOriginal('cv_id'));
        } elseif ($model instanceof Portfolio) {
            self::$portfolioIds[$model->id] = true;
        } elseif ($model instanceof Project || $model instanceof Skill) {
            $this->rememberPortfolio($model->portfolio_id);
            $this->rememberPortfolio($model->getOriginal('portfolio_id'));
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
        if (self::$scheduled) {
            return;
        }

        self::$scheduled = true;

        app()->terminating(function (): void {
            try {
                $this->regenerate();
            } finally {
                self::$cvIds = [];
                self::$portfolioIds = [];
                self::$scheduled = false;
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
