<?php

namespace Omnipay\Cashfree\Tests\Message\Payout;

use Omnipay\Cashfree\Message\Payout\CreateBeneficiaryRequest;
use Omnipay\Cashfree\Message\Payout\CreateBeneficiaryResponse;
use Omnipay\Tests\TestCase;

class CreateBeneficiaryRequestTest extends TestCase
{
    private CreateBeneficiaryRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new CreateBeneficiaryRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'payoutClientId' => 'payout_test_id',
            'payoutClientSecret' => 'payout_test_secret',
            'testMode' => true,
            'beneficiaryId' => 'BENE_001',
            'beneficiaryName' => 'John Doe',
            'bankAccount' => '1234567890',
            'bankIfsc' => 'HDFC0001234',
            'beneficiaryEmail' => 'john@example.com',
            'beneficiaryPhone' => '+919876543210',
        ]);
    }

    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->request->getData();

        $this->assertSame('BENE_001', $data['beneficiary_id']);
        $this->assertSame('John Doe', $data['beneficiary_name']);
        $this->assertSame('1234567890', $data['bank_account_number']);
        $this->assertSame('HDFC0001234', $data['bank_ifsc']);
        $this->assertSame('john@example.com', $data['beneficiary_contact_details']['beneficiary_email']);
        $this->assertSame('+919876543210', $data['beneficiary_contact_details']['beneficiary_phone']);
    }

    public function testGetDataWithUpiVpa(): void
    {
        $this->request->setBankAccount(null);
        $this->request->setBankIfsc(null);
        $this->request->setVpa('john@upi');

        $data = $this->request->getData();

        $this->assertArrayNotHasKey('bank_account_number', $data);
        $this->assertArrayNotHasKey('bank_ifsc', $data);
        $this->assertSame('john@upi', $data['vpa']);
    }

    public function testValidationRequiresBeneficiaryId(): void
    {
        $this->request->setBeneficiaryId(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresBeneficiaryName(): void
    {
        $this->request->setBeneficiaryName(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testSandboxEndpointUsed(): void
    {
        $this->setMockHttpResponse('CreateBeneficiarySuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://sandbox.cashfree.com/payout/beneficiary',
            (string) $lastRequest->getUri()
        );
    }

    public function testProductionEndpointUsed(): void
    {
        $this->request->setTestMode(false);
        $this->setMockHttpResponse('CreateBeneficiarySuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://api.cashfree.com/payout/beneficiary',
            (string) $lastRequest->getUri()
        );
    }

    public function testAuthHeaders(): void
    {
        $this->setMockHttpResponse('CreateBeneficiarySuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('payout_test_id', $lastRequest->getHeaderLine('x-client-id'));
        $this->assertSame('payout_test_secret', $lastRequest->getHeaderLine('x-client-secret'));
        $this->assertSame('2024-01-01', $lastRequest->getHeaderLine('x-api-version'));
    }

    public function testSuccessfulResponse(): void
    {
        $this->setMockHttpResponse('CreateBeneficiarySuccess.txt');
        $response = $this->request->send();

        $this->assertInstanceOf(CreateBeneficiaryResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('BENE_001', $response->getBeneficiaryId());
        $this->assertSame('VERIFIED', $response->getBeneficiaryStatus());
    }

    public function testErrorResponse(): void
    {
        $this->setMockHttpResponse('CreateBeneficiaryError.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('beneficiary_id_already_exists', $response->getCode());
    }
}
