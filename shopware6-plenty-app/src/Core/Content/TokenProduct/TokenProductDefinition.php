<?php

namespace PlentyConnector\Core\Content\TokenProduct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TokenProductDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'plenty_token_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return TokenProductEntity::class;
    }

    public function getCollectionClass(): string
    {
        return TokenProductCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            new LongTextField('description', 'description'),
            (new IntField('token_price', 'tokenPrice'))->addFlags(new Required()),
            new StringField('image_url', 'imageUrl'),
            new IntField('stock', 'stock'),
            new BoolField('active', 'active'),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
