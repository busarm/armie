<?php

namespace Armie\Data\PDO\Models;

use Armie\Data\PDO\Model;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
 * @inheritDoc
 */
class BaseModel extends Model
{
    protected string $_table = '';
    protected string $_key = '';
    protected ?string $_createdAt = null;
    protected ?string $_updatedAt = null;
    protected ?string $_deletedAt = null;
    /** @var \Armie\Data\PDO\Field[] */
    protected array $_fields = [];
    /** @var \Armie\Data\PDO\Relation<static,parent>[] */
    protected array $_relations = [];

    public function __sleep(): array
    {
        return array_merge(
            parent::__sleep(),
            [
                '_table', '_key', '_createdAt', '_updatedAt',
                '_fields', '_relations',
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function __excluded(): array
    {
        return array_merge(
            parent::__excluded(),
            [
                '_table', '_key', '_createdAt', '_updatedAt',
                '_fields', '_relations',
            ]
        );
    }

    /**
     * @param string                                    $tableName
     * @param string                                    $keyName
     * @param string                                    $createdDateName
     * @param string                                    $updatedDateName
     * @param string                                    $deletedDateName
     * @param \Armie\Data\PDO\Field[]                   $fields
     * @param \Armie\Data\PDO\Relation<static,parent>[] $relations
     *
     * @return static
     */
    public static function init(
        string $tableName,
        string $keyName,
        ?string $createdDateName = null,
        ?string $updatedDateName = null,
        ?string $deletedDateName = null,
        array $fields = [],
        array $relations = []
    ): static {
        $model = new static();
        $model->_table = $tableName;
        $model->_key = $keyName;
        $model->_createdAt = $createdDateName;
        $model->_updatedAt = $updatedDateName;
        $model->_deletedAt = $deletedDateName;
        $model->_fields = $fields;
        $model->_relations = $relations;

        return $model;
    }

    //########## Getters ###########

    /**
     * @inheritDoc
     */
    public function getTableName(): string
    {
        return $this->_table;
    }

    /**
     * @inheritDoc
     */
    public function getKeyName(): ?string
    {
        return $this->_key;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedDateName(): ?string
    {
        return $this->_createdAt;
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedDateName(): ?string
    {
        return $this->_updatedAt;
    }

    /**
     * @inheritDoc
     */
    public function getSoftDeleteDateName(): ?string
    {
        return $this->_deletedAt;
    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return $this->_fields;
    }

    /**
     * @inheritDoc
     */
    public function getRelations(): array
    {
        return $this->_relations;
    }
}
