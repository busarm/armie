<?php

namespace Busarm\PhpMini\Test\TestApp\Models;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Reference;
use Busarm\PhpMini\Data\PDO\Relations\OneToMany;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class CategoryTestModel extends Model
{
    public int|null $id;
    public string|null $name;
    public string|null $description;
    public string|null $createdAt;
    public string|null $updatedAt;

    /** @var ProductTestModel[] */
    public array|null $products;

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
            new OneToMany('products', $this, new Reference(ProductTestModel::class, ['id' => 'categoryId']))
        ];
    }

    /**
     * Model table name. e.g db table, collection name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'categories';
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

    /**
     * Model created date param name. e.g created_at, createdAt
     *
     * @return string
     */
    public function getCreatedDateName(): ?string
    {
        return 'createdAt';
    }

    /**
     * Model updated date date param name. e.g updated_at, updatedAt
     *
     * @return string
     */
    public function getUpdatedDateName(): ?string
    {
        return 'updatedAt';
    }
}
