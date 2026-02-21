<?php

namespace Omnipay\Cashfree\Message\Payout;

/**
 * Initiate a transfer via Cashfree Payouts V2 Standard Transfer API.
 *
 * Required: transactionId (as transferId), amount, beneficiaryId OR inline beneficiary details.
 * transferMode defaults to 'banktransfer'. Options: banktransfer, neft, imps, rtgs, upi.
 *
 * @see https://www.cashfree.com/docs/api-reference/payouts/v2/transfers-v2/standard-transfer-v2
 */
class PayoutRequest extends AbstractPayoutRequest
{
    public function getData(): array
    {
        $this->validate('transactionId', 'amount');

        $data = [
            'transfer_id' => $this->getTransactionId(),
            'transfer_amount' => (float) $this->getAmount(),
            'transfer_mode' => $this->getTransferMode() ?: 'banktransfer',
        ];

        // Either reference an existing beneficiary or provide inline details
        if ($this->getBeneficiaryId()) {
            $data['beneficiary_id'] = $this->getBeneficiaryId();
        } else {
            // Inline beneficiary for direct transfer
            $beneficiaryDetails = [
                'beneficiary_name' => $this->getBeneficiaryName(),
            ];

            if ($this->getBankAccount()) {
                $beneficiaryDetails['bank_account_number'] = $this->getBankAccount();
            }
            if ($this->getBankIfsc()) {
                $beneficiaryDetails['bank_ifsc'] = $this->getBankIfsc();
            }
            if ($this->getVpa()) {
                $beneficiaryDetails['vpa'] = $this->getVpa();
            }
            if ($this->getBeneficiaryEmail()) {
                $beneficiaryDetails['beneficiary_contact_details']['beneficiary_email'] = $this->getBeneficiaryEmail();
            }
            if ($this->getBeneficiaryPhone()) {
                $beneficiaryDetails['beneficiary_contact_details']['beneficiary_phone'] = $this->getBeneficiaryPhone();
            }

            $data['beneficiary_details'] = $beneficiaryDetails;
        }

        if ($this->getDescription()) {
            $data['remarks'] = $this->getDescription();
        }

        return $data;
    }

    public function sendData($data): PayoutResponse
    {
        $endpoint = $this->getBaseEndpoint() . '/transfers';

        $httpResponse = $this->httpClient->request(
            'POST',
            $endpoint,
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $responseBody = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new PayoutResponse($this, $responseBody ?? []);
    }
}
