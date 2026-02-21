<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\FetchPaymentDetailsRequest;
use Omnipay\Tests\TestCase;

class FetchPaymentDetailsRequestTest extends TestCase
{
    private FetchPaymentDetailsRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new FetchPaymentDetailsRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'clientId' => 'test_app_id',
            'clientSecret' => 'test_secret',
            'testMode' => true,
            'transactionId' => 'ORDER_123',
        ]);
    }

    public function testGetDataReturnsOrderId(): void
    {
        $data = $this->request->getData();

        $this->assertSame('ORDER_123', $data['order_id']);
    }

    public function testValidationRequiresTransactionId(): void
    {
        $this->request->setTransactionId(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testSendDataCallsCorrectEndpoint(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertStringContainsString(
            '/pg/orders/ORDER_123/payments',
            (string) $lastRequest->getUri()
        );
    }

    public function testSandboxEndpointUsedInTestMode(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://sandbox.cashfree.com/pg/orders/',
            (string) $lastRequest->getUri()
        );
    }

    public function testAuthHeadersIncluded(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('test_app_id', $lastRequest->getHeaderLine('x-client-id'));
        $this->assertSame('test_secret', $lastRequest->getHeaderLine('x-client-secret'));
        $this->assertSame('2023-08-01', $lastRequest->getHeaderLine('x-api-version'));
    }

    public function testSuccessfulResponseParsesPaymentDetails(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsSuccess.txt');
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('5114925505463', $response->getBankReference());
        $this->assertSame('945.87', $response->getServiceCharge());
        $this->assertSame('170.26', $response->getServiceTax());
        $this->assertSame('upi', $response->getPaymentMethod());
        $this->assertSame('12345678', $response->getTransactionReference());
    }

    public function testEmptyResponseIsNotSuccessful(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsEmpty.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertNull($response->getBankReference());
        $this->assertNull($response->getServiceCharge());
        $this->assertNull($response->getPaymentMethod());
    }

    public function testPendingPaymentIsNotSuccessful(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsPending.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertNull($response->getBankReference());
    }
}
