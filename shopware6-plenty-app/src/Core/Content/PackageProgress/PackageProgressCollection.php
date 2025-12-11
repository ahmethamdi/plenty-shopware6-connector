<?php

namespace PlentyConnector\Core\Content\PackageProgress;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                      add(PackageProgressEntity $entity)
 * @method void                      set(string $key, PackageProgressEntity $entity)
 * @method PackageProgressEntity[]    getIterator()
 * @method PackageProgressEntity[]    getElements()
 * @method PackageProgressEntity|null get(string $key)
 * @method PackageProgressEntity|null first()
 * @method PackageProgressEntity|null last()
 */
class PackageProgressCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PackageProgressEntity::class;
    }
}
