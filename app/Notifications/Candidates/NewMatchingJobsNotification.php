<?php

namespace App\Notifications\Candidates;

use App\Models\CandidateMatchScore;
use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class NewMatchingJobsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $companyName;

    public function __construct(
        private readonly Collection $matchScores,
    ) {
        $this->companyName = (new GeneralSetting)->company_name;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("New Job Matches Found — {$this->companyName}")
            ->greeting("Hello {$notifiable->name},")
            ->from(env('MAIL_FROM_ADDRESS'), $this->companyName)
            ->line("Great news! We've found new job openings that match your profile:");

        foreach ($this->matchScores->take(5) as $match) {
            /** @var CandidateMatchScore $match */
            $job = $match->jobOpening;
            if ($job) {
                $score = number_format($match->overall_score, 0);
                $message->with(new HtmlString(
                    "<strong>{$job->postingTitle}</strong> — {$score}% match" .
                    ($job->Salary ? " | Salary: {$job->Salary}" : '') .
                    ($job->RemoteJob ? ' | Remote' : '')
                ));
            }
        }

        $portalUrl = url('/portal/candidate');

        $remaining = $this->matchScores->count() - 5;
        if ($remaining > 0) {
            $message->line("And {$remaining} more matching jobs!");
        }

        return $message
            ->line('Log in to your candidate portal to view all recommended jobs and their detailed match analysis.')
            ->action('View Recommended Jobs', $portalUrl)
            ->salutation(new HtmlString("Best regards,<br/>{$this->companyName}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'match_count' => $this->matchScores->count(),
            'top_score' => $this->matchScores->first()?->overall_score,
        ];
    }
}
