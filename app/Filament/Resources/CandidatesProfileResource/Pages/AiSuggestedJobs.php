<?php

namespace App\Filament\Resources\CandidatesProfileResource\Pages;

use App\Filament\Resources\CandidatesProfileResource;
use App\Jobs\ProcessCandidateJobSuggestions;
use App\Models\CandidateMatchScore;
use App\Models\Candidates;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AiSuggestedJobs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CandidatesProfileResource::class;

    protected static string $view = 'filament.pages.ai-suggested-jobs';

    public Candidates|int|string $record;

    public function getTitle(): string
    {
        return "AI Suggested Jobs — {$this->record->FirstName} {$this->record->LastName}";
    }

    public function getBreadcrumb(): string
    {
        return 'AI Suggested Jobs';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_suggestions')
                ->label('Refresh AI Suggestions')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    ProcessCandidateJobSuggestions::dispatch($this->record->id);

                    Notification::make()->title('AI Match Queued')->success()
                        ->body('Job suggestions refresh has been queued.')->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CandidateMatchScore::query()
                    ->where('candidate_id', $this->record->id)
                    ->orderByDesc('overall_score')
            )
            ->columns([
                Tables\Columns\TextColumn::make('jobOpening.postingTitle')
                    ->label('Job Title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jobOpening.JobType')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('jobOpening.Salary')
                    ->label('Salary'),
                Tables\Columns\IconColumn::make('jobOpening.RemoteJob')
                    ->label('Remote')
                    ->boolean(),
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
            ->emptyStateHeading('No AI suggestions yet')
            ->emptyStateDescription('Click "Refresh AI Suggestions" to find matching jobs for this candidate.')
            ->emptyStateIcon('heroicon-o-cpu-chip');
    }
}
