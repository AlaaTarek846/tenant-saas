<?php


function responseJson(int $status, string $message, $data = null, $pagination = null)
{
    $response = [
        'status' => $status == 200 || $status == 201 || $status == 204 ? 'success' : '',
        'code' => $status == 200 || $status == 201 || $status == 204 ? true : false,
        'message' => $message,
        'data' => $data ?? [],
        'pagination' => $pagination,
    ];

    return response()->json($response, $status);

}

function getPaginates($collection)
{
    return [
        'per_page' => $collection->perPage(),
        'path' => $collection->path(),
        'total' => $collection->total(),
        'current_page' => $collection->currentPage(),
        'next_page_url' => $collection->nextPageUrl(),
        'prev_page_url' => $collection->previousPageUrl(),
        'last_page' => $collection->lastPage(),
        'has_more_pages' => $collection->hasMorePages(),
        'from' => $collection->firstItem(),
        'to' => $collection->lastItem(),
    ];
}


function allOrPaginate($query, $resource, $groupBy = null)
{
    // استخرج قيمة paginate من الريكوست، خلي default = 10
    $perPage = request()->paginate ?? 10;

    // Validate بس لو فيه paginate في الريكوست
    if (request()->has('paginate') && request()->paginate != null) {
        request()->validate([
            'paginate' => 'gt:0|lte:50'
        ]);
    }

    // اعمل paginate دايمًا باستخدام $perPage
    $collection = $query->paginate($perPage);

    // جهز البيانات باستخدام الـ Resource
    $resourceCollection = $resource::collection($collection->items());

    $data = $groupBy
        ? collect($resourceCollection->resolve())->groupBy($groupBy)
        : $resourceCollection->resolve();

    // جهز pagination
    $paginate = getPaginates($collection);

    return ['data' => $data, 'paginate' => $paginate];
}

