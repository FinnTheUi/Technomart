<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Daily digest to admins of products at or below their reorder level.
 */
class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(public Collection $products) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->products->count().' product(s) need restocking - Technomart')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('These products are at or below their reorder level:');

        foreach ($this->products as $product) {
            $mail->line('- '.$product->name.': '.$product->stock.' left (reorder at '.$product->reorder_level.')');
        }

        return $mail->action('Create a purchase order', route('manager.purchase-orders.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_ids' => $this->products->pluck('id')->all(),
            'url' => route('manager.purchase-orders.index'),
            'message' => $this->products->count().' product(s) are low on stock: '.$this->products->pluck('name')->take(5)->implode(', ').'.',
        ];
    }
}

