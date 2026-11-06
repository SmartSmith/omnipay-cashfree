<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Reads the order. A Cashfree order stays ACTIVE after a failed or dropped attempt (the buyer may retry on
 * it), so for an ACTIVE order the request also reads the order's payments and hands the response the latest
 * attempt under `latest_payment` (LAUNCH-197): the response reads an ACTIVE order whose latest attempt
 * FAILED or was USER_DROPPED as failed, never pending.
 */
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
        if (! is_array($responseBody)) {
            $responseBody = [];
        }

        if (($responseBody['order_status'] ?? null) === 'ACTIVE') {
            $latest = $this->latestPayment($endpoint . '/payments');
            if ($latest !== null) {
                $responseBody['latest_payment'] = $latest;
            }
        }

        return $this->response = new CompletePurchaseResponse($this, $responseBody);
    }

    /**
     * The order's latest payment attempt, the one with the highest cf_payment_id, or null when the order has
     * none yet. An answer that is not a list of attempts each carrying a cf_payment_id and a payment_status
     * throws: a status read from it would be a guess.
     *
     * @return array<string, mixed>|null
     */
    private function latestPayment(string $endpoint): ?array
    {
        $httpResponse = $this->httpClient->request('GET', $endpoint, $this->getAuthHeaders());
        $payments = json_decode($httpResponse->getBody()->getContents(), true);
        if (! is_array($payments) || ! array_is_list($payments)) {
            throw new InvalidResponseException('Cashfree answered the order payments read with something other than a list of payments (HTTP ' . $httpResponse->getStatusCode() . ')');
        }

        $latest = null;
        foreach ($payments as $payment) {
            if (! is_array($payment) || ! isset($payment['cf_payment_id'], $payment['payment_status'])
                || ! is_numeric($payment['cf_payment_id']) || ! is_string($payment['payment_status'])) {
                throw new InvalidResponseException('Cashfree listed a payment with no cf_payment_id or payment_status');
            }
            if ($latest === null || (int) $payment['cf_payment_id'] > (int) $latest['cf_payment_id']) {
                $latest = $payment;
            }
        }

        return $latest;
    }
}
