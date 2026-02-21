<?php

namespace Omnipay\Cashfree\Tests\Message\Payout;

use Omnipay\Cashfree\Message\Payout\ValidateBankAccountRequest;
use Omnipay\Cashfree\Message\Payout\ValidateBankAccountResponse;
use Omnipay\Tests\TestCase;

class ValidateBankAccountRequestTest extends TestCase
{
    private ValidateBankAccountRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new ValidateBankAccountRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'payoutClientId' => 'payout_test_id',
            'payoutClientSecret' => 'payout_test_secret',
            'testMode' => true,
            'bankAccount' => '1234567890',
            'bankIfsc' => 'HDFC0001234',
            'beneficiaryName' => 'John Doe',
        ]);
    }

    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->request->getData();

        $this->assertSame('1234567890', $data['bank_account']);
        $this->assertSame('HDFC0001234', $data['ifsc']);
        $this->assertSame('John Doe', $data['name']);
    }

    public function testValidationRequiresBankAccount(): void
    {
        $this->request->setBankAccount(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresIfsc(): void
    {
        $this->request->setBankIfsc(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresName(): void
    {
        $this->request->setBeneficiaryName(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testUsesGetMethod(): void
    {
        $this->setMockHttpResponse('ValidateBankAccountValid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
    }

    public function testQueryParametersInUrl(): void
    {
        $this->setMockHttpResponse('ValidateBankAccountValid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $uri = (string) $lastRequest->getUri();
        $this->assertStringContainsString('bank_account=1234567890', $uri);
        $this->assertStringContainsString('ifsc=HDFC0001234', $uri);
    }

    public function testValidAccountResponse(): void
    {
        $this->setMockHttpResponse('ValidateBankAccountValid.txt');
        $response = $this->request->send();

        $this->assertInstanceOf(ValidateBankAccountResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('VALID', $response->getAccountStatus());
        $this->assertSame('JOHN DOE', $response->getAccountHolderName());
    }

    public function testInvalidAccountResponse(): void
    {
        $this->setMockHttpResponse('ValidateBankAccountInvalid.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('INVALID', $response->getAccountStatus());
    }
}
