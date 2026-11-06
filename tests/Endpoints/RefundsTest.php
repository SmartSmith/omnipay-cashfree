<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Tests\Endpoints;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\Test;

/**
 * LAUNCH-196. Refunding a paid order and listing its refunds against Cashfree PG's sandbox, each
 * against the answers Cashfree gives: a refund done, a refund pending, a refund refused, a refund_id
 * Cashfree already holds, and an answer that is not JSON.
 */
final class RefundsTest extends EndpointTestCase
{
    #[Test]
    #[Calls('Gateway::refund')]
    public function refund_sends_the_refund_id_and_amount_and_reads_the_refund_done(): void
    {
        $this->setMockHttpResponse('RefundSuccess.txt');

        $response = $this->gateway->refund([
            'transactionId' => 'ORDER_123',
            'refundId' => 'sp_rf_0001',
            'amount' => '50.50',
            'currency' => 'INR',
            'refundNote' => 'Order cancelled',
        ])->send();

        $request = $this->sent();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/pg/orders/ORDER_123/refunds', (string) $request->getUri());
        $this->assertPgHeaders($request);
        $this->assertSame(['refund_id' => 'sp_rf_0001', 'refund_amount' => 50.5, 'refund_note' => 'Order cancelled'], $this->body($request));
        $this->assertTrue($response->isAccepted());
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isRefused());
        $this->assertSame('1553338', $response->getTransactionReference());
        $this->assertSame('SUCCESS', $response->getRefundStatus());
        $this->assertSame('sp_rf_0001', $response->getRefund()['refund_id']);
    }

    #[Test]
    public function refund_of_the_whole_order_without_a_note_reads_the_refund_pending(): void
    {
        $this->setMockHttpResponse('RefundPending.txt');

        $response = $this->gateway->refund(['transactionId' => 'ORDER_123', 'refundId' => 'sp_rf_0002', 'amount' => '100.00', 'currency' => 'INR'])->send();

        $this->assertSame(['refund_id' => 'sp_rf_0002', 'refund_amount' => 100], $this->body($this->sent()));
        $this->assertTrue($response->isAccepted());
        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('PENDING', $response->getRefundStatus());
    }

    #[Test]
    public function refund_over_the_paid_sum_is_read_as_refused(): void
    {
        $this->setMockHttpResponse('RefundRefused.txt');

        $response = $this->gateway->refund(['transactionId' => 'ORDER_123', 'refundId' => 'sp_rf_0003', 'amount' => '500.00', 'currency' => 'INR'])->send();

        $this->sent();
        $this->assertSame(400, $response->getHttpStatus());
        $this->assertTrue($response->isRefused());
        $this->assertFalse($response->isAccepted());
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertNull($response->getRefund());
        $this->assertSame('refund_amount_invalid', $response->getCode());
        $this->assertSame('invalid_request_error', $response->getErrorType());
        $this->assertStringStartsWith('refund_amount : should be less than or equal', (string) $response->getMessage());
    }

    #[Test]
    public function refund_under_a_refund_id_cashfree_already_holds_is_read_as_a_duplicate_not_a_refusal(): void
    {
        $this->setMockHttpResponse('RefundDuplicate.txt');

        $response = $this->gateway->refund(['transactionId' => 'ORDER_123', 'refundId' => 'sp_rf_0001', 'amount' => '50.50', 'currency' => 'INR'])->send();

        $this->sent();
        $this->assertTrue($response->isDuplicateRefundId());
        $this->assertFalse($response->isRefused());
        $this->assertFalse($response->isAccepted());
    }

    #[Test]
    public function refund_answered_with_something_that_is_not_json_is_raised(): void
    {
        $this->setMockHttpResponse('RefundServerError.txt');

        try {
            $this->gateway->refund(['transactionId' => 'ORDER_123', 'refundId' => 'sp_rf_0001', 'amount' => '50.50', 'currency' => 'INR'])->send();
            $this->fail('An answer that is not JSON was read as a refund answer.');
        } catch (InvalidResponseException $raised) {
            $this->assertSame('Cashfree answered HTTP 502 with a body that is not JSON.', $raised->getMessage());
        }
    }

    #[Test]
    public function refund_without_a_refund_id_is_refused_before_sending(): void
    {
        try {
            $this->gateway->refund(['transactionId' => 'ORDER_123', 'amount' => '50.50', 'currency' => 'INR'])->send();
            $this->fail('A refund with no refundId was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The refundId parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }

    #[Test]
    #[Calls('Gateway::fetchRefunds')]
    public function fetch_refunds_lists_every_refund_of_the_order(): void
    {
        $this->setMockHttpResponse('FetchRefundsSuccess.txt');

        $response = $this->gateway->fetchRefunds(['transactionId' => 'ORDER_123'])->send();

        $request = $this->sent();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://sandbox.cashfree.com/pg/orders/ORDER_123/refunds', (string) $request->getUri());
        $this->assertPgHeaders($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame(['sp_rf_0001', 'sp_rf_0002'], array_column($response->getRefunds(), 'refund_id'));
        $this->assertSame('PENDING', $response->getRefund('sp_rf_0002')['refund_status']);
        $this->assertNull($response->getRefund('sp_rf_9999'));
    }

    #[Test]
    public function fetch_refunds_of_an_order_with_none_is_an_empty_list(): void
    {
        $this->setMockHttpResponse('FetchRefundsEmpty.txt');

        $response = $this->gateway->fetchRefunds(['transactionId' => 'ORDER_123'])->send();

        $this->sent();
        $this->assertTrue($response->isSuccessful());
        $this->assertSame([], $response->getRefunds());
    }

    #[Test]
    public function fetch_refunds_of_an_unknown_order_is_an_error_whose_list_is_never_read_as_empty(): void
    {
        $this->setMockHttpResponse('FetchRefundsNotFound.txt');

        $response = $this->gateway->fetchRefunds(['transactionId' => 'ORDER_404'])->send();

        $this->sent();
        $this->assertFalse($response->isSuccessful());
        $this->assertSame(404, $response->getHttpStatus());
        $this->assertSame('order_not_found', $response->getCode());
        $this->expectException(InvalidResponseException::class);
        $response->getRefunds();
    }

    #[Test]
    public function fetch_refunds_without_an_order_id_is_refused_before_sending(): void
    {
        try {
            $this->gateway->fetchRefunds()->send();
            $this->fail('A fetchRefunds with no transactionId was sent.');
        } catch (InvalidRequestException $refused) {
            $this->assertSame('The transactionId parameter is required', $refused->getMessage());
        }
        $this->assertNothingSent();
    }
}
