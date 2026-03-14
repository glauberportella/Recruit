<?php

namespace App\Livewire\User;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class UpdatePasswordForm extends Component
{
    /**
     * The component's state.
     *
     * @var array<string, mixed>
     */
    public $state = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    /**
     * Update the user's password.
     */
    public function updatePassword(): void
    {
        $this->resetErrorBag();

        $this->updateUserPassword(Auth::user(), $this->state);

        if (session() !== null) {
            session()->put([
                'password_hash_'.Auth::getDefaultDriver() => Auth::user()?->getAuthPassword(),
            ]);
        }
        $this->state = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->passwordUpdated();

    }

    public function updateUserPassword(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => __('candidate.profile.password_mismatch'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }

    /**
     * Get the current user of the application.
     */
    public function getUserProperty(): ?Authenticatable
    {
        return Auth::user();
    }

    public function passwordUpdated(): void
    {
        Notification::make()
            ->title(__('candidate.profile.password_updated'))
            ->success()
            ->body(__('candidate.profile.password_updated_body'))
            ->send();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('livewire.user.update-password-form');
    }
}
