<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AiMatchingSettings extends Settings
{
    public ?string $ai_provider;

    public ?string $openai_api_key;

    public ?string $openai_model;

    public float $skills_weight;

    public float $experience_weight;

    public float $education_weight;

    public float $salary_weight;

    public float $minimum_match_score;

    public bool $auto_match_enabled;

    public static function group(): string
    {
        return 'ai_matching';
    }
}
