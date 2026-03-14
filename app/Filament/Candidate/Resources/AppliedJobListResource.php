<?php

namespace App\Filament\Candidate\Resources;

use App\Filament\Candidate\Resources\AppliedJobListResource\Pages;
use App\Filament\Enums\JobCandidateStatus;
use App\Models\JobCandidates;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppliedJobListResource extends Resource
{
    protected static ?string $model = JobCandidates::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('candidate.portal.my_applied_jobs');
    }

    public static function getModelLabel(): string
    {
        return __('candidate.portal.applied_job');
    }

    public static function getPluralModelLabel(): string
    {
        return __('candidate.portal.applied_jobs');
    }

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('Email', '=', Filament::auth()->user()->email)->count();

        return $count > 0 ? $count : '';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('Email', '=', Filament::auth()->user()->email)->count() > 0 ? 'success' : '';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('JobCandidateId')
                    ->label(__('candidate.portal.job_candidate_number')),
                Tables\Columns\TextColumn::make('job.postingTitle')
                    ->label(__('candidate.portal.job_title')),
                Tables\Columns\BooleanColumn::make('job.RemoteJob')
                    ->label(__('candidate.portal.remote_job')),
                Tables\Columns\TextColumn::make('job.Salary')
                    ->toggleable()
                    ->label(__('messages.salary')),
                Tables\Columns\TextColumn::make('CandidateStatus')
                    ->label(__('candidate.portal.application_status')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(JobCandidateStatus::class)
                    ->attribute('CandidateStatus'),
            ])
            ->actions([
            ])
            ->emptyStateHeading(__('candidate.portal.no_job_applied'))
            ->emptyStateDescription(__('candidate.portal.no_job_applied_desc'))
            ->emptyStateIcon('heroicon-o-briefcase');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('Email', '=', auth()->user()->email);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppliedJobLists::route('/'),
        ];
    }
}
