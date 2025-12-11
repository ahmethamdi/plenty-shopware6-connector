<?php

namespace PlentyConnector\Core\Content\TokenProduct;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class TokenProductEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;
    protected ?string $description = null;
    protected int $tokenPrice;
    protected ?string $imageUrl = null;
    protected int $stock = 0;
    protected bool $active = true;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTokenPrice(): int
    {
        return $this->tokenPrice;
    }

    public function setTokenPrice(int $tokenPrice): void
    {
        $this->tokenPrice = $tokenPrice;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
