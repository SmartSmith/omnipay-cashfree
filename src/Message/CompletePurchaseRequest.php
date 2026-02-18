<?php

namespace Omnipay\Cashfree\Message;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData(): array
    {
        $this->validate('transactionId');

        return [
            'order_id' => $this->getTransactionId(),
        ];
    }

    public function sendData($data): CompletePurchaseResponse
    {
        $endpoint = $this->getBaseEndpoint() . '/orders/' . urlencode($data['order_id']);

        $httpResponse = $this->httpClient->request(
            'GET',
            $endpoint,
            $this->getAuthHeaders()
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new CompletePurchaseResponse($this, $responseBody ?? []);
    }
}
