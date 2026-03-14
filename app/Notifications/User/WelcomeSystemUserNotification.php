<?php

namespace App\Notifications\User;

use App\Models\User;
use App\Settings\GeneralSetting;
use Filament\Exceptions\NoDefaultPanelSetException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WelcomeSystemUserNotification extends Notification
{
    use Queueable;

    protected ?User $user;

    protected ?string $login_link;

    protected ?string $company_name;

    /**
     * Create a new notification instance.
     *
     * @throws NoDefaultPanelSetException
     */
    public function __construct(?User $user)
    {
        $this->user = $user;
        $this->login_link = filament()->getDefaultPanel()->getLoginUrl();
        $this->company_name = (new GeneralSetting())->company_name;
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
            ->from(env('MAIL_FROM_ADDRESS'), $this->company_name)
            ->subject(__('notifications.welcome_system_user.subject'))
            ->greeting(__('notifications.welcome_system_user.greeting', ['name' => $this->user->name]))
            ->line(__('notifications.welcome_system_user.registration_complete'))
            ->line(__('notifications.welcome_system_user.login_info'))
            ->action(__('notifications.welcome_system_user.login_now'), $this->login_link)
            ->line(__('notifications.welcome_system_user.support'))
            ->line(__('notifications.welcome_system_user.thank_you'))
            ->salutation(new HtmlString(__('notifications.welcome_system_user.regards') . "<br/>{$this->company_name}"));
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
