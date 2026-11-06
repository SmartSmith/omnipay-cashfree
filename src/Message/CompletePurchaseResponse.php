<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Cashfree's order as CompletePurchaseRequest read it. PAID is successful. ACTIVE is pending unless its latest
 * attempt (`latest_payment`) FAILED or was USER_DROPPED: Cashfree keeps the order ACTIVE so the buyer may retry,
 * but that attempt is over, so the response is failed with Cashfree's reason (LAUNCH-197). EXPIRED and
 * TERMINATED are failed.
 */
class CompletePurchaseResponse extends AbstractResponse
{
    /** The attempt statuses after which an ACTIVE order's attempt is over without payment. */
    private const array ENDED_ATTEMPT_STATUSES = ['FAILED', 'USER_DROPPED'];

    public function isSuccessful(): bool
    {
        return ($this->data['order_status'] ?? '') === 'PAID';
    }

    public function isPending(): bool
    {
        return ($this->data['order_status'] ?? '') === 'ACTIVE' && ! $this->latestAttemptEnded();
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
        if ($this->latestAttemptEnded()) {
            $message = $this->data['latest_payment']['payment_message'] ?? null;
            if (is_string($message) && $message !== '') {
                return $message;
            }

            return 'Payment attempt ' . $this->data['latest_payment']['payment_status'];
        }

        $status = $this->data['order_status'] ?? 'UNKNOWN';

        return match ($status) {
            'PAID' => 'Payment successful',
            'ACTIVE' => 'Payment pending',
            'EXPIRED' => 'Order expired',
            'TERMINATED' => 'Order terminated',
            default => $this->data['message'] ?? "Order status: {$status}",
        };
    }

    /** The latest attempt's status (FAILED, USER_DROPPED) when it ended the attempt, else the order's status. */
    public function getCode(): ?string
    {
        if ($this->latestAttemptEnded()) {
            return $this->data['latest_payment']['payment_status'];
        }

        return $this->data['order_status'] ?? null;
    }

    public function getOrderStatus(): ?string
    {
        return $this->data['order_status'] ?? null;
    }

    /**
     * The order's latest payment attempt, read for an ACTIVE order only, or null when none was read.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestPayment(): ?array
    {
        $latest = $this->data['latest_payment'] ?? null;

        return is_array($latest) ? $latest : null;
    }

    private function latestAttemptEnded(): bool
    {
        if (($this->data['order_status'] ?? '') !== 'ACTIVE') {
            return false;
        }
        $latest = $this->getLatestPayment();

        return $latest !== null && in_array($latest['payment_status'] ?? null, self::ENDED_ATTEMPT_STATUSES, true);
    }
}
