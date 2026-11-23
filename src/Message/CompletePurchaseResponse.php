<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * Cashfree's order as CompletePurchaseRequest read it. PAID is successful; EXPIRED and TERMINATED are failed.
 * Cashfree keeps an order ACTIVE while the buyer may still pay on it, so an ACTIVE order is read by its latest
 * payment attempt (`latest_payment`, which the request always sets for an ACTIVE order): it is pending only while
 * that attempt is in flight. With no attempt, or with an attempt that ended without payment, this attempt is over
 * and the response is failed with the attempt's reason; the same order can still be paid later (JUDGE-2376).
 * An attempt status outside both closed lists, or an ACTIVE order read without its payments, throws.
 */
class CompletePurchaseResponse extends AbstractResponse
{
    /**
     * Attempt statuses still in flight on an ACTIVE order: PENDING, and SUCCESS, because Cashfree has the payment
     * but has not yet marked the order PAID.
     */
    private const array IN_FLIGHT_ATTEMPT_STATUSES = ['PENDING', 'SUCCESS'];

    /** Attempt statuses that ended the attempt without payment; Cashfree keeps the order ACTIVE for a retry. */
    private const array ENDED_ATTEMPT_WITHOUT_PAYMENT_STATUSES = ['FAILED', 'USER_DROPPED', 'NOT_ATTEMPTED', 'VOID', 'CANCELLED'];

    /** The code of an ACTIVE order that had no payment attempt at all. */
    private const string NO_ATTEMPT_CODE = 'NO_ATTEMPT';

    /**
     * How an ACTIVE order's attempt ended without payment, or null when the order is not ACTIVE or its attempt
     * is still in flight. The one reading isPending(), getCode() and getMessage() take an ACTIVE order from.
     *
     * @var array{code: string, message: string}|null
     */
    private readonly ?array $endedAttempt;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidResponseException when an ACTIVE order's attempt cannot be read
     */
    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);
        $this->endedAttempt = $this->readEndedAttempt();
    }

    public function isSuccessful(): bool
    {
        return ($this->data['order_status'] ?? '') === 'PAID';
    }

    public function isPending(): bool
    {
        return ($this->data['order_status'] ?? '') === 'ACTIVE' && $this->endedAttempt === null;
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
        if ($this->endedAttempt !== null) {
            return $this->endedAttempt['message'];
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

    /**
     * For an ACTIVE order whose attempt ended without payment, the attempt's status (FAILED, USER_DROPPED,
     * NOT_ATTEMPTED, VOID, CANCELLED) or NO_ATTEMPT when there was none; else the order's status.
     */
    public function getCode(): ?string
    {
        if ($this->endedAttempt !== null) {
            return $this->endedAttempt['code'];
        }

        return $this->data['order_status'] ?? null;
    }

    public function getOrderStatus(): ?string
    {
        return $this->data['order_status'] ?? null;
    }

    /**
     * The order's latest payment attempt, read for an ACTIVE order only, or null when none was read or the
     * order had none.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestPayment(): ?array
    {
        $latest = $this->data['latest_payment'] ?? null;

        return is_array($latest) ? $latest : null;
    }

    /**
     * @return array{code: string, message: string}|null
     *
     * @throws InvalidResponseException
     */
    private function readEndedAttempt(): ?array
    {
        if (($this->data['order_status'] ?? null) !== 'ACTIVE') {
            return null;
        }
        if (! is_array($this->data) || ! array_key_exists('latest_payment', $this->data)) {
            throw new InvalidResponseException("Cashfree's ACTIVE order was read without its payments");
        }

        $latest = $this->data['latest_payment'];
        if ($latest === null) {
            return ['code' => self::NO_ATTEMPT_CODE, 'message' => 'No payment was attempted on this order'];
        }
        if (! is_array($latest)) {
            throw new InvalidResponseException("Cashfree's ACTIVE order was read with a latest payment that is not a payment ("
                .get_debug_type($latest).')');
        }

        $status = $latest['payment_status'] ?? null;
        if (in_array($status, self::IN_FLIGHT_ATTEMPT_STATUSES, true)) {
            return null;
        }
        if (! in_array($status, self::ENDED_ATTEMPT_WITHOUT_PAYMENT_STATUSES, true)) {
            throw new InvalidResponseException("Cashfree's latest payment on an ACTIVE order has a status this package does not know: "
                .(is_string($status) ? $status : get_debug_type($status)));
        }

        $message = $latest['payment_message'] ?? null;

        return [
            'code' => $status,
            'message' => is_string($message) && $message !== '' ? $message : 'Payment attempt '.$status,
        ];
    }
}
