<?php

namespace Busarm\PhpMini\Test\TestApp\Models;

use Busarm\PhpMini\Data\PDO\Model;
use Busarm\PhpMini\Data\PDO\Reference;
use Busarm\PhpMini\Data\PDO\Relations\ManyToMany;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
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
                new Reference(new ProductTagTestModel, ['id' => 'tagId']),
                new Reference(new ProductTestModel, ['productId' => 'id'])
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
        return 'tags';
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
