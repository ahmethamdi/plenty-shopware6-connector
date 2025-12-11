<?php

namespace PlentyConnector\Core\Content\TokenOrder;

use PlentyConnector\Core\Content\TokenProduct\TokenProductEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TokenOrderEntity extends Entity
{
    use EntityIdTrait;

    protected string $customerId;
    protected string $tokenProductId;
    protected int $tokenAmount;
    protected string $status = 'pending';
    protected ?array $customerAddress = null;
    protected ?CustomerEntity $customer = null;
    protected ?TokenProductEntity $tokenProduct = null;

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getTokenProductId(): string
    {
        return $this->tokenProductId;
    }

    public function setTokenProductId(string $tokenProductId): void
    {
        $this->tokenProductId = $tokenProductId;
    }

    public function getTokenAmount(): int
    {
        return $this->tokenAmount;
    }

    public function setTokenAmount(int $tokenAmount): void
    {
        $this->tokenAmount = $tokenAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCustomerAddress(): ?array
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?array $customerAddress): void
    {
        $this->customerAddress = $customerAddress;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getTokenProduct(): ?TokenProductEntity
    {
        return $this->tokenProduct;
    }

    public function setTokenProduct(?TokenProductEntity $tokenProduct): void
    {
        $this->tokenProduct = $tokenProduct;
    }
}
