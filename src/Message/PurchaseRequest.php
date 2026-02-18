<?php

namespace Omnipay\Cashfree\Message;

class PurchaseRequest extends AbstractRequest
{
    public function getData(): array
    {
        $this->validate('amount', 'currency', 'transactionId');

        $data = [
            'order_id' => $this->getTransactionId(),
            'order_amount' => (float) $this->getAmount(),
            'order_currency' => $this->getCurrency(),
            'customer_details' => [
                'customer_id' => $this->getCustomerId() ?: $this->getTransactionId(),
                'customer_email' => $this->getCustomerEmail() ?: '',
                'customer_phone' => $this->getCustomerPhone() ?: '9999999999',
                'customer_name' => $this->getCustomerName() ?: '',
            ],
            'order_meta' => [],
        ];

        if ($this->getReturnUrl()) {
            $data['order_meta']['return_url'] = $this->getReturnUrl() . '?order_id={order_id}';
        }

        if ($this->getNotifyUrl()) {
            $data['order_meta']['notify_url'] = $this->getNotifyUrl();
        }

        $paymentMethods = $this->getPaymentMethods();
        if ($paymentMethods) {
            $data['order_meta']['payment_methods'] = $paymentMethods;
        }

        if ($this->getDescription()) {
            $data['order_note'] = $this->getDescription();
        }

        return $data;
    }

    public function sendData($data): PurchaseResponse
    {
        $endpoint = $this->getBaseEndpoint() . '/orders';

        $httpResponse = $this->httpClient->request(
            'POST',
            $endpoint,
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new PurchaseResponse($this, $responseBody ?? []);
    }
}
