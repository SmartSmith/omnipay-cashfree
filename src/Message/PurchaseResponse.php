<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return !empty($this->data['payment_session_id']) || !empty($this->data['payments_url']);
    }

    public function getRedirectUrl(): ?string
    {
        return $this->data['payments_url'] ?? null;
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['cf_order_id'] ?? null;
    }

    public function getTransactionId(): ?string
    {
        return $this->data['order_id'] ?? null;
    }

    public function getPaymentSessionId(): ?string
    {
        return $this->data['payment_session_id'] ?? null;
    }

    public function getMessage(): ?string
    {
        if (isset($this->data['message'])) {
            return $this->data['message'];
        }

        if (isset($this->data['order_status'])) {
            return 'Order status: ' . $this->data['order_status'];
        }

        return null;
    }

    public function getCode(): ?string
    {
        return $this->data['code'] ?? $this->data['type'] ?? null;
    }
}
