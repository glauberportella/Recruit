<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralsResource\Pages;
use App\Models\JobOpenings;
use App\Models\Referrals;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralsResource extends Resource
{
    protected static ?string $model = Referrals::class;

    protected static ?string $recordTitleAttribute = 'jobopenings.postingTitle';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'healthicons-o-referral';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.referrals.refer_candidate'))
                    ->schema([
                        Forms\Components\FileUpload::make('resume')
                            ->hint('Supported file types: .pdf')
                            ->nullable()
                            ->acceptedFileTypes([
                                'application/pdf',
                            ]),
                        Forms\Components\Section::make(__('admin.referrals.job_recommendation'))
                            ->schema([
                                Forms\Components\Select::make('ReferringJob')
                                    ->prefixIcon('heroicon-s-briefcase')
                                    ->options(JobOpenings::all()->pluck('JobTitle', 'id'))
                                    ->required(),
                            ]),
                        Forms\Components\Section::make(__('admin.referrals.candidate_information'))
                            ->schema([
                                Forms\Components\Select::make('Candidate')
                                    ->prefixIcon('heroicon-s-briefcase')
                                    ->relationship(name: 'candidates', titleAttribute: 'full_name')
                                    ->searchable(['email', 'LastName', 'FirstName'])
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('FirstName')
                                            ->label(__('messages.first_name')),
                                        Forms\Components\TextInput::make('LastName')
                                            ->label(__('messages.last_name')),
                                        Forms\Components\TextInput::make('Mobile')
                                            ->label(__('messages.mobile'))
                                            ->tel(),
                                        Forms\Components\TextInput::make('Email')
                                            ->required(),
                                        Forms\Components\TextInput::make('CurrentEmployer')
                                            ->label(__('admin.job_candidates.current_employer')),
                                        Forms\Components\TextInput::make('CurrentJobTitle')
                                            ->label(__('admin.job_candidates.current_job_title')),
                                    ]),
                            ]),
                        Forms\Components\Section::make(__('messages.additional_information'))
                            ->schema([
                                Forms\Components\Select::make('Relationship')
                                    ->options([
                                        'None' => __('enums.relationship.none'),
                                        'Personally Known' => __('enums.relationship.personally_known'),
                                        'Former Colleague' => __('enums.relationship.former_colleague'),
                                        'Socially Connected' => __('enums.relationship.socially_connected'),
                                        'Got the resume through a common fried' => __('enums.relationship.common_friend'),
                                        'Others' => __('enums.relationship.others'),
                                    ]),
                                Forms\Components\Select::make('KnownPeriod')
                                    ->options([
                                        'None' => __('enums.known_period.none'),
                                        'Less than a year' => __('enums.known_period.less_than_year'),
                                        '1-2 years' => __('enums.known_period.1_2_years'),
                                        '3-5 years' => __('enums.known_period.3_5_years'),
                                        '5+ years' => __('enums.known_period.5_plus_years'),
                                    ]),
                                Forms\Components\Textarea::make('Notes')
                                    ->nullable(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('candidates.FullName'),
                Tables\Columns\TextColumn::make('jobopenings.JobTitle'),
                Tables\Columns\TextColumn::make('jobcandidates.CandidateStatus'),
                Tables\Columns\TextColumn::make('jobcandidates.candidateOwner'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferrals::route('/create'),
            'view' => Pages\ViewReferrals::route('/{record}'),
            'edit' => Pages\EditReferrals::route('/{record}/edit'),
        ];
    }
}
