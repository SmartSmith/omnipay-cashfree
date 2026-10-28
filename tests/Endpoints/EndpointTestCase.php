<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Tests\Endpoints;

use Omnipay\Common\GatewayFactory;
use Omnipay\Common\GatewayInterface;
use Omnipay\Tests\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * The gateway as a consuming app holds it: made by Omnipay's factory by name, initialised with both
 * pairs of credentials in test mode, and its HTTP client the mock client, which answers with the
 * fixtures in Mock/ and records every request sent.
 */
abstract class EndpointTestCase extends TestCase
{
    protected const string PG_CLIENT_ID = 'pg_app_id';

    protected const string PG_CLIENT_SECRET = 'pg_secret_key';

    protected const string PAYOUT_CLIENT_ID = 'payout_app_id';

    protected const string PAYOUT_CLIENT_SECRET = 'payout_secret_key';

    protected GatewayInterface $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = (new GatewayFactory)->create('Cashfree', $this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'clientId' => self::PG_CLIENT_ID,
            'clientSecret' => self::PG_CLIENT_SECRET,
            'payoutClientId' => self::PAYOUT_CLIENT_ID,
            'payoutClientSecret' => self::PAYOUT_CLIENT_SECRET,
            'testMode' => true,
        ]);
    }

    /** The one request the operation sent. */
    protected function sent(): RequestInterface
    {
        $requests = $this->getMockClient()->getRequests();
        $this->assertCount(1, $requests, 'The operation sent '.count($requests).' requests, expected one.');

        return $requests[0];
    }

    protected function assertNothingSent(): void
    {
        $this->assertSame([], $this->getMockClient()->getRequests(), 'A refused operation reached Cashfree.');
    }

    protected function assertPgHeaders(RequestInterface $request): void
    {
        $this->assertSame(self::PG_CLIENT_ID, $request->getHeaderLine('x-client-id'));
        $this->assertSame(self::PG_CLIENT_SECRET, $request->getHeaderLine('x-client-secret'));
        $this->assertSame('2023-08-01', $request->getHeaderLine('x-api-version'));
    }

    protected function assertPayoutHeaders(RequestInterface $request): void
    {
        $this->assertSame(self::PAYOUT_CLIENT_ID, $request->getHeaderLine('x-client-id'));
        $this->assertSame(self::PAYOUT_CLIENT_SECRET, $request->getHeaderLine('x-client-secret'));
        $this->assertSame('2024-01-01', $request->getHeaderLine('x-api-version'));
    }

    /** @return array<array-key, mixed> */
    protected function body(RequestInterface $request): array
    {
        return json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
