<?php

namespace App\Filament\Resources\InterviewResource\Pages;

use App\Filament\Resources\InterviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInterview extends ViewRecord
{
    protected static string $resource = InterviewResource::class;

    protected static string $view = 'filament.pages.view-interview';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('join_meeting')
                ->label('Join Meeting')
                ->icon('heroicon-o-video-camera')
                ->color('success')
                ->url(fn () => route('interview.meeting', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->isJoinable()),
        ];
    }
}
