<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Message;

/** Lists every refund of an order: `GET /orders/{order_id}/refunds`. */
class FetchRefundsRequest extends AbstractRequest
{
    /** @return array{order_id: string} */
    public function getData(): array
    {
        $this->validate('transactionId');

        return ['order_id' => (string) $this->getTransactionId()];
    }

    /** @param array{order_id: string} $data */
    public function sendData($data): FetchRefundsResponse
    {
        $endpoint = $this->getBaseEndpoint().'/orders/'.rawurlencode($data['order_id']).'/refunds';

        $httpResponse = $this->httpClient->request('GET', $endpoint, $this->getAuthHeaders());

        return $this->response = new FetchRefundsResponse(
            $this,
            CashfreeAnswer::decode($httpResponse->getStatusCode(), $httpResponse->getBody()->getContents()),
            $httpResponse->getStatusCode(),
        );
    }
}
