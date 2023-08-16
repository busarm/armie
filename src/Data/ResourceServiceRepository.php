<?php

namespace Armie\Data;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Dto\ResourceItemRequestDto;
use Armie\Dto\ResourceListRequestDto;
use Armie\Dto\ResourcePaginatedListRequestDto;
use Armie\Dto\PaginatedCollectionDto;
use Armie\Dto\ServiceRequestDto;
use Armie\Enums\ServiceType;
use Armie\Interfaces\Data\ResourceServiceRepositoryInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ServiceHandlerInterface;

/**
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
class ResourceServiceRepository implements ResourceServiceRepositoryInterface
{
    public function __construct(
        protected RequestInterface $request,
        protected ServiceHandlerInterface $serviceProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(ResourceItemRequestDto $dto): ?BaseDto
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
    public function list(ResourceListRequestDto $dto): CollectionBaseDto
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
    public function paginatedList(ResourcePaginatedListRequestDto $dto): PaginatedCollectionDto
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
