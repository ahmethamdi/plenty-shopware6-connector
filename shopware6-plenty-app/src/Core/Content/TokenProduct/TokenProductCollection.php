<?php

namespace PlentyConnector\Core\Content\TokenProduct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                     add(TokenProductEntity $entity)
 * @method void                     set(string $key, TokenProductEntity $entity)
 * @method TokenProductEntity[]    getIterator()
 * @method TokenProductEntity[]    getElements()
 * @method TokenProductEntity|null get(string $key)
 * @method TokenProductEntity|null first()
 * @method TokenProductEntity|null last()
 */
class TokenProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TokenProductEntity::class;
    }
}
