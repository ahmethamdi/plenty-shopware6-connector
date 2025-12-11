<?php

namespace PlentyConnector\Core\Content\Package;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(PackageEntity $entity)
 * @method void              set(string $key, PackageEntity $entity)
 * @method PackageEntity[]    getIterator()
 * @method PackageEntity[]    getElements()
 * @method PackageEntity|null get(string $key)
 * @method PackageEntity|null first()
 * @method PackageEntity|null last()
 */
class PackageCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PackageEntity::class;
    }
}
