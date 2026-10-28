<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Tests\Endpoints;

use Omnipay\Common\Exception\InvalidRequestException;
use PHPUnit\Framework\Attributes\Test;

/**
 * The payment gateway operations against Cashfree PG's sandbox: creating an order, reading it back
 * after the customer returns, and reading its payments. Each runs once for its success answer and
 * once for each failure answer Cashfree gives, and a request missing what it needs is refused
 * before anything is sent.
 */
final class PaymentGatewayTest extends EndpointTestCase
{
    #[Test]
    #[Calls('Gateway::purchase')]
    public function purchase_creates_the_order_and_redirects_to_its_payment_page(): void
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $response = $this->gateway->purchase([
            'amount' => '100.00',
            'currency' => 'INR',
            'transactionId' => 'ORDER_123',
            'customerId' => 'CUST_1',
            'customerEmail' => 'buyer@example.com',
            'customerPhone' => '9999999999',
            'customerName' => 'Buyer',
            'returnUrl' => 'https://shop.example.com/return',
            'notifyUrl' => 'https://shop.example.com/notify',
        ])->send();

        $request = $this->sent();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/pg/orders', (string) $request->getUri());
        $this->assertPgHeaders($request);
        $this->assertSame([
            'order_id' => 'ORDER_123',
            'order_amount' => 100,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'CUST_1',
                'customer_email' => 'buyer@example.com',
                'customer_phone' => '9999999999',
                'customer_name' => 'Buyer',
            ],
            'order_meta' => [
                'return_url' => 'https://shop.example.com/return?order_id={order_id}',
                'notify_url' => 'https://shop.example.com/notify',
            ],
        ], $this->body($request));
        $this->assertFalse($response->isSuccessful(), 'An order awaiting payment is a redirect, not yet a success.');
        $this->assertTrue($response->isRedirect());
        $this->assertSame('https://sandbox.cashfree.com/pg/view/session_abc123', $response->getRedirectUrl());
        $this->assertSame('cf_123456', $response->getTransactionReference());
        $this->assertSame('session_abc123', $response->getPaymentSessionId());
    }

    #[Test]
    public function purchase_reports_an_order_id_cashfree_already_holds(): void
    {
        $this->setMockHttpResponse('PurchaseError.txt');

        $response = $this->gateway->purchase(['amount' => '100.00', 'currency' => 'INR', 'transactionId' => 'ORDER_123'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('order_id already exists', $response->getMessage());
        $this->assertSame('order_id_already_exists', $response->getCode());
    }

    #[Test]
    public function purchase_without_an_order_id_is_refused_before_sending(): void
    {
        try {
            $this->gateway->purchase(['amount' => '100.00', 'currency' => 'INR'])->send();
            $this->fail('A purchase with no transactionId was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The transactionId parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }

    #[Test]
    #[Calls('Gateway::completePurchase')]
    public function complete_purchase_reads_a_paid_order(): void
    {
        $this->setMockHttpResponse('CompletePurchasePaid.txt');

        $response = $this->gateway->completePurchase(['transactionId' => 'ORDER_123'])->send();

        $request = $this->sent();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/pg/orders/ORDER_123', (string) $request->getUri());
        $this->assertPgHeaders($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('cf_123456', $response->getTransactionReference());
        $this->assertSame('ORDER_123', $response->getTransactionId());
        $this->assertSame('Payment successful', $response->getMessage());
    }

    #[Test]
    public function complete_purchase_reads_an_order_still_awaiting_payment_as_pending(): void
    {
        $this->setMockHttpResponse('CompletePurchaseActive.txt');

        $response = $this->gateway->completePurchase(['transactionId' => 'ORDER_123'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('Payment pending', $response->getMessage());
    }

    #[Test]
    public function complete_purchase_reads_an_expired_order_as_failed(): void
    {
        $this->setMockHttpResponse('CompletePurchaseExpired.txt');

        $response = $this->gateway->completePurchase(['transactionId' => 'ORDER_123'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('Order expired', $response->getMessage());
        $this->assertSame('EXPIRED', $response->getCode());
    }

    #[Test]
    public function complete_purchase_without_an_order_id_is_refused_before_sending(): void
    {
        try {
            $this->gateway->completePurchase()->send();
            $this->fail('A completePurchase with no transactionId was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The transactionId parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }

    #[Test]
    #[Calls('Gateway::fetchPaymentDetails')]
    public function fetch_payment_details_reads_the_successful_payment(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsSuccess.txt');

        $response = $this->gateway->fetchPaymentDetails(['transactionId' => 'ORDER_123'])->send();

        $request = $this->sent();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/pg/orders/ORDER_123/payments', (string) $request->getUri());
        $this->assertPgHeaders($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('12345678', $response->getTransactionReference());
        $this->assertSame('5114925505463', $response->getBankReference());
        $this->assertSame('945.87', $response->getServiceCharge());
        $this->assertSame('170.26', $response->getServiceTax());
        $this->assertSame('upi', $response->getPaymentMethod());
    }

    #[Test]
    public function fetch_payment_details_reads_a_pending_payment_as_not_successful(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsPending.txt');

        $response = $this->gateway->fetchPaymentDetails(['transactionId' => 'ORDER_123'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('Payment is pending', $response->getMessage());
    }

    #[Test]
    public function fetch_payment_details_reads_an_order_with_no_payments_as_not_successful(): void
    {
        $this->setMockHttpResponse('FetchPaymentDetailsEmpty.txt');

        $response = $this->gateway->fetchPaymentDetails(['transactionId' => 'ORDER_123'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('No payment data available', $response->getMessage());
    }

    #[Test]
    public function fetch_payment_details_without_an_order_id_is_refused_before_sending(): void
    {
        try {
            $this->gateway->fetchPaymentDetails()->send();
            $this->fail('A fetchPaymentDetails with no transactionId was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The transactionId parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }
}
