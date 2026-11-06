<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\CompletePurchaseRequest;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Tests\TestCase;

class CompletePurchaseRequestTest extends TestCase
{
    private CompletePurchaseRequest $request;

    protected function setUp(): void
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

    public function test_get_data_returns_order_id(): void
    {
        $data = $this->request->getData();

        $this->assertSame('ORDER_123', $data['order_id']);
    }

    public function test_validation_requires_transaction_id(): void
    {
        $this->request->setTransactionId(null);
        $this->expectException(InvalidRequestException::class);
        $this->request->getData();
    }

    public function test_send_data_calls_correct_endpoint(): void
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

    public function test_sandbox_endpoint_used_in_test_mode(): void
    {
        $this->setMockHttpResponse('CompletePurchasePaid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertStringStartsWith(
            'https://sandbox.cashfree.com/pg/orders/',
            (string) $lastRequest->getUri()
        );
    }

    public function test_auth_headers_included(): void
    {
        $this->setMockHttpResponse('CompletePurchasePaid.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('test_app_id', $lastRequest->getHeaderLine('x-client-id'));
        $this->assertSame('test_secret', $lastRequest->getHeaderLine('x-client-secret'));
        $this->assertSame('2023-08-01', $lastRequest->getHeaderLine('x-api-version'));
    }

    /** LAUNCH-197: an ACTIVE order whose latest attempt FAILED reads as failed, with Cashfree's reason. */
    public function test_active_order_with_failed_attempt_is_failed(): void
    {
        $this->setMockHttpResponse(['CompletePurchaseActive.txt', 'FetchPaymentDetailsFailed.txt']);
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('Insufficient funds in the account', $response->getMessage());
        $this->assertSame('FAILED', $response->getCode());
        $this->assertSame('ACTIVE', $response->getOrderStatus());
        $this->assertSame('cf_123456', $response->getTransactionReference());
        $requests = $this->getMockClient()->getRequests();
        $this->assertCount(2, $requests);
        $this->assertSame('https://sandbox.cashfree.com/pg/orders/ORDER_123/payments', (string) $requests[1]->getUri());
        $this->assertSame('test_secret', $requests[1]->getHeaderLine('x-client-secret'));
    }

    /** LAUNCH-197: a dropped attempt is over too. */
    public function test_active_order_with_user_dropped_attempt_is_failed(): void
    {
        $this->setMockHttpResponse(['CompletePurchaseActive.txt', 'FetchPaymentDetailsUserDropped.txt']);
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('Customer dropped the payment', $response->getMessage());
        $this->assertSame('USER_DROPPED', $response->getCode());
    }

    /** An ACTIVE order with no attempt yet stays pending. */
    public function test_active_order_with_no_attempt_is_pending(): void
    {
        $this->setMockHttpResponse(['CompletePurchaseActive.txt', 'FetchPaymentDetailsEmpty.txt']);
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('Payment pending', $response->getMessage());
        $this->assertSame('ACTIVE', $response->getCode());
        $this->assertNull($response->getLatestPayment());
    }

    /** An ACTIVE order whose attempt is pending stays pending. */
    public function test_active_order_with_pending_attempt_is_pending(): void
    {
        $this->setMockHttpResponse(['CompletePurchaseActive.txt', 'FetchPaymentDetailsPending.txt']);
        $response = $this->request->send();

        $this->assertTrue($response->isPending());
        $this->assertSame('Payment pending', $response->getMessage());
    }

    /** The latest attempt is the one with the highest cf_payment_id, wherever Cashfree lists it: a retry after a failure is pending. */
    public function test_retry_after_failed_attempt_is_pending(): void
    {
        $this->setMockHttpResponse(['CompletePurchaseActive.txt', 'FetchPaymentDetailsPendingAfterFailed.txt']);
        $response = $this->request->send();

        $this->assertTrue($response->isPending());
        $this->assertSame('PENDING', $response->getLatestPayment()['payment_status']);
    }

    /** A PAID order is successful from the order read alone; the payments are not read. */
    public function test_paid_order_is_successful_without_reading_payments(): void
    {
        $this->setMockHttpResponse('CompletePurchasePaid.txt');
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame('Payment successful', $response->getMessage());
        $this->assertCount(1, $this->getMockClient()->getRequests());
    }

    /** A payments answer that is not a list of payments throws rather than reading as pending. */
    public function test_payments_answer_that_is_not_a_list_throws(): void
    {
        $this->setMockHttpResponse(['CompletePurchaseActive.txt', 'FetchPaymentDetailsNotAList.txt']);

        $this->expectException(InvalidResponseException::class);
        $this->request->send();
    }
}
