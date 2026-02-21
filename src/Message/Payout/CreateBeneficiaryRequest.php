<?php

namespace Omnipay\Cashfree\Message\Payout;

/**
 * Create a beneficiary on Cashfree Payouts V2.
 *
 * Required: beneficiaryId, beneficiaryName, and either (bankAccount + bankIfsc) or vpa.
 *
 * @see https://www.cashfree.com/docs/api-reference/payouts/v2/beneficiary-v2/create-beneficiary-v2
 */
class CreateBeneficiaryRequest extends AbstractPayoutRequest
{
    public function getData(): array
    {
        $this->validate('beneficiaryId', 'beneficiaryName');

        $data = [
            'beneficiary_id' => $this->getBeneficiaryId(),
            'beneficiary_name' => $this->getBeneficiaryName(),
        ];

        if ($this->getBankAccount()) {
            $data['bank_account_number'] = $this->getBankAccount();
        }

        if ($this->getBankIfsc()) {
            $data['bank_ifsc'] = $this->getBankIfsc();
        }

        if ($this->getVpa()) {
            $data['vpa'] = $this->getVpa();
        }

        if ($this->getBeneficiaryEmail()) {
            $data['beneficiary_contact_details']['beneficiary_email'] = $this->getBeneficiaryEmail();
        }

        if ($this->getBeneficiaryPhone()) {
            $data['beneficiary_contact_details']['beneficiary_phone'] = $this->getBeneficiaryPhone();
        }

        return $data;
    }

    public function sendData($data): CreateBeneficiaryResponse
    {
        $endpoint = $this->getBaseEndpoint() . '/beneficiary';

        $httpResponse = $this->httpClient->request(
            'POST',
            $endpoint,
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new CreateBeneficiaryResponse($this, $responseBody ?? []);
    }
}
