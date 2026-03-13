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

    protected static ?string $navigationLabel = 'Interviews';

    protected static ?string $navigationGroup = 'Recruitment';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Interview Details')
                    ->schema([
                        Forms\Components\Select::make('job_candidate_id')
                            ->label('Job Candidate')
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

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Date & Time')
                            ->required()
                            ->native(false)
                            ->minutesStep(15)
                            ->default(now()->addDay()->setHour(10)->setMinute(0)),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
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

                Forms\Components\Section::make('Feedback & Rating')
                    ->schema([
                        Forms\Components\Textarea::make('interviewer_notes')
                            ->label('Interviewer Notes')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('rating')
                            ->label('Rating')
                            ->options([
                                1 => '1 - Poor',
                                2 => '2 - Below Average',
                                3 => '3 - Average',
                                4 => '4 - Good',
                                5 => '5 - Excellent',
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
                    ->label('Candidate')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobCandidate.job.JobTitle')
                    ->label('Job')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Interview $record): string => $record->status->getColor()),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) . str_repeat('☆', 5 - $state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduler.name')
                    ->label('Scheduled By')
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
                    ->label('Join')
                    ->icon('heroicon-o-video-camera')
                    ->color('success')
                    ->url(function (Interview $record): string {
                        return route('interview.meeting', $record);
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Interview $record): bool => $record->isJoinable()),
                Tables\Actions\Action::make('copy_candidate_link')
                    ->label('Candidate Link')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function (Interview $record) {
                        Notification::make()
                            ->title('Candidate Meeting Link')
                            ->body($record->getCandidateMeetingUrl())
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn (Interview $record): bool => $record->isUpcoming() || $record->isJoinable()),
                Tables\Actions\Action::make('notify_candidate')
                    ->label('Send Notification')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Interview Notification')
                    ->modalDescription('Send an email notification to the candidate with interview details.')
                    ->action(function (Interview $record) {
                        $candidate = $record->jobCandidate->candidateProfile;
                        if ($candidate) {
                            $candidate->notify(new InterviewScheduledNotification($record));
                            Notification::make()
                                ->title('Notification Sent')
                                ->success()
                                ->body("Interview notification sent to {$candidate->email}")
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
