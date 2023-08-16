<?php

namespace Armie\Test\TestApp\Models;

use Armie\Data\PDO\Model;
use Armie\Data\PDO\Reference;
use Armie\Data\PDO\Relations\OneToOne;

/**
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ProductTagTestModel extends Model
{
    public int|null $id;
    public int|null $tagId;
    public int|null $productId;

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRelations(): array
    {
        return [
            new OneToOne('product', $this, new Reference(ProductTestModel::class, ['productId' => 'id'])),
            new OneToOne('tag', $this, new Reference(TagTestModel::class, ['tagId' => 'id'])),
        ];
    }

    /**
     * Model table name. e.g db table, collection name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'product_tags';
    }

    /**
     * Model key name. e.g table primary key, unique index
     *
     * @return string|null
     */
    public function getKeyName(): ?string
    {
        return 'id';
    }
}
