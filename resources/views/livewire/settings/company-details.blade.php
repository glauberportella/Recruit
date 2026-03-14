<div>
    <x-grid-section md="2">
        <x-slot name="title">
            {{ __('admin.settings.update_company_details') }}
        </x-slot>

        <x-slot name="description">
            {{ __('admin.settings.update_company_desc') }}
        </x-slot>
        <x-filament::section>
            <x-filament-panels::form wire:submit="saveSettings">
                <x-filament-forms::field-wrapper id="site_name" statePath="site_name" required="required" label="{{ __('admin.settings.site_name') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="site_name" type="text" maxLength="255" required="required" wire:model="state.site_name"/>
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="company_name" statePath="company_name" required="required" label="{{ __('admin.settings.company_name') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="company_name" type="text" maxLength="255" required="required" wire:model="state.company_name"/>
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="website" statePath="website" required="required" label="{{ __('admin.settings.company_website') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="website" type="text" maxLength="255" required="required" wire:model="state.website"/>
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="email" statePath="email" required="required" label="{{ __('admin.settings.primary_contact_email') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="email" type="email" maxLength="255" required="required" wire:model="state.email"  />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="employee_count" statePath="employee_count" required="required" label="{{ __('admin.settings.employee_count') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="employee_count" type="number" maxLength="255" required="required" wire:model="state.employee_count" />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="city" statePath="city" required="required" label="{{ __('messages.city') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="city" type="text" maxLength="255" required="required" wire:model="state.city" />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="state" statePath="state" required="required" label="{{ __('messages.state') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="state" type="text" maxLength="255" required="required" wire:model="state.state"/>
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>

                <x-filament-forms::field-wrapper id="country" statePath="country" required="required" label="{{ __('messages.country') }}">
                    <x-filament::input.wrapper class="overflow-hidden">
                        <x-filament::input id="country" type="text" maxLength="255" required="required" wire:model="state.country" />
                    </x-filament::input.wrapper>
                </x-filament-forms::field-wrapper>
                <div class="text-left">
                    <x-filament::button icon="iconpark-send"  icon-position="before" tooltip="{{ __('admin.settings.update_company_details') }}" type="submit">
                        <span wire:loading.remove wire:target="saveSettings">{{ __('messages.update') }}</span>
                        <span wire:loading wire:target="saveSettings">{{ __('messages.updating') }}</span>
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </x-filament::section>
    </x-grid-section>
    <x-section-border />
</div>
