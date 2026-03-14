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
            ->subject(__('notifications.new_matching_jobs.subject', ['company' => $this->companyName]))
            ->greeting(__('notifications.new_matching_jobs.greeting', ['name' => $notifiable->name]))
            ->from(env('MAIL_FROM_ADDRESS'), $this->companyName)
            ->line(__('notifications.new_matching_jobs.intro'));

        foreach ($this->matchScores->take(5) as $match) {
            /** @var CandidateMatchScore $match */
            $job = $match->jobOpening;
            if ($job) {
                $score = number_format($match->overall_score, 0);
                $message->with(new HtmlString(
                    "<strong>{$job->postingTitle}</strong> — {$score}% match" .
                    ($job->Salary ? " | " . __('notifications.new_matching_jobs.salary', ['salary' => $job->Salary]) : '') .
                    ($job->RemoteJob ? ' | ' . __('notifications.new_matching_jobs.remote') : '')
                ));
            }
        }

        $portalUrl = url('/portal/candidate');

        $remaining = $this->matchScores->count() - 5;
        if ($remaining > 0) {
            $message->line(__('notifications.new_matching_jobs.more_jobs', ['count' => $remaining]));
        }

        return $message
            ->line(__('notifications.new_matching_jobs.login_info'))
            ->action(__('notifications.new_matching_jobs.view_recommended'), $portalUrl)
            ->salutation(new HtmlString(__('notifications.new_matching_jobs.regards') . "<br/>{$this->companyName}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'match_count' => $this->matchScores->count(),
            'top_score' => $this->matchScores->first()?->overall_score,
        ];
    }
}
