<?php

namespace Armie\Data;

use Armie\Dto\BaseDto;
use Armie\Dto\CollectionBaseDto;
use Armie\Dto\ResourceItemRequestDto;
use Armie\Dto\ResourceListRequestDto;
use Armie\Dto\ResourcePaginatedListRequestDto;
use Armie\Helpers\Security;
use Armie\Interfaces\Data\RepositoryInterface;
use Armie\Interfaces\Data\ResourceControllerInterface;
use Armie\Interfaces\RequestInterface;
use Armie\Interfaces\ResponseInterface;

/**
 * Armie Framework.
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 */
abstract class ResourceController implements ResourceControllerInterface
{
    public function __construct(
        private RepositoryInterface $repository,
        private RequestInterface $request,
        private ResponseInterface $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(ResourceItemRequestDto $dto): ResponseInterface
    {
        $data = $this->repository->findById(
            $dto->id,
            Security::cleanQueryParamKeys($dto->query),
            Security::cleanQueryParamValues($dto->query),
            Security::cleanParams($dto->columns)
        );

        return $data ? $this->response->json($data->toArray(), 200) : $this->response->setStatusCode(404);
    }

    /**
     * @inheritDoc
     */
    public function list(ResourceListRequestDto $dto): ResponseInterface
    {
        return $this->response->json($this->repository->all(
            Security::cleanQueryParamKeys($dto->query),
            Security::cleanQueryParamValues($dto->query),
            Security::cleanParams($dto->columns),
            Security::cleanParams($dto->sort)
        )->toArray(), 200);
    }

    /**
     * @inheritDoc
     */
    public function paginatedList(ResourcePaginatedListRequestDto $dto): ResponseInterface
    {
        return $this->response->json($this->repository->paginate(
            Security::cleanQueryParamKeys($dto->query),
            Security::cleanQueryParamValues($dto->query),
            Security::cleanParams($dto->columns),
            Security::cleanParams($dto->sort),
            $dto->page,
            $dto->limit
        )->toArray(), 200);
    }

    /**
     * @inheritDoc
     */
    public function create(BaseDto $dto): ResponseInterface
    {
        $data = $this->repository->create($dto->toArray(true, true));

        return $data ? $this->response->json($data->toArray(), 200) : $this->response->setStatusCode(400);
    }

    /**
     * @inheritDoc
     */
    public function createBulk(CollectionBaseDto $dto): ResponseInterface
    {
        $done = $this->repository->createBulk($dto->toArray(true, true));

        return $done ? $this->response->json([], 200) : $this->response->setStatusCode(400);
    }

    /**
     * @inheritDoc
     */
    public function update(int|string $id, BaseDto $dto): ResponseInterface
    {
        $done = $this->repository->updateById($id, $dto->toArray(true, true));

        return $done ? $this->response->json([], 200) : $this->response->setStatusCode(400);
    }

    /**
     * @inheritDoc
     */
    public function updateBulk(CollectionBaseDto $dto): ResponseInterface
    {
        $done = $this->repository->updateBulk($dto->toArray(true, true));

        return $done ? $this->response->json([], 200) : $this->response->setStatusCode(400);
    }

    /**
     * @inheritDoc
     */
    public function delete(int|string $id): ResponseInterface
    {
        $done = $this->repository->deleteById($id, (bool) $this->request->query()->get('force', false));

        return $done ? $this->response->json([], 200) : $this->response->setStatusCode(500);
    }

    /**
     * @inheritDoc
     */
    public function deleteBulk(CollectionBaseDto $dto): ResponseInterface
    {
        $done = $this->repository->deleteBulk($dto->toArray(true, true), (bool) $this->request->query()->get('force', false));

        return $done ? $this->response->json([], 200) : $this->response->setStatusCode(500);
    }
}
