<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\PurchaseRequest;
use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    private PurchaseRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'clientId' => 'test_app_id',
            'clientSecret' => 'test_secret',
            'testMode' => true,
            'amount' => '100.00',
            'currency' => 'INR',
            'transactionId' => 'ORDER_123',
            'returnUrl' => 'https://example.com/callback',
            'customerEmail' => 'test@example.com',
            'customerPhone' => '9876543210',
            'customerName' => 'John Doe',
            'customerId' => 'CUST_001',
        ]);
    }

    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->request->getData();

        $this->assertSame('ORDER_123', $data['order_id']);
        $this->assertSame(100.00, $data['order_amount']);
        $this->assertSame('INR', $data['order_currency']);
        $this->assertArrayHasKey('customer_details', $data);
        $this->assertArrayHasKey('order_meta', $data);
    }

    public function testCustomerDetailsMapping(): void
    {
        $data = $this->request->getData();
        $customer = $data['customer_details'];

        $this->assertSame('CUST_001', $customer['customer_id']);
        $this->assertSame('test@example.com', $customer['customer_email']);
        $this->assertSame('9876543210', $customer['customer_phone']);
        $this->assertSame('John Doe', $customer['customer_name']);
    }

    public function testCustomerIdDefaultsToTransactionId(): void
    {
        $this->request->setCustomerId('');
        $data = $this->request->getData();

        $this->assertSame('ORDER_123', $data['customer_details']['customer_id']);
    }

    public function testReturnUrlIncludesOrderIdTemplate(): void
    {
        $data = $this->request->getData();

        $this->assertSame(
            'https://example.com/callback?order_id={order_id}',
            $data['order_meta']['return_url']
        );
    }

    public function testNotifyUrlIncludedWhenSet(): void
    {
        $this->request->setNotifyUrl('https://example.com/notify');
        $data = $this->request->getData();

        $this->assertSame('https://example.com/notify', $data['order_meta']['notify_url']);
    }

    public function testPaymentMethodsIncludedWhenSet(): void
    {
        $this->request->setPaymentMethods('upi,nb,wallet,card');
        $data = $this->request->getData();

        $this->assertSame('upi,nb,wallet,card', $data['order_meta']['payment_methods']);
    }

    public function testPaymentMethodsOmittedWhenNotSet(): void
    {
        $data = $this->request->getData();

        $this->assertArrayNotHasKey('payment_methods', $data['order_meta']);
    }

    public function testOrderNoteIncludedWhenDescriptionSet(): void
    {
        $this->request->setDescription('Test order note');
        $data = $this->request->getData();

        $this->assertSame('Test order note', $data['order_note']);
    }

    public function testSandboxEndpointUsedInTestMode(): void
    {
        $this->request->setTestMode(true);
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $response = $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://sandbox.cashfree.com/pg/orders',
            (string) $lastRequest->getUri()
        );
    }

    public function testProductionEndpointUsedWhenNotTestMode(): void
    {
        $this->request->setTestMode(false);
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $response = $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://api.cashfree.com/pg/orders',
            (string) $lastRequest->getUri()
        );
    }

    public function testAuthHeadersIncluded(): void
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('test_app_id', $lastRequest->getHeaderLine('x-client-id'));
        $this->assertSame('test_secret', $lastRequest->getHeaderLine('x-client-secret'));
        $this->assertSame('2023-08-01', $lastRequest->getHeaderLine('x-api-version'));
        $this->assertSame('application/json', $lastRequest->getHeaderLine('Content-Type'));
    }

    public function testValidationRequiresAmount(): void
    {
        $this->request->setAmount(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresCurrency(): void
    {
        $this->request->setCurrency(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testValidationRequiresTransactionId(): void
    {
        $this->request->setTransactionId(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }
}
