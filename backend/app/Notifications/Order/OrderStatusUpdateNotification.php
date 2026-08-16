<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $fromStatus,
        private readonly string $toStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Update - '.$this->order->order_number)
            ->line('Your order #'.$this->order->order_number.' status has been updated.')
            ->line('From: '.ucfirst($this->fromStatus))
            ->line('To: '.ucfirst($this->toStatus))
            ->action('View Order', url('/dashboard/orders'))
            ->line('Thank you for shopping with us!');
    }
}
