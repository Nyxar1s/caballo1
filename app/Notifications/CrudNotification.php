<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrudNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $action,
        private string $model,
        private array $details = [],
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->action) {
            'created' => $this->createMailForCreated($notifiable),
            'updated' => $this->createMailForUpdated($notifiable),
            'deleted' => $this->createMailForDeleted($notifiable),
            default => new MailMessage(),
        };
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return match ($this->action) {
            'created' => $this->arrayForCreated(),
            'updated' => $this->arrayForUpdated(),
            'deleted' => $this->arrayForDeleted(),
            default => [],
        };
    }

    private function createMailForCreated(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new {$this->model} has been created.")
            ->line("Details: " . json_encode($this->details))
            ->line('Thank you for using our application!');
    }

    private function createMailForUpdated(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->greeting("Hello {$notifiable->name}!")
            ->line("A {$this->model} has been updated.")
            ->line("Changes: " . json_encode($this->details))
            ->line('Thank you for using our application!');
    }

    private function createMailForDeleted(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->greeting("Hello {$notifiable->name}!")
            ->line("A {$this->model} has been deleted.")
            ->line("Details: " . json_encode($this->details))
            ->line('Thank you for using our application!');
    }

    private function arrayForCreated(): array
    {
        return [
            'action' => 'created',
            'model' => $this->model,
            'message' => "New {$this->model} created",
            'details' => $this->details,
            'timestamp' => now(),
        ];
    }

    private function arrayForUpdated(): array
    {
        return [
            'action' => 'updated',
            'model' => $this->model,
            'message' => "{$this->model} updated",
            'details' => $this->details,
            'timestamp' => now(),
        ];
    }

    private function arrayForDeleted(): array
    {
        return [
            'action' => 'deleted',
            'model' => $this->model,
            'message' => "{$this->model} deleted",
            'details' => $this->details,
            'timestamp' => now(),
        ];
    }
}
