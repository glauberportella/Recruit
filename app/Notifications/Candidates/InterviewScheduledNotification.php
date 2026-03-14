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
            ->subject(__('notifications.interview_scheduled.subject', ['job' => $job->JobTitle, 'company' => $this->companyName]))
            ->greeting(__('notifications.interview_scheduled.greeting', ['name' => $candidate->FirstName]))
            ->from(env('MAIL_FROM_ADDRESS'), $this->companyName)
            ->line(__('notifications.interview_scheduled.intro', ['job' => $job->JobTitle, 'company' => $this->companyName]))
            ->line(__('notifications.interview_scheduled.details_heading'))
            ->line(__('notifications.interview_scheduled.date_time', ['datetime' => $scheduledAt]))
            ->line(__('notifications.interview_scheduled.duration', ['duration' => $duration]))
            ->line(__('notifications.interview_scheduled.subject_line', ['title' => $this->interview->title]))
            ->line($this->interview->description ? "**" . __('messages.notes') . ":** {$this->interview->description}" : '')
            ->line(__('notifications.interview_scheduled.join_info'))
            ->action(__('notifications.interview_scheduled.view_interviews'), url('/portal/candidate'))
            ->line(__('notifications.interview_scheduled.prepare'))
            ->line(__('notifications.interview_scheduled.looking_forward'));
    }
}
