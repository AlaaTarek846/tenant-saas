<?php

namespace App\Repositories;

use App\Support\DeletionGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    protected Model $model;

    public function index()
    {
        return $this->model;
    }

    public function query()
    {
        return $this->model->query();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $store = $this->model->create($data);

            if (isset($data['statuses'])) {
                $store->setStatus($data['statuses']);
            }

            if (isset($data['translations'])) {
                $store->setTranslation($data['translations']);
            }

            if (isset($data['sort'])) {
                $store->setSort('order', $data['sort']);
            }

            if (isset($data['enums'])) {
                $store->setEnum($data['enums']);
            }

            return $store;
        });
    }

    public function show($id)
    {
        return $this->model->findOrFail($id);
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $row = $this->model->findOrFail($id);
            $row->update($data);

            if (isset($data['statuses'])) {
                $row->setStatus($data['statuses']);
            }

            if (isset($data['translations'])) {
                $row->setTranslation($data['translations']);
            }

            if (isset($data['sort'])) {
                $row->setSort($data['sort']);
            }

            if (isset($data['enums'])) {
                $row->setEnum($data['enums']);
            }

            return $row;
        });
    }

    public function destroy($id, ?array $relations = null)
    {
        $row = $this->model->findOrFail($id);

        $relationsToCheck = $relations ?? (method_exists($this, 'deletionBlockRelations')
            ? $this->deletionBlockRelations()
            : []);

        if ($relationsToCheck !== []) {
            $resourceLabel = method_exists($this, 'deletionResourceLabel')
                ? $this->deletionResourceLabel()
                : null;

            DeletionGuard::ensureDeletable($row, $relationsToCheck, $resourceLabel);
        }

        return DB::transaction(function () use ($row) {
            if (method_exists($row, 'clearStatuses')) {
                $row->clearStatuses();
            }

            if (method_exists($row, 'deleteTranslation')) {
                $row->deleteTranslation();
            }

            if (method_exists($row, 'clearEnums')) {
                $row->clearEnums();
            }

            if (method_exists($row, 'deleteSort')) {
                $row->deleteSort();
            }

            if (method_exists($row, 'cleanMedia')) {
                $row->cleanMedia();
            }

            return $row->delete();
        });
    }
}
