<?php

namespace Omnipay\Cashfree\Message;

class FetchPaymentDetailsRequest extends AbstractRequest
{
    public function getData(): array
    {
        $this->validate('transactionId');

        return [
            'order_id' => $this->getTransactionId(),
        ];
    }

    public function sendData($data): FetchPaymentDetailsResponse
    {
        $endpoint = $this->getBaseEndpoint() . '/orders/' . urlencode($data['order_id']) . '/payments';

        $httpResponse = $this->httpClient->request(
            'GET',
            $endpoint,
            $this->getAuthHeaders()
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new FetchPaymentDetailsResponse($this, $responseBody ?? []);
    }
}
