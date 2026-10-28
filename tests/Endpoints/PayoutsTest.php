<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Tests\Endpoints;

use Omnipay\Common\Exception\InvalidRequestException;
use PHPUnit\Framework\Attributes\Test;

/**
 * The payout operations against Cashfree Payouts' sandbox, with the payout credentials rather than
 * the payment gateway's: adding a beneficiary, sending a transfer, reading a transfer's status and
 * checking a bank account. Each runs once for its success answer and once for each failure answer
 * Cashfree gives, and a request missing what it needs is refused before anything is sent.
 */
final class PayoutsTest extends EndpointTestCase
{
    #[Test]
    #[Calls('Gateway::createBeneficiary')]
    public function create_beneficiary_adds_the_bank_account(): void
    {
        $this->setMockHttpResponse('CreateBeneficiarySuccess.txt');

        $response = $this->gateway->createBeneficiary([
            'beneficiaryId' => 'BENE_001',
            'beneficiaryName' => 'John Doe',
            'bankAccount' => '00011020001234',
            'bankIfsc' => 'HDFC0001234',
            'beneficiaryEmail' => 'john@example.com',
        ])->send();

        $request = $this->sent();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/payout/beneficiary', (string) $request->getUri());
        $this->assertPayoutHeaders($request);
        $this->assertSame([
            'beneficiary_id' => 'BENE_001',
            'beneficiary_name' => 'John Doe',
            'bank_account_number' => '00011020001234',
            'bank_ifsc' => 'HDFC0001234',
            'beneficiary_contact_details' => ['beneficiary_email' => 'john@example.com'],
        ], $this->body($request));
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('BENE_001', $response->getBeneficiaryId());
        $this->assertSame('VERIFIED', $response->getBeneficiaryStatus());
    }

