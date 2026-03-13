<?php

namespace App\Notifications\Candidates;

use App\Models\Interview;
use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Interview $interview;

    private string $companyName;

    public function __construct(Interview $interview)
    {
        $this->interview = $interview;
        $this->companyName = (new GeneralSetting)->company_name ?? 'Our Company';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $candidate = $this->interview->jobCandidate->candidateProfile;
        $job = $this->interview->jobCandidate->job;
        $scheduledAt = $this->interview->scheduled_at->format('l, F j, Y \a\t g:i A');
        $duration = $this->interview->duration_minutes;

        return (new MailMessage)
            ->subject("Interview Scheduled - {$job->JobTitle} at {$this->companyName}")
            ->greeting("Dear {$candidate->FirstName},")
            ->from(env('MAIL_FROM_ADDRESS'), $this->companyName)
            ->line("We are pleased to inform you that an interview has been scheduled for the **{$job->JobTitle}** position at {$this->companyName}.")
            ->line("**Interview Details:**")
            ->line("- **Date & Time:** {$scheduledAt}")
            ->line("- **Duration:** {$duration} minutes")
            ->line("- **Subject:** {$this->interview->title}")
            ->line($this->interview->description ? "**Notes:** {$this->interview->description}" : '')
            ->line('You will be able to join the video interview from your Candidate Portal when the interview time arrives.')
            ->action('View My Interviews', url('/portal/candidate'))
            ->line('Please ensure you have a stable internet connection and a working camera/microphone.')
            ->line('Thank you for your interest, and we look forward to speaking with you!');
    }
}
