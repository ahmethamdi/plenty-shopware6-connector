<?php

namespace PlentyConnector\Core\Content\TokenOrder;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                   add(TokenOrderEntity $entity)
 * @method void                   set(string $key, TokenOrderEntity $entity)
 * @method TokenOrderEntity[]    getIterator()
 * @method TokenOrderEntity[]    getElements()
 * @method TokenOrderEntity|null get(string $key)
 * @method TokenOrderEntity|null first()
 * @method TokenOrderEntity|null last()
 */
class TokenOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TokenOrderEntity::class;
    }
}
