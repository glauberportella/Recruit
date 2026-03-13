<?php

namespace App\Filament\Candidate\Resources;

use App\Filament\Candidate\Resources\MyInterviewsResource\Pages;
use App\Filament\Enums\InterviewStatus;
use App\Models\Interview;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyInterviewsResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'My Interviews';

    protected static ?string $modelLabel = 'Interview';

    protected static ?string $pluralModelLabel = 'My Interviews';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $email = Filament::auth()->user()?->email;
        if (! $email) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->where('status', InterviewStatus::Scheduled->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Interview')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobCandidate.job.JobTitle')
                    ->label('Job'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y \a\t H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (Interview $record): string => $record->status->getColor()),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(InterviewStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('join')
                    ->label('Join Meeting')
                    ->icon('heroicon-o-video-camera')
                    ->color('success')
                    ->url(fn (Interview $record): string => route('interview.meeting', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Interview $record): bool => $record->isJoinable()),
            ])
            ->emptyStateHeading('No Interviews Scheduled')
            ->emptyStateDescription('When an interview is scheduled for you, it will appear here.')
            ->emptyStateIcon('heroicon-o-video-camera');
    }

    public static function getEloquentQuery(): Builder
    {
        $email = auth()->user()?->email;

        return parent::getEloquentQuery()
            ->whereHas('jobCandidate', function (Builder $query) use ($email) {
                $query->where('Email', $email);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyInterviews::route('/'),
        ];
    }
}
