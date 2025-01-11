<?php

namespace Santosdave\SabreWrapper\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class HealthCheckAlert extends Notification implements ShouldQueue
{
    use Queueable;

    private array $healthCheck;

    public function __construct(array $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    public function via($notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Sabre API Health Check Alert')
            ->line('A health check has detected issues with the Sabre API integration.')
            ->line('Status: ' . strtoupper($this->healthCheck['status']))
            ->line('Time: ' . $this->healthCheck['timestamp']);

        foreach ($this->healthCheck['checks'] as $name => $check) {
            if ($check['status'] !== 'healthy') {
                $mail->line('')
                    ->line("Service: {$name}")
                    ->line("Status: {$check['status']}")
                    ->line("Message: {$check['message']}");

                if (isset($check['details'])) {
                    $mail->line('Details: ' . json_encode($check['details'], JSON_PRETTY_PRINT));
                }
            }
        }

        $mail->action('View Health Dashboard', url('/dashboard/health'));

        return $mail;
    }

    public function toSlack($notifiable)
    {
        $message = [];

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'status' => $this->healthCheck['status'],
            'timestamp' => $this->healthCheck['timestamp'],
            'checks' => $this->healthCheck['checks'],
            'meta' => $this->healthCheck['meta']
        ];
    }
}
