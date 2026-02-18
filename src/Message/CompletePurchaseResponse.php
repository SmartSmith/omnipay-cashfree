<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        return ($this->data['order_status'] ?? '') === 'PAID';
    }

    public function isPending(): bool
    {
        return ($this->data['order_status'] ?? '') === 'ACTIVE';
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['cf_order_id'] ?? null;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['order_id'] ?? null;
    }

    public function getMessage(): ?string
    {
        $status = $this->data['order_status'] ?? 'UNKNOWN';

        return match ($status) {
            'PAID' => 'Payment successful',
            'ACTIVE' => 'Payment pending',
            'EXPIRED' => 'Order expired',
            'TERMINATED' => 'Order terminated',
            default => $this->data['message'] ?? "Order status: {$status}",
        };
    }

    public function getCode(): ?string
    {
        return $this->data['order_status'] ?? null;
    }

    public function getOrderStatus(): ?string
    {
        return $this->data['order_status'] ?? null;
    }
}
