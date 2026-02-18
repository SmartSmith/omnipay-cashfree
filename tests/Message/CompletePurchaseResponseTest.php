<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\CompletePurchaseResponse;
use Omnipay\Tests\TestCase;
use Mockery;

class CompletePurchaseResponseTest extends TestCase
{
    public function testPaidStatusIsSuccessful(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'cf_order_id' => 'cf_123',
            'order_id' => 'ORDER_123',
            'order_status' => 'PAID',
            'order_amount' => 100.00,
            'order_currency' => 'INR',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('cf_123', $response->getTransactionReference());
        $this->assertSame('ORDER_123', $response->getTransactionId());
        $this->assertSame('Payment successful', $response->getMessage());
        $this->assertSame('PAID', $response->getCode());
        $this->assertSame('PAID', $response->getOrderStatus());
    }

    public function testActiveStatusIsPending(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'cf_order_id' => 'cf_123',
            'order_id' => 'ORDER_123',
            'order_status' => 'ACTIVE',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('Payment pending', $response->getMessage());
        $this->assertSame('ACTIVE', $response->getCode());
    }

    public function testExpiredStatusIsNotSuccessful(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'cf_order_id' => 'cf_123',
            'order_id' => 'ORDER_123',
            'order_status' => 'EXPIRED',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('Order expired', $response->getMessage());
        $this->assertSame('EXPIRED', $response->getCode());
    }

    public function testTerminatedStatusIsNotSuccessful(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'cf_order_id' => 'cf_123',
            'order_id' => 'ORDER_123',
            'order_status' => 'TERMINATED',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('Order terminated', $response->getMessage());
        $this->assertSame('TERMINATED', $response->getCode());
    }

    public function testUnknownStatusFallbackMessage(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'order_status' => 'SOMETHING_NEW',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Order status: SOMETHING_NEW', $response->getMessage());
    }

    public function testEmptyResponseHandling(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, []);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getOrderStatus());
    }

    public function testErrorResponseWithMessage(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'message' => 'order not found',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('order not found', $response->getMessage());
    }
}
