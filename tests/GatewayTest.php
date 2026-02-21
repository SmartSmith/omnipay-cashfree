<?php

namespace Omnipay\Cashfree\Tests;

use Omnipay\Cashfree\Gateway;
use Omnipay\Cashfree\Message\PurchaseRequest;
use Omnipay\Cashfree\Message\CompletePurchaseRequest;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /** @var Gateway */
    protected $gateway;

    public function setUp(): void
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'clientId' => 'test_app_id',
            'clientSecret' => 'test_secret_key',
            'testMode' => true,
        ]);
    }

    public function testGatewayName(): void
    {
        $this->assertSame('Cashfree', $this->gateway->getName());
    }

    public function testDefaultParameters(): void
    {
        $defaults = $this->gateway->getDefaultParameters();

        $this->assertArrayHasKey('clientId', $defaults);
        $this->assertArrayHasKey('clientSecret', $defaults);
        $this->assertArrayHasKey('testMode', $defaults);
    }

    public function testGetSetClientId(): void
    {
        $this->gateway->setClientId('my_app_id');
        $this->assertSame('my_app_id', $this->gateway->getClientId());
    }

    public function testGetSetClientSecret(): void
    {
        $this->gateway->setClientSecret('my_secret');
        $this->assertSame('my_secret', $this->gateway->getClientSecret());
    }

    public function testPurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->purchase([
            'amount' => '100.00',
            'currency' => 'INR',
            'transactionId' => 'ORDER_123',
        ]);

        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function testCompletePurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->completePurchase([
            'transactionId' => 'ORDER_123',
        ]);

        $this->assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function testPurchaseInheritsGatewayCredentials(): void
    {
        $request = $this->gateway->purchase([
            'amount' => '100.00',
            'currency' => 'INR',
            'transactionId' => 'ORDER_123',
        ]);

        $this->assertSame('test_app_id', $request->getClientId());
        $this->assertSame('test_secret_key', $request->getClientSecret());
        $this->assertTrue($request->getTestMode());
    }
}
