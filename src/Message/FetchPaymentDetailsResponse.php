<?php

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Message\AbstractResponse;

class FetchPaymentDetailsResponse extends AbstractResponse
{
    /**
     * Returns true if any payment in the response has status SUCCESS.
     */
    public function isSuccessful(): bool
    {
        $payment = $this->getSuccessfulPayment();
        return $payment !== null;
    }

    public function getBankReference(): ?string
    {
        return $this->getSuccessfulPayment()['bank_reference'] ?? null;
    }

    public function getServiceCharge(): ?string
    {
        return $this->getSuccessfulPayment()['payment_completion_charges'] ?? null;
    }

    public function getServiceTax(): ?string
    {
        return $this->getSuccessfulPayment()['payment_completion_tax'] ?? null;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->getSuccessfulPayment()['payment_group'] ?? null;
    }

    public function getTransactionReference(): ?string
    {
        return isset($this->getSuccessfulPayment()['cf_payment_id'])
            ? (string) $this->getSuccessfulPayment()['cf_payment_id']
            : null;
    }

    public function getMessage(): ?string
    {
        $payment = $this->getSuccessfulPayment();
        if ($payment) {
            return $payment['payment_message'] ?? 'Payment successful';
        }

        if (empty($this->data) || !is_array($this->data)) {
            return 'No payment data available';
        }

        return $this->data[0]['payment_message'] ?? 'No successful payment found';
    }

    /**
     * Find the first payment with status SUCCESS from the payments array.
     */
    protected function getSuccessfulPayment(): ?array
    {
        if (!is_array($this->data)) {
            return null;
        }

        foreach ($this->data as $payment) {
            if (($payment['payment_status'] ?? '') === 'SUCCESS') {
                return $payment;
            }
        }

        return null;
    }
}
