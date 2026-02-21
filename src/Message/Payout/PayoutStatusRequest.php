<?php

namespace Omnipay\Cashfree\Message\Payout;

/**
 * Get transfer status via Cashfree Payouts V2.
 *
 * Can query by transferId (our reference) or cfTransferId (Cashfree's reference).
 *
 * @see https://www.cashfree.com/docs/api-reference/payouts/v2/transfers-v2/get-transfer-status-v2
 */
class PayoutStatusRequest extends AbstractPayoutRequest
{
    public function getCfTransferId(): ?string
    {
        return $this->getParameter('cfTransferId');
    }

    public function setCfTransferId(?string $value): self
    {
        return $this->setParameter('cfTransferId', $value);
    }

    public function getData(): array
    {
        // Either transactionId (our transfer_id) or cfTransferId is required
        if (!$this->getTransactionId() && !$this->getCfTransferId()) {
            throw new \Omnipay\Common\Exception\InvalidRequestException('Either transactionId or cfTransferId is required');
        }

        return [];
    }

    public function sendData($data): PayoutStatusResponse
    {
        $queryParams = [];

        if ($this->getCfTransferId()) {
            $queryParams['cf_transfer_id'] = $this->getCfTransferId();
        } else {
            $queryParams['transfer_id'] = $this->getTransactionId();
        }

        $endpoint = $this->getBaseEndpoint() . '/transfers?' . http_build_query($queryParams);

        $httpResponse = $this->httpClient->request(
            'GET',
            $endpoint,
            $this->getAuthHeaders()
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new PayoutStatusResponse($this, $responseBody ?? []);
    }
}
