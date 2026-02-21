<?php

namespace Omnipay\Cashfree\Message\Payout;

/**
 * Validate a bank account via Cashfree Payouts V2.
 *
 * Verifies that the bank account number + IFSC are valid and the account is active.
 *
 * @see https://www.cashfree.com/docs/api-reference/payouts/v1/verify-1
 */
class ValidateBankAccountRequest extends AbstractPayoutRequest
{
    public function getData(): array
    {
        $this->validate('bankAccount', 'bankIfsc', 'beneficiaryName');

        return [
            'bank_account' => $this->getBankAccount(),
            'ifsc' => $this->getBankIfsc(),
            'name' => $this->getBeneficiaryName(),
        ];
    }

    public function sendData($data): ValidateBankAccountResponse
    {
        $queryParams = http_build_query($data);
        $endpoint = $this->getBaseEndpoint() . '/validation/bankDetails?' . $queryParams;

        $httpResponse = $this->httpClient->request(
            'GET',
            $endpoint,
            $this->getAuthHeaders()
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new ValidateBankAccountResponse($this, $responseBody ?? []);
    }
}
