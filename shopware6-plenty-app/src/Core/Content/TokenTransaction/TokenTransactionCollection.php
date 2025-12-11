<?php

namespace PlentyConnector\Core\Content\TokenTransaction;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(TokenTransactionEntity $entity)
 * @method void                         set(string $key, TokenTransactionEntity $entity)
 * @method TokenTransactionEntity[]    getIterator()
 * @method TokenTransactionEntity[]    getElements()
 * @method TokenTransactionEntity|null get(string $key)
 * @method TokenTransactionEntity|null first()
 * @method TokenTransactionEntity|null last()
 */
class TokenTransactionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TokenTransactionEntity::class;
    }
}
