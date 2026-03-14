<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidatesProfileResource\Pages;
use App\Filament\Resources\CandidatesProfileResource\RelationManagers;
use App\Jobs\ProcessCandidateJobSuggestions;
use App\Models\Candidates;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class CandidatesProfileResource extends Resource
{
    protected static ?string $model = Candidates::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(array_merge(
                self::standardBasicInformationFormLayout(),
                self::standardSkillSetFormLayout(),
                self::standardCurrentJobInformationFormLayout(),
                self::standardAddressInformationFormLayout(),
                self::standardSchoolInformationFormLayout(),
                self::standardCandidatesExperienceFormLayout(),
                self::standardAdditionalInformationFormLayout(),
                []
            ));
    }

    private static function standardSchoolInformationFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.candidates.degree_info'))
                ->schema([
                    Forms\Components\Repeater::make('School')
                        ->label('')
                        ->addActionLabel(__('admin.candidates.add_degree'))
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
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation(),
                        )
                        ->columns(4),
                ]),
        ];
    }

    private static function standardBasicInformationFormLayout(): array
    {
        return [
            Forms\Components\TextInput::make('CandidateId')
                ->readOnly()
                ->disabled()
                ->visibleOn('view'),
            Forms\Components\Section::make(__('admin.candidates.basic_info'))
                ->schema([
                    Forms\Components\TextInput::make('FirstName')
                        ->label(__('messages.first_name')),
                    Forms\Components\TextInput::make('LastName')
                        ->label(__('messages.last_name')),
                    Forms\Components\TextInput::make('Mobile')
                        ->label(__('messages.mobile'))
                        ->tel(),
                    Forms\Components\TextInput::make('email')
                        ->required(),
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
                        ->mask(RawJs::make(<<<'JS'
                                $money($input, '.',',')
                                JS))
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

    public static function standardSkillSetFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.candidates.skill_set_info'))
                ->schema([
                    Forms\Components\Repeater::make('SkillSet')
                        ->label('')
                        ->addActionLabel(__('admin.candidates.add_skill_set'))
                        ->columns(4)
                        ->schema([
                            Forms\Components\TextInput::make('skill')
                                ->label(__('admin.candidates.skill')),
                            Forms\Components\Select::make('proficiency')
                                ->options([
                                    'Master' => __('enums.proficiency.master'),
                                    'Intermediate' => __('enums.proficiency.intermediate'),
                                    'Beginner' => __('enums.proficiency.beginner'),
                                ])
                                ->label(__('admin.candidates.proficiency')),
                            Forms\Components\Select::make('experience')
                                ->options([
                                    '1year' => __('enums.experience_years.1year'),
                                    '2year' => __('enums.experience_years.2years'),
                                    '3year' => __('enums.experience_years.3years'),
                                    '4year' => __('enums.experience_years.4years'),
                                    '5year' => __('enums.experience_years.5years'),
                                    '6year' => __('enums.experience_years.6years'),
                                    '7year' => __('enums.experience_years.7years'),
                                    '8year' => __('enums.experience_years.8years'),
                                    '9year' => __('enums.experience_years.9years'),
                                    '10year+' => __('enums.experience_years.10years_plus'),
                                ])
                                ->label(__('admin.candidates.experience')),
                            Forms\Components\Select::make('last_used')
                                ->options(function () {
                                    $lastUsedOptions = [];
                                    $counter = 30;
                                    for ($i = $counter; $i >= 0; $i--) {
                                        $lastUsedOptions[
                                        sprintf('%s', Carbon::now()->subYear($i)->year)
                                            ] =
                                            sprintf('%s', Carbon::now()->subYear($i)->year);
                                    }

                                    return $lastUsedOptions;
                                })
                                ->label(__('admin.candidates.last_used')),

                        ]),
                ]),

        ];

    }

    public static function standardCurrentJobInformationFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.candidates.current_job_info'))
                ->schema([
                    Forms\Components\TextInput::make('CurrentEmployer')
                        ->label(__('admin.job_candidates.current_employer')),
                    Forms\Components\TextInput::make('CurrentJobTitle')
                        ->label(__('admin.job_candidates.current_job_title')),
                    Forms\Components\TextInput::make('CurrentSalary')
                        ->label(__('admin.job_candidates.current_salary'))
                        ->mask(RawJs::make(<<<'JS'
                                $money($input, '.',',')
                                JS)),
                ])->columns(2),
        ];
    }

    public static function standardAddressInformationFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.candidates.address_info'))
                ->schema([
                    Forms\Components\TextInput::make('Street'),
                    Forms\Components\TextInput::make('City'),
                    Forms\Components\TextInput::make('Country'),
                    Forms\Components\TextInput::make('ZipCode'),
                    Forms\Components\TextInput::make('State'),
                ])->columns(2),
        ];
    }

    public static function standardCandidatesExperienceFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('admin.candidates.experience_info'))
                ->schema([
                    Forms\Components\Repeater::make('ExperienceDetails')
                        ->label('')
                        ->addActionLabel(__('admin.candidates.add_experience'))
                        ->schema([
                            Forms\Components\Checkbox::make('current')
                                ->label(__('admin.candidates.current_question'))
                                ->inline(false),
                            Forms\Components\TextInput::make('company_name'),
                            Forms\Components\TextInput::make('duration'),
                            Forms\Components\TextInput::make('role'),
                            Forms\Components\Textarea::make('company_address'),
                        ])
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation(),
                        )
                        ->columns(5),
                ]),
        ];
    }

    public static function standardAdditionalInformationFormLayout(): array
    {
        return [
            Forms\Components\Section::make(__('messages.additional_information'))
                ->schema([
                    Forms\Components\Textarea::make('AdditionalInformation')
                        ->label(''),
                ]),

        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('ai_suggest_jobs')
                    ->label(__('admin.candidates.ai_suggest_jobs'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.candidates.ai_job_suggestions'))
                    ->modalDescription(__('admin.candidates.ai_job_suggestions_desc'))
                    ->action(function (Candidates $record) {
                        ProcessCandidateJobSuggestions::dispatch($record->id);

                        Notification::make()->title(__('admin.candidates.ai_match_queued'))->success()
                            ->body(__('admin.candidates.ai_match_queued_body'))->send();
                    }),
                Tables\Actions\Action::make('view_suggested_jobs')
                    ->label(__('admin.candidates.suggested_jobs'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->url(fn (Candidates $record) => CandidatesProfileResource::getUrl('ai-suggested-jobs', ['record' => $record]))
                    ->visible(fn (Candidates $record) => $record->matchScores()->exists()),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidatesProfiles::route('/'),
            'create' => Pages\CreateCandidatesProfile::route('/create'),
            'view' => Pages\ViewCandidatesProfile::route('/{record}'),
            'edit' => Pages\EditCandidatesProfile::route('/{record}/edit'),
            'ai-suggested-jobs' => Pages\AiSuggestedJobs::route('/{record}/ai-suggested-jobs'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
