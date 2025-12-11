<?php

namespace PlentyConnector\Core\Content\Package;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use PlentyConnector\Core\Content\PackageProgress\PackageProgressCollection;

class PackageEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;
    protected float $targetAmount;
    protected int $tokenReward;
    protected bool $active = true;
    protected ?array $categoryIds = [];
    protected string $visibilityType = 'all';
    protected ?array $allowedCustomerIds = [];
    protected ?array $excludedCustomerIds = [];
    protected ?PackageProgressCollection $progresses = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTargetAmount(): float
    {
        return $this->targetAmount;
    }

    public function setTargetAmount(float $targetAmount): void
    {
        $this->targetAmount = $targetAmount;
    }

    public function getTokenReward(): int
    {
        return $this->tokenReward;
    }

    public function setTokenReward(int $tokenReward): void
    {
        $this->tokenReward = $tokenReward;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCategoryIds(): ?array
    {
        return $this->categoryIds;
    }

    public function setCategoryIds(?array $categoryIds): void
    {
        $this->categoryIds = $categoryIds;
    }

    public function getVisibilityType(): ?string
    {
        return $this->visibilityType;
    }

    public function setVisibilityType(?string $visibilityType): void
    {
        $this->visibilityType = $visibilityType;
    }

    public function getAllowedCustomerIds(): ?array
    {
        return $this->allowedCustomerIds;
    }

    public function setAllowedCustomerIds(?array $allowedCustomerIds): void
    {
        $this->allowedCustomerIds = $allowedCustomerIds;
    }

    public function getExcludedCustomerIds(): ?array
    {
        return $this->excludedCustomerIds;
    }

    public function setExcludedCustomerIds(?array $excludedCustomerIds): void
    {
        $this->excludedCustomerIds = $excludedCustomerIds;
    }

    public function getProgresses(): ?PackageProgressCollection
    {
        return $this->progresses;
    }

    public function setProgresses(PackageProgressCollection $progresses): void
    {
        $this->progresses = $progresses;
    }
}
