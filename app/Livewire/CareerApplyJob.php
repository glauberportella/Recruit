<?php

namespace App\Livewire;

use AbanoubNassem\FilamentGRecaptchaField\Forms\Components\GRecaptcha;
use Afatmustafa\FilamentTurnstile\Forms\Components\Turnstile;
use App\Filament\Enums\JobCandidateStatus;
use App\Models\Attachments;
use App\Models\Candidates;
use App\Models\JobCandidates;
use App\Models\JobOpenings;
use DominionSolutions\FilamentCaptcha\Forms\Components\Captcha;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

class CareerApplyJob extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    public ?array $data = [];

    public ?string $captcha = '';

    public string|null|JobOpenings $record = '';

    public static ?JobOpenings $jobDetails = null;

    public ?string $referenceNumber;

    public function mount($jobReferenceNumber)
    {
        // search for the job reference number, if not valid, redirect to all job
        $this->jobOpeningDetails($jobReferenceNumber);
        $this->referenceNumber = $jobReferenceNumber;
        $this->form->fill();
    }

    public function updatedReferenceNumber()
    {
        $this->jobOpeningDetails($this->referenceNumber);
    }

    private function jobOpeningDetails($reference): void
    {
        $this->record = JobOpenings::jobStillOpen()->where('JobOpeningSystemID', '=', $reference)->first();
        if (empty($this->record)) {
            // redirect back as the job opening is closed or tampered id or not existing
            Notification::make()
                ->title(__('candidate.career.job_closed'))
                ->icon('heroicon-o-x-circle')
                ->iconColor('warning')
                ->send();
            $this->redirectRoute('career.landing_page');
        }
    }

    public function create(): void
    {
        $data = $this->form->getState();

        // Create Candidate
        $candidate = Candidates::create([
            'FirstName' => $data['FirstName'],
            'LastName' => $data['LastName'],
            'Mobile' => $data['mobile'],
            'email' => $data['Email'],
            'ExperienceInYears' => $data['experience'],
            'Street' => $data['Street'],
            'City' => $data['City'],
            'Country' => $data['Country'],
            'ZipCode' => $data['ZipCode'],
            'State' => $data['State'],
            'CurrentEmployer' => $data['CurrentEmployer'],
            'CurrentJobTitle' => $data['CurrentJobTitle'],
            'School' => $data['School'],
            'ExperienceDetails' => $data['ExperienceDetails'],
        ]);

        // Job Candidates
        $job_candidates = JobCandidates::create([
            'JobId' => $this->record->id,
            'CandidateSource' => 'Career Page',
            'CandidateStatus' => JobCandidateStatus::New,
            'candidate' => $candidate->id,
            'mobile' => $data['mobile'],
            'Email' => $data['Email'],
            'ExperienceInYears' => $data['experience'],
            'CurrentJobTitle' => $data['CurrentJobTitle'],
            'CurrentEmployer' => $data['CurrentEmployer'],
            'Street' => $data['Street'],
            'City' => $data['City'],
            'Country' => $data['Country'],
            'ZipCode' => $data['ZipCode'],
            'State' => $data['State'],
        ]);

        if ($candidate && $job_candidates) {
            // Save CV attachment
            if (! empty($data['attachment'])) {
                Attachments::create([
                    'attachment' => $data['attachment'],
                    'attachmentName' => $data['attachmentName'] ?? basename($data['attachment']),
                    'category' => 'Resume',
                    'attachmentOwner' => $job_candidates->id,
                    'moduleName' => 'JobCandidates',
                ]);
            }

            Notification::make()
                ->title(__('candidate.career.application_submitted'))
                ->success()
                ->body(__('candidate.career.application_submitted_body'))
                ->send();
            Notification::make()
                ->title(__('candidate.career.reminder'))
                ->success()
                ->body(__('candidate.career.reminder_body'))
                ->send();
            $this->redirectRoute('career.landing_page');
        }

    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Wizard::make([
                    Wizard\Step::make(__('candidate.application.step_application'))
                        ->icon('heroicon-o-user')
                        ->columns(2)
                        ->schema(array_merge($this->applicationStepWizard(),
                            [Forms\Components\Grid::make(1)
                                ->columns(1)
                                ->schema($this->captchaField())]
                        )),
                    Wizard\Step::make(__('candidate.application.step_assessment'))
                        ->visible(false)
                        ->icon('heroicon-o-user')
                        ->columns(2)
                        ->schema(array_merge([], $this->assessmentStepWizard())),
                ])
                    ->nextAction(
                        fn (Action $action) => $action->view('career-form.apply-job-components.NextActionButton'),
                    )
                    ->submitAction(view('career-form.apply-job-components.SubmitApplicationButton')),
            ]);
    }

    private function assessmentStepWizard(): Wizard\Step|array
    {
        return [];
    }

    private function applicationStepWizard(): array
    {
        return
            [
                Forms\Components\Section::make(__('messages.basic_information'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('FirstName')
                            ->required()
                            ->label(__('messages.first_name')),
                        Forms\Components\TextInput::make('LastName')
                            ->required()
                            ->label(__('messages.last_name')),
                        Forms\Components\TextInput::make('mobile')
                            ->required(),
                        Forms\Components\TextInput::make('Email')
                            ->required()
                            ->email(),
                    ]),
                Forms\Components\Section::make(__('messages.address_information'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('Street'),
                        Forms\Components\TextInput::make('City'),
                        Forms\Components\TextInput::make('Country'),
                        Forms\Components\TextInput::make('ZipCode'),
                        Forms\Components\TextInput::make('State'),
                    ]),
                Forms\Components\Section::make(__('candidate.application.professional_details'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('CurrentEmployer')
                            ->label(__('candidate.application.current_employer')),
                        Forms\Components\TextInput::make('CurrentJobTitle')
                            ->label(__('candidate.application.current_job_title')),
                        Forms\Components\Select::make('experience')
                            ->options([
                                '1year' => __('enums.experience_years.1year'),
                                '2year' => __('enums.experience_years.2year'),
                                '3year' => __('enums.experience_years.3year'),
                                '4year' => __('enums.experience_years.4year'),
                                '5year' => __('enums.experience_years.5year'),
                                '6year' => __('enums.experience_years.6year'),
                                '7year' => __('enums.experience_years.7year'),
                                '8year' => __('enums.experience_years.8year'),
                                '9year' => __('enums.experience_years.9year'),
                                '10year+' => __('enums.experience_years.10year+'),
                            ])
                            ->label(__('candidate.application.experience')),
                    ]),
                Forms\Components\Section::make(__('candidate.application.educational_details'))
                    ->schema([
                        Forms\Components\Repeater::make('School')
                            ->label('')
                            ->addActionLabel(__('candidate.application.add_degree'))
                            ->schema([
                                Forms\Components\TextInput::make('school_name')
                                    ->required(),
                                Forms\Components\TextInput::make('major')
                                    ->required(),
                                Forms\Components\Select::make('duration')
                                    ->options([
                                        '4years' => __('enums.school_duration.4years'),
                                        '5years' => __('enums.school_duration.5years'),
                                    ])
                                    ->required(),
                                Forms\Components\Checkbox::make('pursuing')
                                    ->inline(false),
                            ])
                            ->deletable(true)
                            ->columns(4),
                    ]),
                Forms\Components\Section::make(__('candidate.application.experience_details'))
                    ->schema([
                        Forms\Components\Repeater::make('ExperienceDetails')
                            ->label('')
                            ->addActionLabel(__('candidate.application.add_experience'))
                            ->schema([
                                Forms\Components\Checkbox::make('current')
                                    ->label('Current?')
                                    ->inline(false),
                                Forms\Components\TextInput::make('company_name'),
                                Forms\Components\TextInput::make('duration'),
                                Forms\Components\TextInput::make('role'),
                                Forms\Components\Textarea::make('company_address'),
                            ])
                            ->deletable(true)
                            ->columns(5),
                    ]),
                Forms\Components\FileUpload::make('attachment')
                    ->preserveFilenames()
                    ->storeFileNamesIn('attachmentName')
                    ->directory('JobCandidate-attachments')
                    ->visibility('private')
                    ->openable()
                    ->downloadable()
                    ->previewable()
                    ->acceptedFileTypes([
                        'application/pdf',
                    ])
                    ->required()
                    ->columnSpanFull()
                    ->label(__('candidate.application.resume')),
            ];
    }

    private function captchaField(): array
    {
        if (! config('recruit.enable_captcha')) {
            return [];
        }
        if (config('recruit.enable_captcha')) {
            if (config('recruit.captcha_provider.default') === 'Google') {
                return [GRecaptcha::make('captcha')];
            }
            if (config('recruit.captcha_provider.default') === 'Cloudflare') {
                return [
                    Turnstile::make('turnstile')
                        ->theme('light')
                        ->size('normal')
                        ->language('en-US'),
                ];
            }

            // default
            if (config('recruit.captcha_provider.default') === 'Recruit_Captcha') {
                return [
                    Captcha::make('captcha')
                        ->rules(['captcha'])
                        ->required()
                        ->validationMessages([
                            'captcha' => __('candidate.career.captcha_mismatch'),
                        ]),
                ];
            }

        }

        return [];

    }

    #[Title('Apply Job ')]
    public function render()
    {
        return view('livewire.career-apply-job', [
            'jobDetail' => $this->record,
        ]);
    }
}
