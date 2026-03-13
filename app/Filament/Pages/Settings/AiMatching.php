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

    protected static ?string $navigationLabel = 'AI Matching';

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
                ->title('Validation Error')
                ->danger()
                ->body('The sum of all weights must equal 1.0. Current total: ' . number_format($totalWeight, 2))
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
            ->title('AI Matching settings updated')
            ->success()
            ->body('Your AI Matching configuration has been saved successfully.')
            ->send();
    }

    public function generateEmbeddings(): void
    {
        if (empty($this->state['openai_api_key'])) {
            Notification::make()
                ->title('API Key Required')
                ->danger()
                ->body('Please configure your OpenAI API key first.')
                ->send();

            return;
        }

        GenerateAllEmbeddings::dispatch();

        Notification::make()
            ->title('Embedding Generation Started')
            ->success()
            ->body('Vector embeddings are being generated in the background for all candidates and job openings.')
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
