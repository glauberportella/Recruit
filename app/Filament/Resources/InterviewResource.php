<?php

namespace App\Filament\Resources;

use App\Filament\Enums\InterviewStatus;
use App\Filament\Resources\InterviewResource\Pages;
use App\Models\Interview;
use App\Models\JobCandidates;
use App\Models\User;
use App\Notifications\Candidates\InterviewScheduledNotification;
use App\Services\Jitsi\JitsiService;
use App\Settings\JitsiSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.interviews.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.nav.recruitment');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.interviews.details'))
                    ->schema([
                        Forms\Components\Select::make('job_candidate_id')
                            ->label(__('admin.interviews.job_candidate'))
                            ->options(function () {
                                return JobCandidates::with(['candidateProfile', 'job'])
                                    ->get()
                                    ->mapWithKeys(fn (JobCandidates $jc) => [
                                        $jc->id => ($jc->candidateProfile?->FirstName . ' ' . $jc->candidateProfile?->LastName . ' — ' . $jc->job?->JobTitle),
                                    ]);
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => 'Interview - ' . now()->format('M d, Y')),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin.interviews.schedule'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label(__('admin.interviews.date_time'))
                            ->required()
                            ->native(false)
                            ->minutesStep(15)
                            ->default(now()->addDay()->setHour(10)->setMinute(0)),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label(__('admin.interviews.duration_minutes'))
                            ->numeric()
                            ->required()
                            ->default(fn () => app(JitsiSettings::class)->default_meeting_duration ?? 60)
                            ->minValue(15)
                            ->maxValue(480)
                            ->step(15),
                        Forms\Components\Select::make('status')
                            ->options(InterviewStatus::class)
                            ->default('Scheduled')
                            ->required(),
                        Forms\Components\Hidden::make('scheduled_by')
                            ->default(fn () => auth()->id()),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin.interviews.feedback_rating'))
                    ->schema([
                        Forms\Components\Textarea::make('interviewer_notes')
                            ->label(__('admin.interviews.interviewer_notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('rating')
                            ->label(__('messages.rating'))
                            ->options([
                                1 => __('enums.interview_rating.1'),
                                2 => __('enums.interview_rating.2'),
                                3 => __('enums.interview_rating.3'),
                                4 => __('enums.interview_rating.4'),
                                5 => __('enums.interview_rating.5'),
                            ])
                            ->nullable(),
                    ])
                    ->collapsible()
                    ->collapsed(fn (?Interview $record) => $record === null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobCandidate.candidateProfile.full_name')
                    ->label(__('admin.interviews.candidate'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobCandidate.job.JobTitle')
                    ->label(__('admin.interviews.job'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('admin.interviews.scheduled'))
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label(__('messages.duration'))
                    ->suffix(' min')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Interview $record): string => $record->status->getColor()),
                Tables\Columns\TextColumn::make('rating')
                    ->label(__('messages.rating'))
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) . str_repeat('☆', 5 - $state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduler.name')
                    ->label(__('admin.interviews.scheduled_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(InterviewStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('join_meeting')
                    ->label(__('admin.interviews.join'))
                    ->icon('heroicon-o-video-camera')
                    ->color('success')
                    ->url(function (Interview $record): string {
                        return route('interview.meeting', $record);
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Interview $record): bool => $record->isJoinable()),
                Tables\Actions\Action::make('copy_candidate_link')
                    ->label(__('admin.interviews.candidate_link'))
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (Interview $record) {
                        Notification::make()
                            ->title(__('admin.interviews.candidate_meeting_link'))
                            ->body($record->getCandidateMeetingUrl())
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn (Interview $record): bool => $record->isUpcoming() || $record->isJoinable()),
                Tables\Actions\Action::make('notify_candidate')
                    ->label(__('admin.interviews.send_notification'))
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.interviews.send_interview_notification'))
                    ->modalDescription(__('admin.interviews.send_interview_notification_desc'))
                    ->action(function (Interview $record) {
                        $candidate = $record->jobCandidate->candidateProfile;
                        if ($candidate) {
                            $candidate->notify(new InterviewScheduledNotification($record));
                            Notification::make()
                                ->title(__('admin.interviews.notification_sent'))
                                ->success()
                                ->body(__('admin.interviews.notification_sent_body', ['email' => $candidate->email]))
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterviews::route('/'),
            'create' => Pages\CreateInterview::route('/create'),
            'view' => Pages\ViewInterview::route('/{record}'),
            'edit' => Pages\EditInterview::route('/{record}/edit'),
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
