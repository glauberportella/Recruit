<?php

namespace App\Notifications\User;

use App\Models\User;
use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class InviteNewSystemUserNotification extends Notification
{
    use Queueable;

    protected User $user;

    protected ?string $verify_link;

    private string $companyName;

    /**
     * Create a new notification instance.
     */
    public function __construct(User|Model $user, $verify_link)
    {
        $this->user = $user;
        $this->verify_link = $verify_link;
        $this->companyName = (new GeneralSetting)->company_name;
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
            ->subject(__('notifications.system_user_invitation.subject'))
            ->from(env('MAIL_FROM_ADDRESS'), $this->companyName)
            ->greeting(__('notifications.system_user_invitation.greeting', ['name' => $this->user->name]))
            ->line(__('notifications.system_user_invitation.welcome'))
            ->with(new HtmlString("1. " . __('notifications.system_user_invitation.verify_email', ['link' => $this->verify_link])))
            ->with(new HtmlString('2. ' . __('notifications.system_user_invitation.create_password')))
            ->with(new HtmlString(__('notifications.system_user_invitation.password_criteria')))
            ->with(new HtmlString('3. ' . __('notifications.system_user_invitation.access_system')))
            ->action(__('notifications.system_user_invitation.verify_create_action'), $this->verify_link)
            ->line(__('notifications.system_user_invitation.support'))
            ->line(__('notifications.system_user_invitation.thank_you'))
            ->salutation(new HtmlString(__('notifications.system_user_invitation.regards') . "<br/>{$this->companyName}"));
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
