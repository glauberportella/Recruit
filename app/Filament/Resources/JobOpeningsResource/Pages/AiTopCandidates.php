<?php

namespace App\Filament\Resources\JobOpeningsResource\Pages;

use App\Filament\Resources\JobOpeningsResource;
use App\Jobs\ProcessCandidateMatching;
use App\Models\CandidateMatchScore;
use App\Models\JobOpenings;
use App\Services\AI\CandidateMatchingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AiTopCandidates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = JobOpeningsResource::class;

    protected static string $view = 'filament.pages.ai-top-candidates';

    public JobOpenings|int|string $record;

    public function getTitle(): string
    {
        return "AI Top Candidates — {$this->record->postingTitle}";
    }

    public function getBreadcrumb(): string
    {
        return 'AI Top Candidates';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('run_matching')
                ->label('Run AI Matching for All Candidates')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    ProcessCandidateMatching::dispatch($this->record->id);

                    Notification::make()->title('AI Match Queued')->success()
                        ->body('Matching analysis for all candidates has been queued.')->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CandidateMatchScore::query()
                    ->where('job_opening_id', $this->record->id)
                    ->orderByDesc('overall_score')
            )
            ->columns([
                Tables\Columns\TextColumn::make('candidate.FirstName')
                    ->label('First Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('candidate.LastName')
                    ->label('Last Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('candidate.email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Match %')
                    ->badge()
                    ->color(fn (CandidateMatchScore $record): string => match (true) {
                        $record->overall_score >= 80 => 'success',
                        $record->overall_score >= 60 => 'info',
                        $record->overall_score >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('skills_score')
                    ->label('Skills')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('experience_score')
                    ->label('Experience')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('education_score')
                    ->label('Education')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary_score')
                    ->label('Salary')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('matched_at')
                    ->label('Analyzed')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-chart-bar')
                    ->modalHeading('Match Score Details')
                    ->modalContent(fn (CandidateMatchScore $record) => view('filament.components.match-score-details', ['match' => $record]))
                    ->modalSubmitAction(false),
            ])
            ->emptyStateHeading('No AI matches yet')
            ->emptyStateDescription('Click "Run AI Matching" to analyze candidates for this job opening.')
            ->emptyStateIcon('heroicon-o-cpu-chip');
    }
}
