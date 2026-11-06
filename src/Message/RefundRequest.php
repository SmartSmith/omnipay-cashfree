<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Message;

/**
 * Refunds a paid order, in full or in part: `POST /orders/{order_id}/refunds` with the refund_id the
 * caller chose. Cashfree keeps one refund per refund_id, so a caller that sends the same refund_id
 * again never refunds twice; Cashfree answers 409 instead and the caller reads the refund back.
 */
class RefundRequest extends AbstractRequest
{
    public function getRefundId(): ?string
    {
        return $this->getParameter('refundId');
    }

    public function setRefundId(string $value): self
    {
        return $this->setParameter('refundId', $value);
    }

    public function getRefundNote(): ?string
    {
        return $this->getParameter('refundNote');
    }

    public function setRefundNote(string $value): self
    {
        return $this->setParameter('refundNote', $value);
    }

    /** @return array{order_id: string, refund_id: string, refund_amount: float, refund_note?: string} */
    public function getData(): array
    {
        $this->validate('transactionId', 'refundId', 'amount', 'currency');

        $data = [
            'order_id' => (string) $this->getTransactionId(),
            'refund_id' => (string) $this->getRefundId(),
            'refund_amount' => (float) $this->getAmount(),
        ];

        $note = $this->getRefundNote();
        if ($note !== null && $note !== '') {
            $data['refund_note'] = $note;
        }

        return $data;
    }

    /** @param array{order_id: string, refund_id: string, refund_amount: float, refund_note?: string} $data */
    public function sendData($data): RefundResponse
    {
        $endpoint = $this->getBaseEndpoint().'/orders/'.rawurlencode($data['order_id']).'/refunds';
        $body = $data;
        unset($body['order_id']);

        $httpResponse = $this->httpClient->request('POST', $endpoint, $this->getAuthHeaders(), json_encode($body, JSON_THROW_ON_ERROR));

        return $this->response = new RefundResponse(
            $this,
            CashfreeAnswer::decode($httpResponse->getStatusCode(), $httpResponse->getBody()->getContents()),
            $httpResponse->getStatusCode(),
        );
    }
}
