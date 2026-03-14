<?php

namespace App\Filament\Resources;

use App\Filament\Enums\JobOpeningStatus;
use App\Filament\Resources\JobOpeningsResource\Pages;
use App\Filament\Resources\JobOpeningsResource\RelationManagers;
use App\Jobs\ProcessCandidateMatching;
use App\Models\Departments;
use App\Models\JobOpenings;
use App\Models\User;
use App\Settings\JobOpeningSettings;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class JobOpeningsResource extends Resource
{
    protected static ?string $model = JobOpenings::class;

    protected static ?string $slug = 'job-openings';

    protected static ?string $recordTitleAttribute = 'postingTitle';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static array $requiredSkills = [];

    public function mount(JobOpeningSettings $setting): void
    {
        self::$requiredSkills = $setting->requiredSkills;
        parent::mount();

    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('admin.job_openings.info_section'))
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        TextInput::make('postingTitle')
                            ->maxLength(225)
                            ->required(),
                        TextInput::make('NumberOfPosition')
                            ->numeric()
                            ->required(),
                        TextInput::make('JobTitle')
                            ->maxLength(225)
                            ->required(),
                        TextInput::make('JobOpeningSystemID')
                            ->label(__('admin.job_openings.unique_key_id'))
                            ->readOnly()
                            ->hiddenOn('create'),
                        DatePicker::make('TargetDate')
                            ->label(__('admin.job_openings.target_date'))
                            ->format('d/m/Y')
                            ->native(false)
                            ->displayFormat('m/d/Y')
                            ->required(),
                        Select::make('Status')
                            ->options(JobOpeningStatus::class)
                            ->hiddenOn('create')
                            ->native(false)
                            ->default('New')
                            ->required(),
                        TextInput::make('Salary'),
                        Select::make('Department')
                            ->options(Departments::all()->pluck('DepartmentName', 'id'))
                            ->required(),
                        Select::make('HiringManager')
                            ->options(User::all()->pluck('name', 'id')),
                        Select::make('AssignedRecruiters')
                            ->options(User::all()->pluck('name', 'id')),
                        DatePicker::make('DateOpened')
                            ->label(__('admin.job_openings.date_opened'))
                            ->format('d/m/Y')
                            ->native(false)
                            ->displayFormat('m/d/Y')
                            ->required(),
                        Select::make('JobType')
                            ->options(config('recruit.job_opening.job_type_options'))
                            ->required(),
                        Select::make('RequiredSkill')
                            ->multiple()
                            ->options(self::$requiredSkills)
                            ->required(),
                        Select::make('WorkExperience')
                            ->options(config('recruit.job_opening.work_experience'))
                            ->required(),
                        Checkbox::make('RemoteJob')
                            ->inline(false)
                            ->default(false),
                    ])->columns(2),
                Section::make(__('messages.address_information'))
                    ->id('job-opening-address-information-section')
                    ->icon('heroicon-o-map')
                    ->schema([
                        TextInput::make('City')
                            ->required(),
                        TextInput::make('Country')
                            ->required(),
                        TextInput::make('State')
                            ->label(__('messages.state'))
                            ->required(),
                        TextInput::make('ZipCode')
                            ->label(__('messages.zip_code'))
                            ->required(),
                    ])->columns(2),
                Section::make(__('messages.description_information'))
                    ->id('job-opening-description-information')
                    ->icon('heroicon-o-briefcase')
                    ->label(__('messages.description_information'))
                    ->schema([
                        RichEditor::make('JobDescription')
                            ->label(__('admin.job_openings.job_description'))
                            ->required(),
                        RichEditor::make('JobRequirement')
                            ->label(__('admin.job_openings.requirements'))
                            ->required(),
                        RichEditor::make('JobBenefits')
                            ->label(__('admin.job_openings.benefits'))
                            ->required(),
                        RichEditor::make('AdditionalNotes')
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('admin.job_openings.additional_notes_hint'))
                            ->label(__('admin.job_openings.additional_notes'))
                            ->nullable(),
                    ])->columns(1),
                Section::make(__('messages.system_information'))
                    ->hiddenOn(['create', 'edit'])
                    ->id('job-opening-system-info')
                    ->icon('heroicon-o-computer-desktop')
                    ->label(__('messages.system_information'))
                    ->schema([
                        TextInput::make('CreatedBy'),
                        TextInput::make('ModifiedBy'),
                        TextInput::make('created_at')
                            ->label(__('messages.created_at')),
                        TextInput::make('updated_at')
                            ->label(__('messages.updated_at')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('postingTitle')
                    ->label(__('admin.job_openings.job_title_name')),
                TextColumn::make('NumberOfPosition')
                    ->label(__('admin.job_openings.num_vacancy')),
                TextColumn::make('TargetDate')
                    ->label(__('admin.job_openings.target_date')),
                TextColumn::make('DateOpened')
                    ->label(__('admin.job_openings.date_opened')),
                TextColumn::make('JobType')
                    ->label(__('admin.job_openings.job_type')),
                IconColumn::make('RemoteJob')
                    ->label(__('messages.remote'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge'),
            ])->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-m-plus-small'),
            ])
            ->actions([
                Tables\Actions\Action::make('ai_match_candidates')
                    ->label(__('admin.job_openings.ai_match'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.job_openings.run_ai_match_all'))
                    ->modalDescription(__('admin.job_openings.ai_match_modal_desc'))
                    ->action(function (JobOpenings $record) {
                        ProcessCandidateMatching::dispatch($record->id);

                        Notification::make()->title(__('admin.job_openings.ai_match_queued'))->success()
                            ->body(__('admin.job_openings.ai_match_queued_body'))->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('unpublished')
                        ->tooltip(__('admin.job_openings.unpublished_tooltip'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->label(__('admin.job_openings.unpublished'))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->published_career_site = 0;
                                $record->save();
                            }
                            Notification::make()
                                ->body(__('admin.job_openings.unpublished_body'))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('published')
                        ->label(__('admin.job_openings.publish'))
                        ->icon('heroicon-o-arrow-uturn-up')
                        ->tooltip(__('admin.job_openings.publish_tooltip'))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->published_career_site = 1;
                                $record->save();
                            }
                            Notification::make()
                                ->body(__('admin.job_openings.published_body'))
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon('heroicon-o-globe-alt')
                    ->label(__('admin.job_openings.publish_unpublish')),
                Tables\Actions\BulkAction::make('change_status')
                    ->label(__('admin.job_openings.update_status'))
                    ->icon('heroicon-o-pencil-square')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->form([
                        Select::make('Status')
                            ->options(JobOpeningStatus::class)
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        foreach ($records as $record) {
                            $record->Status = $data['Status'];
                            $record->save();
                        }
                        Notification::make()
                            ->body(__('admin.job_openings.status_updated_body', ['status' => $data['Status']]))
                            ->success()
                            ->send();
                    }),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobOpenings::route('/'),
            'create' => Pages\CreateJobOpenings::route('/create'),
            'view' => Pages\ViewJobOpenings::route('/{record}'),
            'edit' => Pages\EditJobOpenings::route('/{record}/edit'),
            'ai-candidates' => Pages\AiTopCandidates::route('/{record}/ai-candidates'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
