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

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('candidate.portal.recommended_jobs');
    }

    public static function getModelLabel(): string
    {
        return __('candidate.portal.recommended_job');
    }

    public static function getPluralModelLabel(): string
    {
        return __('candidate.portal.recommended_jobs');
    }

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
                    ->label(__('candidate.portal.job_title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobOpening.Salary')
                    ->label(__('messages.salary')),
                Tables\Columns\IconColumn::make('jobOpening.RemoteJob')
                    ->label(__('messages.remote'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('jobOpening.JobType')
                    ->label(__('messages.type')),
                Tables\Columns\TextColumn::make('overall_score')
                    ->label(__('candidate.portal.match'))
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
                    ->label(__('candidate.portal.analyzed'))
                    ->since(),
            ])
            ->defaultSort('overall_score', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_match')
                    ->label(__('candidate.portal.why_match'))
                    ->icon('heroicon-o-question-mark-circle')
                    ->modalHeading(__('candidate.portal.match_details'))
                    ->modalContent(fn (CandidateMatchScore $record) => view('filament.components.candidate-match-details', ['match' => $record]))
                    ->modalSubmitAction(false),
            ])
            ->emptyStateHeading(__('candidate.portal.no_recommendations'))
            ->emptyStateDescription(__('candidate.portal.no_recommendations_desc'))
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
