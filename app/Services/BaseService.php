<?php

namespace App\Services;

use App\Repositories\BaseRepository;

abstract class BaseService
{

    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return $this->repository->index();
    }

    public function store(array $data)
    {
        return $this->repository->store($data);
    }

    public function show($id)
    {
        return $this->repository->show($id);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }

    public function allOrPaginate($resource, $groupBy = null)
    {

        return allOrPaginate($this->repository->query(), $resource, $groupBy);
    }


}
