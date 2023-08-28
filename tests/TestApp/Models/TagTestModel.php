<?php

namespace Armie\Test\TestApp\Models;

use Armie\Data\PDO\Model;
use Armie\Data\PDO\Reference;
use Armie\Data\PDO\Relations\ManyToMany;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class TagTestModel extends Model
{
    public int|null $id;
    public string|null $name;

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
            new ManyToMany(
                'products',
                $this,
                new Reference(ProductTagTestModel::class, ['id' => 'tagId']),
                new Reference(ProductTestModel::class, ['productId' => 'id'])
            ),
        ];
    }

    /**
     * Model table name. e.g db table, collection name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'tags';
    }

    /**
     * Model key name. e.g table primary key, unique index.
     *
     * @return string|null
     */
    public function getKeyName(): ?string
    {
        return 'id';
    }
}
