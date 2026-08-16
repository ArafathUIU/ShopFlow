<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Confirmation - '.$this->order->order_number)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your order #'.$this->order->order_number.' has been placed successfully.')
            ->line('Total: $'.number_format($this->order->total->amount(), 2))
            ->line('Status: '.ucfirst($this->order->status->value))
            ->action('View Order', url('/dashboard/orders'))
            ->line('Thank you for shopping with us!');
    }
}
