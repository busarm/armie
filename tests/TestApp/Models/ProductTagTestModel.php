<?php

namespace Busarm\PhpMini\Test\TestApp\Models;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Relations\OneToMany;
use Busarm\PhpMini\Data\PDO\Relations\OneToOne;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
            new OneToOne('product', $this, new ProductTestModel, ['productId' => 'id']),
            new OneToOne('tag', $this, new TagTestModel, ['tagId' => 'id']),
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
