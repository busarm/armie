<?php

namespace Busarm\PhpMini\Service;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Dto\CrudItemRequestDto;
use Busarm\PhpMini\Dto\CrudListRequestDto;
use Busarm\PhpMini\Dto\CrudPaginatedListRequestDto;
use Busarm\PhpMini\Dto\PaginatedCollectionDto;
use Busarm\PhpMini\Dto\ServiceRequestDto;
use Busarm\PhpMini\Enums\ServiceType;
use Busarm\PhpMini\Interfaces\Data\CrudServiceRepositoryInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ServiceHandlerInterface;

/**
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
class CrudServiceRepository implements CrudServiceRepositoryInterface
{
    public function __construct(
        private RequestInterface $request,
        private ServiceHandlerInterface $serviceProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(CrudItemRequestDto $dto): ?BaseDto
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('/')
                ->setType(ServiceType::READ)
                ->setParams($dto->toArray()),
            $this->request
        );
        if ($response->status && $response->data && !empty($result = $response->data)) {
            return BaseDto::with($result, true);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function list(CrudListRequestDto $dto): CollectionBaseDto
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('list')
                ->setType(ServiceType::READ)
                ->setParams($dto->toArray()),
            $this->request
        );
        if ($response->status && $response->data && !empty($result = $response->data)) {
            return CollectionBaseDto::of($result, true);
        }
        return CollectionBaseDto::of([]);
    }

    /**
     * @inheritDoc
     */
    public function paginatedList(CrudPaginatedListRequestDto $dto): PaginatedCollectionDto
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('paginate')
                ->setType(ServiceType::READ)
                ->setParams($dto->toArray()),
            $this->request
        );
        if ($response->status && $response->data && !empty($result = $response->data)) {
            return (new PaginatedCollectionDto)->load($result, true);
        }
        return new PaginatedCollectionDto;
    }

    /**
     * @inheritDoc
     */
    public function create(BaseDto $dto): bool
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('/')
                ->setType(ServiceType::CREATE)
                ->setParams($dto->toArray()),
            $this->request
        );
        return $response->status;
    }

    /**
     * @inheritDoc
     */
    public function createBulk(CollectionBaseDto $dto): bool
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('bulk')
                ->setType(ServiceType::CREATE)
                ->setParams($dto->toArray()),
            $this->request
        );
        return $response->status;
    }

    /**
     * @inheritDoc
     */
    public function update(int|string $id, BaseDto $dto): bool
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute($id)
                ->setType(ServiceType::UPDATE)
                ->setParams($dto->toArray()),
            $this->request
        );
        return $response->status;
    }

    /**
     * @inheritDoc
     */
    public function updateBulk(CollectionBaseDto $dto): bool
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('bulk')
                ->setType(ServiceType::UPDATE)
                ->setParams($dto->toArray()),
            $this->request
        );
        return $response->status;
    }

    /**
     * @inheritDoc
     */
    public function delete(int|string $id): bool
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute($id)
                ->setType(ServiceType::DELETE),
            $this->request
        );
        return $response->status;
    }

    /**
     * @inheritDoc
     */
    public function deleteBulk(CollectionBaseDto $dto): bool
    {
        $response = $this->serviceProvider->call((new ServiceRequestDto)
                ->setRoute('bulk')
                ->setType(ServiceType::DELETE)
                ->setParams($dto->toArray()),
            $this->request
        );
        return $response->status;
    }
}
