<x-grid-section md="2">
    <x-slot name="title">
        {{ __('messages.update_password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('messages.update_password_desc') }}
    </x-slot>

    <x-filament::section>
        <x-filament-panels::form wire:submit="updatePassword">
            <x-filament-forms::field-wrapper id="current_password" statePath="current_password" required="required" label="{{ __('messages.current_password') }}">
                <x-filament::input.wrapper class="overflow-hidden">
                    <x-filament::input id="current_password" type="password" required="required" wire:model="state.current_password" autocomplete="current-password" />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper id="password" statePath="password" required="required" label="{{ __('messages.new_password') }}">
                <x-filament::input.wrapper class="overflow-hidden">
                    <x-filament::input id="password" type="password" required="required" wire:model="state.password" autocomplete="new-password" />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper id="password_confirmation" statePath="password_confirmation" required="required" label="{{ __('messages.confirm_password') }}">
                <x-filament::input.wrapper class="overflow-hidden">
                    <x-filament::input id="password_confirmation" type="password" required="required" wire:model="state.password_confirmation" autocomplete="new-password" />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>


            <div class="text-left">
                <x-filament::button type="submit">
                    <span wire:loading.remove wire:target="updatePassword">{{ __('messages.update') }}</span>
                    <span wire:loading wire:target="updatePassword">{{ __('messages.updating') }}</span>
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    </x-filament::section>
</x-grid-section>
