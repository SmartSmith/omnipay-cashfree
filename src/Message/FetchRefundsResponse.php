<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * Cashfree's list of an order's refunds. A 200 is a JSON list of refund entities (empty when the
 * order has none); anything else is an error answer {message, code, type}.
 */
class FetchRefundsResponse extends AbstractResponse
{
    /** @param array<array-key, mixed> $data */
    public function __construct(RequestInterface $request, array $data, private readonly int $httpStatus)
    {
        parent::__construct($request, $data);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function isSuccessful(): bool
    {
        return $this->httpStatus >= 200 && $this->httpStatus < 300 && is_array($this->data) && array_is_list($this->data);
    }

    /**
     * @return list<array<array-key, mixed>>
     *
     * @throws InvalidResponseException when Cashfree answered with an error, not a list
     */
    public function getRefunds(): array
    {
        if (! $this->isSuccessful()) {
            throw new InvalidResponseException('Cashfree answered HTTP '.$this->httpStatus.' to the refund list, not a list: '.(string) $this->getMessage());
        }

        return array_values(array_filter($this->data, 'is_array'));
    }

    /** @return array<array-key, mixed>|null the refund Cashfree holds under this refund_id */
    public function getRefund(string $refundId): ?array
    {
        foreach ($this->getRefunds() as $refund) {
            if (isset($refund['refund_id']) && $refund['refund_id'] === $refundId) {
                return $refund;
            }
        }

        return null;
    }

    public function getMessage(): ?string
    {
        if (! $this->isSuccessful() && is_array($this->data) && isset($this->data['message'])) {
            return (string) $this->data['message'];
        }

        return null;
    }

    public function getCode(): ?string
    {
        if (! $this->isSuccessful() && is_array($this->data) && isset($this->data['code'])) {
            return (string) $this->data['code'];
        }

        return null;
    }
}
