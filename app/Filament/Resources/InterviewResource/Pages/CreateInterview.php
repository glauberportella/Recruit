<?php

namespace App\Filament\Resources\InterviewResource\Pages;

use App\Filament\Enums\InterviewStatus;
use App\Filament\Enums\JobCandidateStatus;
use App\Filament\Resources\InterviewResource;
use App\Notifications\Candidates\InterviewScheduledNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateInterview extends CreateRecord
{
    protected static string $resource = InterviewResource::class;

    protected function afterCreate(): void
    {
        $interview = $this->record;

        // Update job candidate status
        $jobCandidate = $interview->jobCandidate;
        if ($jobCandidate) {
            $jobCandidate->update(['CandidateStatus' => JobCandidateStatus::InterviewScheduled->value]);

            // Notify candidate
            $candidate = $jobCandidate->candidateProfile;
            if ($candidate) {
                $candidate->notify(new InterviewScheduledNotification($interview));
            }
        }
    }
}
