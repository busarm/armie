<?php

namespace Busarm\PhpMini\HTTP;

use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\Dto\CollectionBaseDto;
use Busarm\PhpMini\Helpers\Parser;
use Busarm\PhpMini\Helpers\Security;
use Busarm\PhpMini\Interfaces\Data\RepositoryInterface;
use Busarm\PhpMini\Interfaces\HTTP\CrudControllerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;

/**
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 */
abstract class CrudController implements CrudControllerInterface
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
    public function get(int|string $id): ResponseInterface
    {
        $data = $this->repository->findById(
            $id,
            Security::cleanQueryParamKeys((array) $this->request->query()->get('query', [])),
            Security::cleanQueryParamValues((array) $this->request->query()->get('query', [])),
            Security::cleanParams((array) $this->request->query()->get('columns', []))
        );
        return $data ? $this->response->json($data->toArray(), 200) : $this->response->setStatusCode(404);
    }

    /**
     * @inheritDoc
     */
    public function list(): ResponseInterface
    {
        return $this->response->json($this->repository->all(
            Security::cleanQueryParamKeys((array) $this->request->query()->get('query', [])),
            Security::cleanQueryParamValues((array) $this->request->query()->get('query', [])),
            Security::cleanParams((array) $this->request->query()->get('columns', [])),
        )->toArray(), 200);
    }

    /**
     * @inheritDoc
     */
    public function paginatedList(): ResponseInterface
    {
        return $this->response->json($this->repository->paginate(
            (int) $this->request->query()->get('page', 1),
            (int) $this->request->query()->get('limit', 0),
            Parser::parseQueryParamKeys((array) $this->request->query()->get('query', [])),
            Parser::parseQueryParamValues((array) $this->request->query()->get('query', [])),
            Security::cleanParams((array) $this->request->query()->get('columns', [])),
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
