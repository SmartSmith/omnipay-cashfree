<?php

namespace Omnipay\Cashfree\Tests\Message\Payout;

use Omnipay\Cashfree\Message\Payout\PayoutRequest;
use Omnipay\Cashfree\Message\Payout\PayoutResponse;
use Omnipay\Tests\TestCase;

class PayoutRequestTest extends TestCase
{
    private PayoutRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new PayoutRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'payoutClientId' => 'payout_test_id',
            'payoutClientSecret' => 'payout_test_secret',
            'testMode' => true,
            'transactionId' => 'TXN_PAY_202602_001',
            'amount' => '15000.00',
            'beneficiaryId' => 'BENE_001',
            'transferMode' => 'banktransfer',
            'description' => 'Monthly payout for February 2026',
        ]);
    }

    public function testGetDataWithBeneficiaryId(): void
    {
        $data = $this->request->getData();

        $this->assertSame('TXN_PAY_202602_001', $data['transfer_id']);
        $this->assertSame(15000.00, $data['transfer_amount']);
        $this->assertSame('banktransfer', $data['transfer_mode']);
        $this->assertSame('BENE_001', $data['beneficiary_id']);
        $this->assertSame('Monthly payout for February 2026', $data['remarks']);
        $this->assertArrayNotHasKey('beneficiary_details', $data);
    }

    public function testGetDataWithInlineBeneficiary(): void
    {
        $this->request->setBeneficiaryId(null);
        $this->request->setBeneficiaryName('John Doe');
        $this->request->setBankAccount('1234567890');
        $this->request->setBankIfsc('HDFC0001234');

        $data = $this->request->getData();

        $this->assertArrayNotHasKey('beneficiary_id', $data);
        $this->assertSame('John Doe', $data['beneficiary_details']['beneficiary_name']);
        $this->assertSame('1234567890', $data['beneficiary_details']['bank_account_number']);
        $this->assertSame('HDFC0001234', $data['beneficiary_details']['bank_ifsc']);
    }

    public function testDefaultTransferMode(): void
    {
        $this->request->setTransferMode(null);

        $data = $this->request->getData();

        $this->assertSame('banktransfer', $data['transfer_mode']);
    }

    public function testValidationRequiresTransactionId(): void
    {
        $this->request->setTransactionId(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresAmount(): void
    {
        $this->request->setAmount(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testEndpointIsTransfers(): void
    {
        $this->setMockHttpResponse('PayoutSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://sandbox.cashfree.com/payout/transfers',
            (string) $lastRequest->getUri()
        );
    }

    public function testSuccessfulPendingResponse(): void
    {
        $this->setMockHttpResponse('PayoutSuccess.txt');
        $response = $this->request->send();

        $this->assertInstanceOf(PayoutResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('CF_TRANSFER_001', $response->getTransactionReference());
        $this->assertSame('TXN_PAY_202602_001', $response->getTransactionId());
        $this->assertSame('PENDING', $response->getStatus());
    }

    public function testFailedResponse(): void
    {
        $this->setMockHttpResponse('PayoutFailed.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('FAILED', $response->getStatus());
        $this->assertSame('TRANSFER_FAILED', $response->getStatusCode());
    }
}
