<?php

namespace Busarm\PhpMini\Test\TestApp\Models;

use Busarm\PhpMini\Data\PDO\Field;
use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Reference;
use Busarm\PhpMini\Data\PDO\Relations\ManyToMany;
use Busarm\PhpMini\Data\PDO\Relations\OneToOne;
use Busarm\PhpMini\Enums\DataType;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class ProductTestModel extends Model
{
    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return [
            new Field('id', DataType::INT),
            new Field('name', DataType::STRING),
            new Field('type', DataType::STRING),
            new Field('qty', DataType::INT),
            new Field('categoryId', DataType::INT),
            new Field('createdAt', DataType::DATETIME),
            new Field('updatedAt', DataType::DATETIME),
            new Field('deletedAt', DataType::DATETIME)
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRelations(): array
    {
        return [
            new OneToOne('category', $this, new Reference(CategoryTestModel::class, ['categoryId' => 'id'])),
            new ManyToMany(
                'tags',
                $this,
                new Reference(ProductTagTestModel::class, ['id' => 'productId']),
                new Reference(TagTestModel::class, ['tagId' => 'id']),
                false
            )
        ];
    }

    /**
     * Model table name. e.g db table, collection name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'products';
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

    /**
     * Model soft delete date param name. e.g deleted_at, deletedAt
     *
     * @return string
     */
    public function getSoftDeleteDateName(): ?string
    {
        return 'deletedAt';
    }
}
