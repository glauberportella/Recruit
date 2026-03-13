<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ai_matching.ai_provider', 'openai');
        $this->migrator->add('ai_matching.openai_api_key', '');
        $this->migrator->add('ai_matching.openai_model', 'gpt-4o-mini');
        $this->migrator->add('ai_matching.skills_weight', 0.40);
        $this->migrator->add('ai_matching.experience_weight', 0.25);
        $this->migrator->add('ai_matching.education_weight', 0.15);
        $this->migrator->add('ai_matching.salary_weight', 0.20);
        $this->migrator->add('ai_matching.minimum_match_score', 50.0);
        $this->migrator->add('ai_matching.auto_match_enabled', false);
    }
};
