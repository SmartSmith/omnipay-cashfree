<?php

declare(strict_types=1);

namespace Omnipay\Cashfree\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Reads Cashfree's JSON answer body. An answer that is not JSON is not an answer the caller can act
 * on, so it is raised, never read as an empty answer.
 */
final class CashfreeAnswer
{
    /** @return array<array-key, mixed> */
    public static function decode(int $httpStatus, string $body): array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $notJson) {
            throw new InvalidResponseException('Cashfree answered HTTP '.$httpStatus.' with a body that is not JSON.', 0, $notJson);
        }

        if (! is_array($decoded)) {
            throw new InvalidResponseException('Cashfree answered HTTP '.$httpStatus.' with JSON that is not an object or a list.');
        }

        return $decoded;
    }
}
