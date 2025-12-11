<?php

namespace PlentyConnector\Core\Content\PackageProgress;

use PlentyConnector\Core\Content\Package\PackageEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PackageProgressEntity extends Entity
{
    use EntityIdTrait;

    protected string $customerId;
    protected string $packageId;
    protected float $currentAmount = 0.0;
    protected int $completedCycles = 0;
    protected ?\DateTimeInterface $lastResetAt = null;
    protected ?CustomerEntity $customer = null;
    protected ?PackageEntity $package = null;

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getPackageId(): string
    {
        return $this->packageId;
    }

    public function setPackageId(string $packageId): void
    {
        $this->packageId = $packageId;
    }

    public function getCurrentAmount(): float
    {
        return $this->currentAmount;
    }

    public function setCurrentAmount(float $currentAmount): void
    {
        $this->currentAmount = $currentAmount;
    }

    public function getCompletedCycles(): int
    {
        return $this->completedCycles;
    }

    public function setCompletedCycles(int $completedCycles): void
    {
        $this->completedCycles = $completedCycles;
    }

    public function getLastResetAt(): ?\DateTimeInterface
    {
        return $this->lastResetAt;
    }

    public function setLastResetAt(?\DateTimeInterface $lastResetAt): void
    {
        $this->lastResetAt = $lastResetAt;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getPackage(): ?PackageEntity
    {
        return $this->package;
    }

    public function setPackage(PackageEntity $package): void
    {
        $this->package = $package;
    }
}