    #[Test]
    public function create_beneficiary_reports_a_beneficiary_id_cashfree_already_holds(): void
    {
        $this->setMockHttpResponse('CreateBeneficiaryError.txt');

        $response = $this->gateway->createBeneficiary(['beneficiaryId' => 'BENE_001', 'beneficiaryName' => 'John Doe'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Beneficiary with this ID already exists', $response->getMessage());
        $this->assertSame('beneficiary_id_already_exists', $response->getCode());
    }

    #[Test]
    public function create_beneficiary_without_a_name_is_refused_before_sending(): void
    {
        try {
            $this->gateway->createBeneficiary(['beneficiaryId' => 'BENE_001'])->send();
            $this->fail('A createBeneficiary with no beneficiaryName was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The beneficiaryName parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }

    #[Test]
    #[Calls('Gateway::payout')]
    public function payout_starts_a_transfer_to_the_beneficiary(): void
    {
        $this->setMockHttpResponse('PayoutSuccess.txt');

        $response = $this->gateway->payout([
            'transactionId' => 'TXN_PAY_202602_001',
            'amount' => '15000.00',
            'beneficiaryId' => 'BENE_001',
            'description' => 'February payout',
        ])->send();

        $request = $this->sent();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/payout/transfers', (string) $request->getUri());
        $this->assertPayoutHeaders($request);
        $this->assertSame([
            'transfer_id' => 'TXN_PAY_202602_001',
            'transfer_amount' => 15000,
            'transfer_mode' => 'banktransfer',
            'beneficiary_id' => 'BENE_001',
            'remarks' => 'February payout',
        ], $this->body($request));
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('CF_TRANSFER_001', $response->getTransactionReference());
        $this->assertSame('TXN_PAY_202602_001', $response->getTransactionId());
        $this->assertSame(15000.0, $response->getTransferAmount());
    }

    #[Test]
    public function payout_reports_a_transfer_cashfree_failed(): void
    {
        $this->setMockHttpResponse('PayoutFailed.txt');

        $response = $this->gateway->payout(['transactionId' => 'TXN_PAY_202602_002', 'amount' => '15000.00', 'beneficiaryId' => 'BENE_002'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('FAILED', $response->getStatus());
        $this->assertSame('Beneficiary bank account is invalid', $response->getMessage());
        $this->assertSame('TRANSFER_FAILED', $response->getCode());
    }

    #[Test]
    public function payout_without_an_amount_is_refused_before_sending(): void
    {
        try {
            $this->gateway->payout(['transactionId' => 'TXN_PAY_202602_001', 'beneficiaryId' => 'BENE_001'])->send();
            $this->fail('A payout with no amount was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The amount parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }

    #[Test]
    #[Calls('Gateway::payoutStatus')]
    public function payout_status_reads_a_completed_transfer_by_our_transfer_id(): void
    {
        $this->setMockHttpResponse('PayoutStatusSuccess.txt');

        $response = $this->gateway->payoutStatus(['transactionId' => 'TXN_PAY_202602_001'])->send();

        $request = $this->sent();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/payout/transfers?transfer_id=TXN_PAY_202602_001', (string) $request->getUri());
        $this->assertPayoutHeaders($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('UTR123456789', $response->getUtr());
        $this->assertSame('CF_TRANSFER_001', $response->getTransactionReference());
        $this->assertSame('BENE_001', $response->getBeneficiaryId());
    }

    #[Test]
    public function payout_status_reads_by_cashfree_transfer_id_when_given(): void
    {
        $this->setMockHttpResponse('PayoutStatusPending.txt');

        $response = $this->gateway->payoutStatus(['transactionId' => 'TXN_PAY_202602_001', 'cfTransferId' => 'CF_TRANSFER_001'])->send();

        $this->assertSame('https://sandbox.cashfree.com/payout/transfers?cf_transfer_id=CF_TRANSFER_001', (string) $this->sent()->getUri());
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('Transfer is being processed', $response->getMessage());
    }

    #[Test]
    public function payout_status_reads_a_failed_transfer(): void
    {
        $this->setMockHttpResponse('PayoutStatusFailed.txt');

        $response = $this->gateway->payoutStatus(['transactionId' => 'TXN_PAY_202602_002'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertTrue($response->isFailed());
        $this->assertSame('Beneficiary bank account is invalid', $response->getMessage());
    }

    #[Test]
    public function payout_status_without_either_transfer_id_is_refused_before_sending(): void
    {
        try {
            $this->gateway->payoutStatus()->send();
            $this->fail('A payoutStatus with no transfer id was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('Either transactionId or cfTransferId is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }

    #[Test]
    #[Calls('Gateway::validateBankAccount')]
    public function validate_bank_account_reads_a_valid_account_and_its_holder(): void
    {
        $this->setMockHttpResponse('ValidateBankAccountValid.txt');

        $response = $this->gateway->validateBankAccount([
            'bankAccount' => '00011020001234',
            'bankIfsc' => 'HDFC0001234',
            'beneficiaryName' => 'John Doe',
        ])->send();

        $request = $this->sent();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame(
            'https://sandbox.cashfree.com/payout/validation/bankDetails?bank_account=00011020001234&ifsc=HDFC0001234&name=John+Doe',
            (string) $request->getUri(),
        );
        $this->assertPayoutHeaders($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('VALID', $response->getAccountStatus());
        $this->assertSame('JOHN DOE', $response->getAccountHolderName());
    }

    #[Test]
    public function validate_bank_account_reads_an_invalid_account_as_not_successful(): void
    {
        $this->setMockHttpResponse('ValidateBankAccountInvalid.txt');

        $response = $this->gateway->validateBankAccount(['bankAccount' => '00011020009999', 'bankIfsc' => 'HDFC0001234', 'beneficiaryName' => 'John Doe'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('INVALID', $response->getAccountStatus());
    }

    #[Test]
    public function validate_bank_account_without_an_ifsc_is_refused_before_sending(): void
    {
        try {
            $this->gateway->validateBankAccount(['bankAccount' => '00011020001234', 'beneficiaryName' => 'John Doe'])->send();
            $this->fail('A validateBankAccount with no bankIfsc was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The bankIfsc parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }
}
