<?php

namespace PlentyConnector\Core\Content\TokenTransaction;

use PlentyConnector\Core\Content\Package\PackageEntity;
use PlentyConnector\Core\Content\TokenProduct\TokenProductEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TokenTransactionEntity extends Entity
{
    use EntityIdTrait;

    protected string $customerId;
    protected int $amount;
    protected string $type;
    protected ?string $packageId = null;
    protected ?string $tokenProductId = null;
    protected ?string $orderId = null;
    protected ?CustomerEntity $customer = null;
    protected ?PackageEntity $package = null;
    protected ?TokenProductEntity $tokenProduct = null;
    protected ?OrderEntity $order = null;

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getPackageId(): ?string
    {
        return $this->packageId;
    }

    public function setPackageId(?string $packageId): void
    {
        $this->packageId = $packageId;
    }

    public function getTokenProductId(): ?string
    {
        return $this->tokenProductId;
    }

    public function setTokenProductId(?string $tokenProductId): void
    {
        $this->tokenProductId = $tokenProductId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getPackage(): ?PackageEntity
    {
        return $this->package;
    }

    public function setPackage(?PackageEntity $package): void
    {
        $this->package = $package;
    }

    public function getTokenProduct(): ?TokenProductEntity
    {
        return $this->tokenProduct;
    }

    public function setTokenProduct(?TokenProductEntity $tokenProduct): void
    {
        $this->tokenProduct = $tokenProduct;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }
}
