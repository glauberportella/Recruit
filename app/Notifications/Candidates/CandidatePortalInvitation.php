<?php

namespace App\Notifications\Candidates;

use App\Models\Candidates;
use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class CandidatePortalInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    private Model|array|null $candidate;

    private string $companyName;

    private string $signup_link;

    /**
     * Create a new notification instance.
     */
    public function __construct(Candidates $candidates, $inviteLink = null)
    {
        $this->candidate = $candidates;
        $this->companyName = (new GeneralSetting)->company_name;
        $this->signup_link = $inviteLink;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.candidate_invitation.subject'))
            ->greeting(__('notifications.candidate_invitation.greeting', ['name' => $this->candidate->LastName]))
            ->from(env('MAIL_FROM_ADDRESS'), $this->companyName)
            ->line(__('notifications.candidate_invitation.intro'))
            ->line(__('notifications.candidate_invitation.value_proposition', ['company' => $this->companyName]))
            ->line(__('notifications.candidate_invitation.what_to_expect'))
            ->with(new HtmlString("1. " . __('notifications.candidate_invitation.tailored_jobs')))
            ->with(new HtmlString("2. " . __('notifications.candidate_invitation.application_tracking')))
            ->with(new HtmlString("3. " . __('notifications.candidate_invitation.company_insights', ['company' => $this->companyName])))
            ->with(new HtmlString('4. ' . __('notifications.candidate_invitation.community')))
            ->line(__('notifications.candidate_invitation.get_started'))
            ->action(__('notifications.candidate_invitation.sign_up'), $this->signup_link)
            ->line(__('notifications.candidate_invitation.same_email'))
            ->line(__('notifications.candidate_invitation.talent_community', ['company' => $this->companyName]))
            ->line(__('notifications.candidate_invitation.great_talent'))
            ->line(__('notifications.candidate_invitation.see_you'))
            ->salutation(new HtmlString(__('notifications.candidate_invitation.regards') . "<br/>{$this->companyName}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
