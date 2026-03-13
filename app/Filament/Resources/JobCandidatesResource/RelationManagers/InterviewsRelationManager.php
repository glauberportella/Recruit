<?php

namespace App\Filament\Resources\JobCandidatesResource\RelationManagers;

use App\Filament\Enums\InterviewStatus;
use App\Filament\Enums\JobCandidateStatus;
use App\Models\Interview;
use App\Notifications\Candidates\InterviewScheduledNotification;
use App\Settings\JitsiSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InterviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'interviews';

    protected static ?string $title = 'Interviews';

    protected static ?string $icon = 'heroicon-o-video-camera';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => 'Interview - ' . now()->format('M d, Y'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Date & Time')
                    ->required()
                    ->native(false)
                    ->minutesStep(15)
                    ->default(now()->addDay()->setHour(10)->setMinute(0)),
                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Duration (min)')
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Interview $record): string => $record->status->getColor()),
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) . str_repeat('☆', 5 - $state) : '—'),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Schedule Interview')
                    ->icon('heroicon-o-calendar')
                    ->after(function (Interview $record) {
                        // Update job candidate status
                        $jobCandidate = $this->getOwnerRecord();
                        $jobCandidate->update(['CandidateStatus' => JobCandidateStatus::InterviewScheduled->value]);

                        // Notify candidate
                        $candidate = $jobCandidate->candidateProfile;
                        if ($candidate) {
                            $candidate->notify(new InterviewScheduledNotification($record));
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('join')
                    ->label('Join')
                    ->icon('heroicon-o-video-camera')
                    ->color('success')
                    ->url(fn (Interview $record): string => route('interview.meeting', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Interview $record): bool => $record->isJoinable()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
