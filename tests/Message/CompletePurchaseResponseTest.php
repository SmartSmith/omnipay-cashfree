<?php

namespace Omnipay\Cashfree\Tests\Message;

use Omnipay\Cashfree\Message\CompletePurchaseResponse;
use Mockery;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

    /**
     * JUDGE-2376: an ACTIVE order is pending only while its latest attempt is in flight.
     *
     * @return array<string, array{string}>
     */
    public static function inFlightAttemptStatuses(): array
    {
        return [
            'a pending attempt' => ['PENDING'],
            'an attempt Cashfree has the payment for but has not yet marked the order PAID' => ['SUCCESS'],
        ];
    }

    #[DataProvider('inFlightAttemptStatuses')]
    public function test_active_order_whose_attempt_is_in_flight_is_pending(string $attemptStatus): void
    {
        $response = $this->activeOrder(['cf_payment_id' => 1, 'payment_status' => $attemptStatus]);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('Payment pending', $response->getMessage());
        $this->assertSame('ACTIVE', $response->getCode());
        $this->assertSame($attemptStatus, $response->getLatestPayment()['payment_status']);
    }

    /**
     * JUDGE-2376: an ACTIVE order whose latest attempt ended without payment is over for this attempt.
     *
     * @return array<string, array{string}>
     */
    public static function endedAttemptStatuses(): array
    {
        return [
            'a failed attempt' => ['FAILED'],
            'an attempt the buyer dropped' => ['USER_DROPPED'],
            'an attempt never made' => ['NOT_ATTEMPTED'],
            'a voided attempt' => ['VOID'],
            'a cancelled attempt' => ['CANCELLED'],
        ];
    }

    #[DataProvider('endedAttemptStatuses')]
    public function test_active_order_whose_attempt_ended_without_payment_is_ended_with_cashfrees_reason(string $attemptStatus): void
    {
        $response = $this->activeOrder([
            'cf_payment_id' => 1,
            'payment_status' => $attemptStatus,
            'payment_message' => 'Reason Cashfree gave',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertSame($attemptStatus, $response->getCode());
        $this->assertSame('Reason Cashfree gave', $response->getMessage());
        $this->assertSame('ACTIVE', $response->getOrderStatus());
    }

    #[DataProvider('endedAttemptStatuses')]
    public function test_active_order_whose_attempt_ended_without_a_reason_says_how_the_attempt_ended(string $attemptStatus): void
    {
        foreach ([[], ['payment_message' => '']] as $noReason) {
            $response = $this->activeOrder(['cf_payment_id' => 1, 'payment_status' => $attemptStatus] + $noReason);

            $this->assertFalse($response->isSuccessful());
            $this->assertFalse($response->isPending());
            $this->assertSame($attemptStatus, $response->getCode());
            $this->assertSame('Payment attempt '.$attemptStatus, $response->getMessage());
        }
    }

    /**
     * JUDGE-2376: an ACTIVE order whose attempt cannot be read throws rather than guessing pending.
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public static function activeOrdersWhoseAttemptCannotBeRead(): array
    {
        $order = ['cf_order_id' => 'cf_123', 'order_id' => 'ORDER_123', 'order_status' => 'ACTIVE'];

        return [
            'read without its payments' => [$order],
            'an attempt status this package does not know' => [$order + ['latest_payment' => ['cf_payment_id' => 1, 'payment_status' => 'SOMETHING_NEW']]],
            'an attempt with no status' => [$order + ['latest_payment' => ['cf_payment_id' => 1]]],
            'a latest payment that is not a payment' => [$order + ['latest_payment' => 'FAILED']],
        ];
    }

    /** @param array<string, mixed> $order */
    #[DataProvider('activeOrdersWhoseAttemptCannotBeRead')]
    public function test_active_order_whose_attempt_cannot_be_read_throws(array $order): void
    {
        $this->expectException(InvalidResponseException::class);
        new CompletePurchaseResponse(Mockery::mock(RequestInterface::class), $order);
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

    public function testErrorResponseWithMessage(): void
    {
        $request = Mockery::mock(\Omnipay\Common\Message\RequestInterface::class);
        $response = new CompletePurchaseResponse($request, [
            'message' => 'order not found',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('order not found', $response->getMessage());
    }

    /** @param array<string, mixed> $latestPayment */
    private function activeOrder(array $latestPayment): CompletePurchaseResponse
    {
        return new CompletePurchaseResponse(Mockery::mock(RequestInterface::class), [
            'cf_order_id' => 'cf_123',
            'order_id' => 'ORDER_123',
            'order_status' => 'ACTIVE',
            'latest_payment' => $latestPayment,
        ]);
    }
}
