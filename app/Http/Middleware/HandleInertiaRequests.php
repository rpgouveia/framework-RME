<?php

namespace App\Http\Middleware;

use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\SystemChangeType;
use App\Enums\SystemSourceType;
use App\Enums\UncertaintyLevel;
use App\Enums\VerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The domain enums whose labels the frontend shows.
     */
    private const ENUMS = [
        AdverseEventNature::class,
        AiSystemCategory::class,
        CauseStatus::class,
        ChangeOrigin::class,
        CostLevel::class,
        EvidenceType::class,
        LifecyclePhase::class,
        LinkStatus::class,
        ReassessmentOutcome::class,
        SystemChangeType::class,
        SystemSourceType::class,
        UncertaintyLevel::class,
        VerificationStatus::class,
    ];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Define the props that are shared once and remembered by the client.
     *
     * @see https://inertiajs.com/data-props/once-props
     *
     * @return array<string, mixed>
     */
    public function shareOnce(Request $request): array
    {
        return [
            ...parent::shareOnce($request),
            'translations' => fn (): array => $this->translations(),
            'enumLabels' => fn (): array => $this->enumLabels(),
        ];
    }

    /**
     * The label of each case of the domain enums, keyed by the enum's name
     * and the case's value: the frontend shows the text the backend resolves
     * from the language file, never a copy of its own (0022, item 4).
     *
     * @return array<string, array<string, string>>
     */
    private function enumLabels(): array
    {
        $labels = [];

        foreach (self::ENUMS as $enum) {
            $labels[class_basename($enum)] = array_column($enum::options(), 'label', 'value');
        }

        return $labels;
    }

    /**
     * Read the JSON translation lines of the active locale.
     *
     * These are the lines the React components look up through `t()`. The
     * fallback locale needs no file: `t()` falls back to the key, which is
     * the English string.
     *
     * @return array<string, string>
     */
    private function translations(): array
    {
        $path = lang_path(app()->getLocale().'.json');

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string> */
        return json_decode(File::get($path), associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
