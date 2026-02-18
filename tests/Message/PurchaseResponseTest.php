<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\PurchaseResponse;
use Omnipay\Tests\TestCase;
use Mockery;

class PurchaseResponseTest extends TestCase
{
    public function testSuccessfulOrderCreation(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new PurchaseResponse($request, [
            'cf_order_id' => 'cf_123456',
            'order_id' => 'ORDER_123',
            'payment_session_id' => 'session_abc123',
            'payments_url' => 'https://sandbox.cashfree.com/pg/view/session_abc123',
            'order_status' => 'ACTIVE',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('https://sandbox.cashfree.com/pg/view/session_abc123', $response->getRedirectUrl());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertSame([], $response->getRedirectData());
        $this->assertSame('cf_123456', $response->getTransactionReference());
        $this->assertSame('ORDER_123', $response->getTransactionId());
        $this->assertSame('session_abc123', $response->getPaymentSessionId());
    }

    public function testIsRedirectFalseWhenNoSessionId(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new PurchaseResponse($request, [
            'message' => 'order_id already exists',
            'code' => 'order_id_already_exists',
            'type' => 'invalid_request_error',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getRedirectUrl());
        $this->assertNull($response->getTransactionReference());
    }

    public function testGetMessageReturnsErrorMessage(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new PurchaseResponse($request, [
            'message' => 'order_id already exists',
            'code' => 'order_id_already_exists',
            'type' => 'invalid_request_error',
        ]);

        $this->assertSame('order_id already exists', $response->getMessage());
        $this->assertSame('order_id_already_exists', $response->getCode());
    }

    public function testGetMessageReturnsOrderStatus(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new PurchaseResponse($request, [
            'cf_order_id' => 'cf_123',
            'order_id' => 'ORDER_123',
            'payment_session_id' => 'sess_123',
            'order_status' => 'ACTIVE',
        ]);

        $this->assertSame('Order status: ACTIVE', $response->getMessage());
    }

    public function testGetCodeFallsBackToType(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new PurchaseResponse($request, [
            'type' => 'authentication_error',
        ]);

        $this->assertSame('authentication_error', $response->getCode());
    }
}
