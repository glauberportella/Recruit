<?php

namespace App\Livewire\Portal\Invitation;

use App\Models\candidatePortalInvitation;
use App\Models\Candidates;
use App\Models\CandidateUser;
use App\Notifications\Candidates\NewCandidatePortalAccountRegisteredNotification;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Support\Colors\Color;
use Illuminate\Http\Request;
use Phpsa\FilamentPasswordReveal\Password;

class CreateCandidateUser extends SimplePage
{
    use InteractsWithActions;
    use InteractsWithFormActions;
    use InteractsWithForms;

    public candidatePortalInvitation $candidatePortalInvitation;

    protected ?string $subheading = null;

    protected static ?string $title = null;

    protected ?string $heading = '';

    public function getSubheading(): ?string
    {
        return __('candidate.invitation.candidate_portal_subtitle');
    }

    public function getTitle(): string
    {
        return __('candidate.invitation.candidate_portal_title');
    }

    public ?array $data = [];

    public static string $view = 'livewire.portal.invitation.create-candidate-user';

    protected static string $layout = 'components.layouts.simple';

    public function mount(Request $request, candidatePortalInvitation $id): void
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid Signature');
        }
        $this->candidatePortalInvitation = $id;
        if ($this->candidatePortalInvitation->joined_at) {
            abort(410, 'Link has Expired');
        }
        $this->data = [...$this->candidatePortalInvitation->toArray()];
    }

    public function create(): void
    {
        $this->form->getState();
        $candidate_user = CandidateUser::create([
            'name' => $this->candidatePortalInvitation->name,
            'email' => $this->candidatePortalInvitation->email,
            'password' => \Hash::make($this->data['password']),
            'email_verified_at' => Carbon::now(),
        ]);
        $this->candidatePortalInvitation->joined_at = Carbon::now();
        $this->candidatePortalInvitation->save();
        $candidate = Candidates::whereEmail($this->candidatePortalInvitation->email)->first();
        $candidate_user->notify(new NewCandidatePortalAccountRegisteredNotification($candidate));
        Notification::make('create_account_success')
            ->success()
            ->duration(10000)
            ->title(__('candidate.invitation.candidate_account_ready'))
            ->body(__('candidate.invitation.candidate_account_ready_body'))
            ->send();

        $this->redirect(filament()->getPanel('candidate')->getLoginUrl());

    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.name'))
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->label(__('messages.email'))
                    ->disabled(),
                Password::make('password')
                    ->minLength(5)
                    ->confirmed()
                    ->label(__('messages.password')),
                Password::make('password_confirmation')
                    ->minLength(5)
                    ->label(__('messages.confirm_password')),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('candidate.invitation.create_account'))
            ->color(Color::Gray)
            ->submit('create');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function hasLogo(): bool
    {
        return true;
    }
}
