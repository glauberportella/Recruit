<x-filament-panels::page>
    <x-grid-section md="1">
        <x-slot name="title">
            Jitsi Meet Configuration
        </x-slot>

        <x-slot name="description">
            Configure Jitsi Meet for online video interviews. Jitsi is used to conduct interviews directly within the platform.
        </x-slot>

        <x-filament::section>
            <x-filament-panels::form wire:submit="saveSettings">

                {{-- Jitsi Domain --}}
                <x-filament-forms::field-wrapper id="jitsi_domain" statePath="jitsi_domain" required="required" label="Jitsi Domain">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="jitsi_domain" type="text" maxLength="255" wire:model="state.jitsi_domain" placeholder="localhost or meet.yourdomain.com" />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The domain where your Jitsi Meet instance is running (e.g., localhost for Docker, or your self-hosted domain).</p>
                </x-filament-forms::field-wrapper>

                {{-- Public URL --}}
                <x-filament-forms::field-wrapper id="jitsi_public_url" statePath="jitsi_public_url" required="required" label="Public URL">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="jitsi_public_url" type="url" maxLength="500" wire:model="state.jitsi_public_url" placeholder="https://localhost:8443" />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The public URL that participants will use to join meetings.</p>
                </x-filament-forms::field-wrapper>

                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">JWT Authentication</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">JWT tokens are used to authenticate meeting participants and control access.</p>

                {{-- JWT App ID --}}
                <x-filament-forms::field-wrapper id="jwt_app_id" statePath="jwt_app_id" required="required" label="JWT App ID">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="jwt_app_id" type="text" maxLength="100" wire:model="state.jwt_app_id" placeholder="recruit" />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                {{-- JWT App Secret --}}
                <x-filament-forms::field-wrapper id="jwt_app_secret" statePath="jwt_app_secret" label="JWT App Secret">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="jwt_app_secret" type="password" maxLength="500" wire:model="state.jwt_app_secret" placeholder="Your JWT secret key" />
                    </x-filament::input.wrapper>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Must match JITSI_JWT_APP_SECRET in your Docker .env file.</p>
                </x-filament-forms::field-wrapper>

                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Meeting Defaults</h3>

                {{-- Default Meeting Duration --}}
                <x-filament-forms::field-wrapper id="default_meeting_duration" statePath="default_meeting_duration" required="required" label="Default Meeting Duration (minutes)">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="default_meeting_duration" type="number" min="15" max="480" step="15" wire:model="state.default_meeting_duration" />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Features</h3>

                {{-- Enable Recording --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="enable_recording" wire:model="state.enable_recording" class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700" />
                    <label for="enable_recording" class="text-sm font-medium text-gray-950 dark:text-white">
                        Enable meeting recording
                    </label>
                </div>

                {{-- Enable Waiting Room --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="enable_waiting_room" wire:model="state.enable_waiting_room" class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700" />
                    <label for="enable_waiting_room" class="text-sm font-medium text-gray-950 dark:text-white">
                        Enable waiting room (lobby) before joining
                    </label>
                </div>

                {{-- Enable Password Protection --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="enable_password_protection" wire:model="state.enable_password_protection" class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700" />
                    <label for="enable_password_protection" class="text-sm font-medium text-gray-950 dark:text-white">
                        Require password to join meetings
                    </label>
                </div>

                <div class="text-left pt-4">
                    <x-filament::button icon="iconpark-send" icon-position="before" tooltip="Save Jitsi Settings" type="submit">
                        <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                        <span wire:loading wire:target="saveSettings">Saving...</span>
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </x-filament::section>
    </x-grid-section>

    {{-- Connection Test Section --}}
    <x-grid-section md="1">
        <x-slot name="title">
            Connection Status
        </x-slot>

        <x-slot name="description">
            Verify your Jitsi Meet instance is accessible and properly configured.
        </x-slot>

        <x-filament::section>
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-server class="w-8 h-8 text-gray-400" />
                        <div>
                            <div class="text-sm font-medium text-gray-950 dark:text-white">Jitsi Server</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $this->state['jitsi_public_url'] ?? 'Not configured' }}</div>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Make sure the Jitsi Docker containers are running: <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-xs">docker compose up -d jitsi-web prosody jicofo jvb</code>
                </p>
            </div>
        </x-filament::section>
    </x-grid-section>
</x-filament-panels::page>
