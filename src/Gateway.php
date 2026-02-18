<?php

namespace Omnipay\Cashfree;

use Omnipay\Common\AbstractGateway;
use Omnipay\Cashfree\Message\PurchaseRequest;
use Omnipay\Cashfree\Message\CompletePurchaseRequest;

class Gateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'Cashfree';
    }

    public function getDefaultParameters(): array
    {
        return [
            'clientId' => '',
            'clientSecret' => '',
            'testMode' => false,
        ];
    }

    public function getClientId(): string
    {
        return $this->getParameter('clientId');
    }

    public function setClientId(string $value): self
    {
        return $this->setParameter('clientId', $value);
    }

    public function getClientSecret(): string
    {
        return $this->getParameter('clientSecret');
    }

    public function setClientSecret(string $value): self
    {
        return $this->setParameter('clientSecret', $value);
    }

    public function purchase(array $parameters = []): PurchaseRequest
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = []): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }
}
