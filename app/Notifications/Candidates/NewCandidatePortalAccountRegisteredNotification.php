<?php

namespace App\Notifications\Candidates;

use App\Models\Candidates;
use App\Settings\GeneralSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NewCandidatePortalAccountRegisteredNotification extends Notification
{
    private Model|array|null $candidate;

    private string $candidate_loginLink;

    protected ?string $company_name;

    public function __construct(Candidates $candidates)
    {
        $this->candidate = $candidates;
        $this->candidate_loginLink = filament()->getPanel('candidate')->getLoginUrl();
        $this->company_name = (new GeneralSetting())->company_name;
    }

    /**
     * Get the notification's delivery channels.
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
            ->subject(__('notifications.new_candidate_account.subject'))
            ->greeting(__('notifications.new_candidate_account.greeting', ['name' => $this->candidate->LastName]))
            ->from(env('MAIL_FROM_ADDRESS'), $this->company_name)
            ->line(__('notifications.new_candidate_account.intro'))
            ->line(__('notifications.new_candidate_account.key_details'))
            ->with(new HtmlString(__('notifications.new_candidate_account.account_info')))
            ->with(new HtmlString("<ul><li>" . __('notifications.new_candidate_account.email_address', ['email' => $this->candidate->email]) . "</li></ul>"))
            ->line(__('notifications.new_candidate_account.getting_started_intro'))
            ->with(new HtmlString(__('notifications.new_candidate_account.getting_started_heading')))
            ->line(__('notifications.new_candidate_account.step_login_page', ['link' => $this->candidate_loginLink]))
            ->line(__('notifications.new_candidate_account.step_use_credentials'))
            ->line(__('notifications.new_candidate_account.step_dashboard'))
            ->with(new HtmlString(__('notifications.new_candidate_account.important_note')))
            ->with(new HtmlString(__('notifications.new_candidate_account.keep_credentials_safe')))
            ->line(__('notifications.new_candidate_account.support'))
            ->line(__('notifications.new_candidate_account.excited'))
            ->line(__('notifications.new_candidate_account.thank_you'))
            ->salutation(new HtmlString(__('notifications.new_candidate_account.regards') . "<br/>{$this->company_name}"));

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
