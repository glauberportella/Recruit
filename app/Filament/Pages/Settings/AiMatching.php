<?php

namespace App\Filament\Pages\Settings;

use App\Jobs\GenerateAllEmbeddings;
use App\Models\Embedding;
use App\Settings\AiMatchingSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class AiMatching extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $view = 'filament.pages.settings.ai-matching';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Settings';

    protected ?string $heading = '';

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.ai_matching');
    }

    public array $state = [];

    public function mount(AiMatchingSettings $setting): void
    {
        $this->state = [
            'ai_provider' => $setting->ai_provider ?? 'openai',
            'openai_api_key' => $setting->openai_api_key ?? '',
            'openai_model' => $setting->openai_model ?? 'gpt-4o-mini',
            'skills_weight' => $setting->skills_weight,
            'experience_weight' => $setting->experience_weight,
            'education_weight' => $setting->education_weight,
            'salary_weight' => $setting->salary_weight,
            'minimum_match_score' => $setting->minimum_match_score,
            'auto_match_enabled' => $setting->auto_match_enabled,
        ];
    }

    public function saveSettings(AiMatchingSettings $setting): void
    {
        Validator::make($this->state, [
            'ai_provider' => ['required', 'string', 'in:openai'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'openai_model' => ['required', 'string', 'max:100'],
            'skills_weight' => ['required', 'numeric', 'min:0', 'max:1'],
            'experience_weight' => ['required', 'numeric', 'min:0', 'max:1'],
            'education_weight' => ['required', 'numeric', 'min:0', 'max:1'],
            'salary_weight' => ['required', 'numeric', 'min:0', 'max:1'],
            'minimum_match_score' => ['required', 'numeric', 'min:0', 'max:100'],
        ])->validateWithBag('saveSettings');

        $totalWeight = $this->state['skills_weight'] + $this->state['experience_weight']
            + $this->state['education_weight'] + $this->state['salary_weight'];

        if (abs($totalWeight - 1.0) > 0.01) {
            Notification::make()
                ->title(__('admin.settings.validation_error'))
                ->danger()
                ->body(__('admin.settings.weights_must_equal_one', ['total' => number_format($totalWeight, 2)]))
                ->send();

            return;
        }

        $setting->ai_provider = $this->state['ai_provider'];
        $setting->openai_api_key = $this->state['openai_api_key'];
        $setting->openai_model = $this->state['openai_model'];
        $setting->skills_weight = (float) $this->state['skills_weight'];
        $setting->experience_weight = (float) $this->state['experience_weight'];
        $setting->education_weight = (float) $this->state['education_weight'];
        $setting->salary_weight = (float) $this->state['salary_weight'];
        $setting->minimum_match_score = (float) $this->state['minimum_match_score'];
        $setting->auto_match_enabled = (bool) $this->state['auto_match_enabled'];
        $setting->save();

        Notification::make()
            ->title(__('admin.settings.ai_settings_updated'))
            ->success()
            ->body(__('admin.settings.ai_settings_updated_body'))
            ->send();
    }

    public function generateEmbeddings(): void
    {
        if (empty($this->state['openai_api_key'])) {
            Notification::make()
                ->title(__('admin.settings.api_key_required'))
                ->danger()
                ->body(__('admin.settings.api_key_required_body'))
                ->send();

            return;
        }

        GenerateAllEmbeddings::dispatch();

        Notification::make()
            ->title(__('admin.settings.embedding_started'))
            ->success()
            ->body(__('admin.settings.embedding_started_body'))
            ->send();
    }

    public function getEmbeddingStatsProperty(): array
    {
        try {
            return [
                'candidates' => Embedding::where('embeddable_type', \App\Models\Candidates::class)->count(),
                'jobs' => Embedding::where('embeddable_type', \App\Models\JobOpenings::class)->count(),
                'total' => Embedding::count(),
            ];
        } catch (\Throwable) {
            return ['candidates' => 0, 'jobs' => 0, 'total' => 0];
        }
    }
}
