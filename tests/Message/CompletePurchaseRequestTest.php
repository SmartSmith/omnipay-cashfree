<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\CompletePurchaseRequest;
use Omnipay\Tests\TestCase;

class CompletePurchaseRequestTest extends TestCase
{
    private CompletePurchaseRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
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
        $this->setMockHttpResponse('CompletePurchasePaid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertStringContainsString(
            '/pg/orders/ORDER_123',
            (string) $lastRequest->getUri()
        );
    }

    public function testSandboxEndpointUsedInTestMode(): void
    {
        $this->setMockHttpResponse('CompletePurchasePaid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://sandbox.cashfree.com/pg/orders/',
            (string) $lastRequest->getUri()
        );
    }

    public function testAuthHeadersIncluded(): void
    {
        $this->setMockHttpResponse('CompletePurchasePaid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('test_app_id', $lastRequest->getHeaderLine('x-client-id'));
        $this->assertSame('test_secret', $lastRequest->getHeaderLine('x-client-secret'));
        $this->assertSame('2023-08-01', $lastRequest->getHeaderLine('x-api-version'));
    }
}
