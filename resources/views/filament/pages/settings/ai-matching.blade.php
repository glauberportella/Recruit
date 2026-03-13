<x-filament-panels::page>
    <x-grid-section md="1">
        <x-slot name="title">
            AI Matching Configuration
        </x-slot>

        <x-slot name="description">
            Configure AI-powered candidate-job matching settings, API keys, and scoring weights.
        </x-slot>

        <x-filament::section>
            <x-filament-panels::form wire:submit="saveSettings">

                {{-- AI Provider --}}
                <x-filament-forms::field-wrapper id="ai_provider" statePath="ai_provider" required="required" label="AI Provider">
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="ai_provider" wire:model="state.ai_provider">
                            <option value="openai">OpenAI</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                {{-- API Key --}}
                <x-filament-forms::field-wrapper id="openai_api_key" statePath="openai_api_key" label="OpenAI API Key">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="openai_api_key" type="password" maxLength="255" wire:model="state.openai_api_key" placeholder="sk-..." />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                {{-- Model --}}
                <x-filament-forms::field-wrapper id="openai_model" statePath="openai_model" required="required" label="OpenAI Model">
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="openai_model" wire:model="state.openai_model">
                            <option value="gpt-4o-mini">GPT-4o Mini (Recommended)</option>
                            <option value="gpt-4o">GPT-4o</option>
                            <option value="gpt-4-turbo">GPT-4 Turbo</option>
                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Scoring Weights</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">The sum of all weights must equal 1.0</p>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Skills Weight --}}
                    <x-filament-forms::field-wrapper id="skills_weight" statePath="skills_weight" required="required" label="Skills Weight">
                        <x-filament::input.wrapper class="overflow-hidden">
                            <x-filament::input id="skills_weight" type="number" step="0.05" min="0" max="1" required="required" wire:model="state.skills_weight" />
                        </x-filament::input.wrapper>
                    </x-filament-forms::field-wrapper>

                    {{-- Experience Weight --}}
                    <x-filament-forms::field-wrapper id="experience_weight" statePath="experience_weight" required="required" label="Experience Weight">
                        <x-filament::input.wrapper class="overflow-hidden">
                            <x-filament::input id="experience_weight" type="number" step="0.05" min="0" max="1" required="required" wire:model="state.experience_weight" />
                        </x-filament::input.wrapper>
                    </x-filament-forms::field-wrapper>

                    {{-- Education Weight --}}
                    <x-filament-forms::field-wrapper id="education_weight" statePath="education_weight" required="required" label="Education Weight">
                        <x-filament::input.wrapper class="overflow-hidden">
                            <x-filament::input id="education_weight" type="number" step="0.05" min="0" max="1" required="required" wire:model="state.education_weight" />
                        </x-filament::input.wrapper>
                    </x-filament-forms::field-wrapper>

                    {{-- Salary Weight --}}
                    <x-filament-forms::field-wrapper id="salary_weight" statePath="salary_weight" required="required" label="Salary Weight">
                        <x-filament::input.wrapper class="overflow-hidden">
                            <x-filament::input id="salary_weight" type="number" step="0.05" min="0" max="1" required="required" wire:model="state.salary_weight" />
                        </x-filament::input.wrapper>
                    </x-filament-forms::field-wrapper>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">General Settings</h3>

                {{-- Minimum Match Score --}}
                <x-filament-forms::field-wrapper id="minimum_match_score" statePath="minimum_match_score" required="required" label="Minimum Match Score (%)">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="minimum_match_score" type="number" step="1" min="0" max="100" required="required" wire:model="state.minimum_match_score" />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                {{-- Auto Match Enabled --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="auto_match_enabled" wire:model="state.auto_match_enabled" class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700" />
                    <label for="auto_match_enabled" class="text-sm font-medium text-gray-950 dark:text-white">
                        Enable automatic matching when new candidates apply
                    </label>
                </div>

                <div class="text-left pt-4">
                    <x-filament::button icon="iconpark-send" icon-position="before" tooltip="Save AI Matching Settings" type="submit">
                        <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                        <span wire:loading wire:target="saveSettings">Saving...</span>
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </x-filament::section>
    </x-grid-section>

    {{-- Vector Embeddings Section --}}
    <x-grid-section md="1">
        <x-slot name="title">
            Vector Embeddings (pgvector)
        </x-slot>

        <x-slot name="description">
            Generate and manage vector embeddings for semantic matching. Embeddings enable fast similarity search between candidates and job openings.
        </x-slot>

        <x-filament::section>
            <div class="space-y-4">
                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $this->embeddingStats['candidates'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Candidate Embeddings</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $this->embeddingStats['jobs'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Job Embeddings</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ $this->embeddingStats['total'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Embeddings</div>
                    </div>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Embeddings are automatically generated when candidates or jobs are created/updated. Use the button below to generate/update embeddings for all existing records.
                </p>

                <div class="text-left">
                    <x-filament::button wire:click="generateEmbeddings" icon="heroicon-o-arrow-path" color="info">
                        <span wire:loading.remove wire:target="generateEmbeddings">Generate All Embeddings</span>
                        <span wire:loading wire:target="generateEmbeddings">Dispatching...</span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </x-grid-section>
</x-filament-panels::page>
