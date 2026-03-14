<?php

namespace App\Filament\Resources;

use App\Filament\Enums\JobCandidateStatus;
use App\Filament\Enums\InterviewStatus;
use App\Filament\Resources\JobCandidatesResource\Pages;
use App\Filament\Resources\JobCandidatesResource\RelationManagers;
use App\Jobs\ProcessCandidateMatching;
use App\Models\CandidateMatchScore;
use App\Models\Candidates;
use App\Models\Interview;
use App\Models\JobCandidates;
use App\Models\JobOpenings;
use App\Models\User;
use App\Notifications\Candidates\InterviewScheduledNotification;
use App\Services\AI\CandidateMatchingService;
use App\Settings\JitsiSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class JobCandidatesResource extends Resource
{
    protected static ?string $model = JobCandidates::class;

    protected static ?string $recordTitleAttribute = 'job.postingTitle';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'vaadin-diploma-scroll';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                array_merge([],
                    self::candidatePipelineFormLayout(),
                    self::candidateBasicInformationFormLayout(),
                    self::candidateCurrentJobInformationFormLayout(),
                    self::candidateAddressInformationFormLayout()
                ));
    }

    public static function candidatePipelineFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.job_candidates.pipeline'))
                ->schema([
                    Forms\Components\Select::make('JobId')
                        ->label(__('admin.job_candidates.job_associated'))
                        ->options(JobOpenings::all()->pluck('JobTitle', 'id'))
                        ->required(),
                    Forms\Components\Select::make('CandidateStatus')
                        ->label(__('admin.job_candidates.candidate_status'))
                        ->options(JobCandidateStatus::class)
                        ->required(),
                    Forms\Components\TextInput::make('CandidateSource')
                        ->nullable('')
                        ->default('web'),
                    Forms\Components\Select::make('CandidateOwner')
                        ->label(__('admin.job_candidates.candidate_owner'))
                        ->options(User::all()->pluck('name', 'id'))
                        ->nullable(),
                ])->columns(2),
        ];
    }

    public static function candidateBasicInformationFormLayout(): array
    {
        return [
            Forms\Components\TextInput::make('JobCandidateId')
                ->visibleOn('view')
                ->readOnly()
                ->disabled(),
            Forms\Components\Section::make(__('admin.job_candidates.basic_info'))
                ->schema([
                    Forms\Components\Select::make('candidate')
                        ->options(Candidates::all()->pluck('full_name', 'id'))
                        ->required(),
                    Forms\Components\TextInput::make('mobile')
                        ->nullable(),
                    Forms\Components\TextInput::make('Email')
                        ->required()
                        ->email(),
                    Forms\Components\Select::make('ExperienceInYears')
                        ->label(__('admin.job_candidates.experience_years'))
                        ->options([
                            '1year' => __('enums.experience_years.1year'),
                            '2years' => __('enums.experience_years.2years'),
                            '3years' => __('enums.experience_years.3years'),
                            '4years' => __('enums.experience_years.4years'),
                            '5years+' => __('enums.experience_years.5years_plus'),
                        ]),
                    Forms\Components\TextInput::make('ExpectedSalary')
                        ->label(__('admin.job_candidates.expected_salary')),
                    Forms\Components\Select::make('HighestQualificationHeld')
                        ->options([
                            'Secondary/High School' => __('enums.qualification.secondary'),
                            'Associates Degree' => __('enums.qualification.associates'),
                            'Bachelors Degree' => __('enums.qualification.bachelors'),
                            'Masters Degree' => __('enums.qualification.masters'),
                            'Doctorate Degree' => __('enums.qualification.doctorate'),
                        ])
                        ->label(__('admin.job_candidates.highest_qualification')),
                ])->columns(2),
        ];
    }

    public static function candidateCurrentJobInformationFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.job_candidates.current_job_info'))
                ->schema([
                    Forms\Components\TextInput::make('CurrentEmployer')
                        ->label(__('admin.job_candidates.current_employer')),
                    Forms\Components\TextInput::make('CurrentJobTitle')
                        ->label(__('admin.job_candidates.current_job_title')),
                    Forms\Components\TextInput::make('CurrentSalary')
                        ->label(__('admin.job_candidates.current_salary')),
                ])->columns(2),
        ];
    }

    public static function candidateAddressInformationFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.job_candidates.address_info'))
                ->schema([
                    Forms\Components\TextInput::make('Street'),
                    Forms\Components\TextInput::make('City'),
                    Forms\Components\TextInput::make('Country'),
                    Forms\Components\TextInput::make('ZipCode'),
                    Forms\Components\TextInput::make('State'),
                ])->columns(2),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidateProfile.full_name')
                    ->label(__('admin.job_candidates.candidate_name')),
                Tables\Columns\TextColumn::make('Email'),
                Tables\Columns\TextColumn::make('CandidateStatus')
                    ->label(__('admin.job_candidates.candidate_status')),
                Tables\Columns\TextColumn::make('CandidateSource')
                    ->label(__('admin.job_candidates.candidate_source'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('recordOwner.name')
                    ->label(__('admin.job_candidates.candidate_owner'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mobile')
                    ->label(__('messages.mobile'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ExpectedSalary')
                    ->label(__('admin.job_candidates.expected_salary'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ExperienceInYears')
                    ->label(__('admin.job_candidates.experience_years'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('HighestQualificationHeld')
                    ->label(__('admin.job_candidates.highest_qualification'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('CurrentEmployer')
                    ->label(__('admin.job_candidates.current_employer_company'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('CurrentJobTitle')
                    ->label(__('admin.job_candidates.current_job_title'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('CurrentSalary')
                    ->label(__('admin.job_candidates.current_salary'))
                    ->money(config('recruit.currency_field'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Street')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('City')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Country')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ZipCode')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('State')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ai_match_score')
                    ->label(__('admin.job_candidates.ai_match_percent'))
                    ->getStateUsing(function (JobCandidates $record): ?string {
                        $match = CandidateMatchScore::where('candidate_id', $record->candidate)
                            ->where('job_opening_id', $record->JobId)
                            ->first();

                        return $match ? number_format($match->overall_score, 1) . '%' : '—';
                    })
                    ->badge()
                    ->color(fn (JobCandidates $record): string => self::getMatchScoreColor($record))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw(
                            "(SELECT overall_score FROM candidate_match_scores WHERE candidate_match_scores.candidate_id = job_candidates.candidate AND candidate_match_scores.job_opening_id = job_candidates.JobId LIMIT 1) {$direction}"
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('ai_match')
                    ->label(__('admin.job_candidates.ai_match'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.job_candidates.run_ai_match'))
                    ->modalDescription(__('admin.job_candidates.ai_match_modal_desc'))
                    ->action(function (JobCandidates $record) {
                        if (! $record->candidate || ! $record->JobId) {
                            Notification::make()->title(__('admin.job_candidates.missing_data'))->danger()
                                ->body(__('admin.job_candidates.missing_data_body'))->send();

                            return;
                        }

                        $candidate = Candidates::find($record->candidate);
                        $job = JobOpenings::find($record->JobId);

                        if (! $candidate || ! $job) {
                            Notification::make()->title(__('admin.job_candidates.not_found'))->danger()
                                ->body(__('admin.job_candidates.not_found_body'))->send();

                            return;
                        }

                        try {
                            $service = app(CandidateMatchingService::class);
                            $result = $service->matchCandidateToJob($candidate, $job);

                            Notification::make()->title(__('admin.job_candidates.ai_match_complete'))->success()
                                ->body(__('admin.job_candidates.ai_match_score_body', ['score' => $result->overall_score]))->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title(__('admin.job_candidates.ai_match_failed'))->danger()
                                ->body($e->getMessage())->send();
                        }
                    }),
                Tables\Actions\Action::make('view_match_details')
                    ->label(__('admin.job_candidates.match_details'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->modalHeading(__('admin.job_candidates.ai_match_score_details'))
                    ->modalContent(function (JobCandidates $record) {
                        $match = CandidateMatchScore::where('candidate_id', $record->candidate)
                            ->where('job_opening_id', $record->JobId)
                            ->first();

                        return view('filament.components.match-score-details', ['match' => $match]);
                    })
                    ->modalSubmitAction(false)
                    ->visible(function (JobCandidates $record): bool {
                        return CandidateMatchScore::where('candidate_id', $record->candidate)
                            ->where('job_opening_id', $record->JobId)
                            ->exists();
                    }),
                Tables\Actions\Action::make('schedule_interview')
                    ->label(__('admin.job_candidates.schedule_interview'))
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->default(fn (JobCandidates $record) => 'Interview - ' . ($record->candidateProfile?->FirstName ?? '') . ' ' . ($record->candidateProfile?->LastName ?? '')),
                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label(__('admin.job_candidates.date_time'))
                            ->required()
                            ->native(false)
                            ->minutesStep(15)
                            ->default(now()->addDay()->setHour(10)->setMinute(0)),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label(__('admin.job_candidates.duration_minutes'))
                            ->numeric()
                            ->required()
                            ->default(fn () => app(JitsiSettings::class)->default_meeting_duration ?? 60)
                            ->minValue(15)
                            ->maxValue(480),
                    ])
                    ->action(function (JobCandidates $record, array $data) {
                        $interview = Interview::create([
                            'job_candidate_id' => $record->id,
                            'scheduled_by' => auth()->id(),
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                            'scheduled_at' => $data['scheduled_at'],
                            'duration_minutes' => $data['duration_minutes'],
                            'status' => InterviewStatus::Scheduled->value,
                        ]);

                        $record->update(['CandidateStatus' => JobCandidateStatus::InterviewScheduled->value]);

                        $candidate = $record->candidateProfile;
                        if ($candidate) {
                            $candidate->notify(new InterviewScheduledNotification($interview));
                        }

                        Notification::make()
                            ->title(__('admin.job_candidates.interview_scheduled'))
                            ->success()
                            ->body(__('admin.job_candidates.interview_scheduled_body', ['date' => $interview->scheduled_at->format('M d, Y H:i')]))
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_ai_match')
                        ->label(__('admin.job_candidates.ai_match_selected'))
                        ->icon('heroicon-o-cpu-chip')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading(__('admin.job_candidates.run_ai_match_selected'))
                        ->modalDescription(__('admin.job_candidates.run_ai_match_selected_desc'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $queued = 0;
                            foreach ($records as $record) {
                                if ($record->candidate && $record->JobId) {
                                    ProcessCandidateMatching::dispatch($record->JobId, $record->candidate);
                                    $queued++;
                                }
                            }

                            Notification::make()->title(__('admin.job_candidates.ai_match_queued'))->success()
                                ->body(__('admin.job_candidates.ai_match_queued_body', ['count' => $queued]))->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::class,
            RelationManagers\InterviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobCandidates::route('/'),
            'create' => Pages\CreateJobCandidates::route('/create'),
            'view' => Pages\ViewJobCandidates::route('/{record}'),
            'edit' => Pages\EditJobCandidates::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getMatchScoreColor(JobCandidates $record): string
    {
        $match = CandidateMatchScore::where('candidate_id', $record->candidate)
            ->where('job_opening_id', $record->JobId)
            ->first();

        if (! $match) {
            return 'gray';
        }

        return match (true) {
            $match->overall_score >= 80 => 'success',
            $match->overall_score >= 60 => 'info',
            $match->overall_score >= 40 => 'warning',
            default => 'danger',
        };
    }
}
