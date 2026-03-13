<?php

namespace App\Filament\Candidate\Resources;

use App\Filament\Candidate\Resources\RecommendedJobsResource\Pages;
use App\Models\CandidateMatchScore;
use App\Models\Candidates;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecommendedJobsResource extends Resource
{
    protected static ?string $model = CandidateMatchScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Recommended Jobs';

    protected static ?string $modelLabel = 'Recommended Job';

    protected static ?string $pluralModelLabel = 'Recommended Jobs';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $candidateEmail = Filament::auth()->user()?->email;
        $candidate = Candidates::where('email', $candidateEmail)->first();

        if (! $candidate) {
            return '';
        }

        $count = CandidateMatchScore::where('candidate_id', $candidate->id)
            ->where('overall_score', '>=', 50)
            ->count();

        return $count > 0 ? (string) $count : '';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $candidateEmail = Filament::auth()->user()?->email;
        $candidate = Candidates::where('email', $candidateEmail)->first();

        if (! $candidate) {
            return '';
        }

        return CandidateMatchScore::where('candidate_id', $candidate->id)
            ->where('overall_score', '>=', 50)
            ->count() > 0 ? 'success' : '';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('jobOpening.postingTitle')
                    ->label('Job Title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobOpening.Salary')
                    ->label('Salary'),
                Tables\Columns\IconColumn::make('jobOpening.RemoteJob')
                    ->label('Remote')
                    ->boolean(),
                Tables\Columns\TextColumn::make('jobOpening.JobType')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Match')
                    ->badge()
                    ->color(fn (CandidateMatchScore $record): string => match (true) {
                        $record->overall_score >= 80 => 'success',
                        $record->overall_score >= 60 => 'info',
                        $record->overall_score >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('matched_at')
                    ->label('Analyzed')
                    ->since(),
            ])
            ->defaultSort('overall_score', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_match')
                    ->label('Why this match?')
                    ->icon('heroicon-o-question-mark-circle')
                    ->modalHeading('Match Details')
                    ->modalContent(fn (CandidateMatchScore $record) => view('filament.components.candidate-match-details', ['match' => $record]))
                    ->modalSubmitAction(false),
            ])
            ->emptyStateHeading('No recommendations yet')
            ->emptyStateDescription('Once your profile is analyzed, matching jobs will appear here.')
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    public static function getEloquentQuery(): Builder
    {
        $candidateEmail = Filament::auth()->user()?->email;
        $candidate = Candidates::where('email', $candidateEmail)->first();

        if (! $candidate) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('candidate_id', $candidate->id)
            ->where('overall_score', '>=', 50)
            ->whereHas('jobOpening', function (Builder $query) {
                $query->where('Status', 'Opened')
                    ->where('published_career_site', 1);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecommendedJobs::route('/'),
        ];
    }
}
