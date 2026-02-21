<?php

namespace Omnipay\Cashfree\Tests\Message\Payout;

use Omnipay\Cashfree\Message\Payout\PayoutStatusRequest;
use Omnipay\Cashfree\Message\Payout\PayoutStatusResponse;
use Omnipay\Tests\TestCase;

class PayoutStatusRequestTest extends TestCase
{
    private PayoutStatusRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new PayoutStatusRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize([
            'payoutClientId' => 'payout_test_id',
            'payoutClientSecret' => 'payout_test_secret',
            'testMode' => true,
            'transactionId' => 'TXN_PAY_202602_001',
        ]);
    }

    public function testGetDataRequiresEitherTransferIdOrCfTransferId(): void
    {
        $this->request->setTransactionId(null);
        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->request->getData();
    }

    public function testQueryByTransferId(): void
    {
        $this->setMockHttpResponse('PayoutStatusSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $uri = (string) $lastRequest->getUri();
        $this->assertStringContainsString('transfer_id=TXN_PAY_202602_001', $uri);
    }

    public function testQueryByCfTransferId(): void
    {
        $this->request->setTransactionId(null);
        $this->request->setCfTransferId('CF_TRANSFER_001');

        $this->setMockHttpResponse('PayoutStatusSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $uri = (string) $lastRequest->getUri();
        $this->assertStringContainsString('cf_transfer_id=CF_TRANSFER_001', $uri);
    }

    public function testSuccessResponse(): void
    {
        $this->setMockHttpResponse('PayoutStatusSuccess.txt');
        $response = $this->request->send();

        $this->assertInstanceOf(PayoutStatusResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isFailed());
        $this->assertSame('SUCCESS', $response->getStatus());
        $this->assertSame('UTR123456789', $response->getUtr());
        $this->assertSame('CF_TRANSFER_001', $response->getTransactionReference());
    }

    public function testPendingResponse(): void
    {
        $this->setMockHttpResponse('PayoutStatusPending.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isPending());
        $this->assertSame('PENDING', $response->getStatus());
    }

    public function testUsesGetMethod(): void
    {
        $this->setMockHttpResponse('PayoutStatusSuccess.txt');
        $this->request->send();

        $lastRequest = $this->getMockClient()->getLastRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
    }
}
