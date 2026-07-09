<?php

namespace Webkul\RestApi\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Eloquent\Repository;
use Webkul\RestApi\Http\Controllers\RestApiController;

class Controller extends RestApiController
{
    /**
     * Exclude keys which not needed during searching.
     *
     * @var array
     */
    protected $excludeKeys = [
        'entity_type',
        'limit',
        'page',
        'pagination',
        'order',
        'sort',
    ];

    /**
     * Add entity type.
     *
     * @return void
     */
    protected function addEntityTypeInRequest($entityType)
    {
        request()->request->add(['entity_type' => $entityType]);
    }

    /**
     * Returns a listing of the resource.
     *
     * @return Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    protected function allResources(Repository $repository)
    {
        $query = $repository->query();

        $table   = $query->getModel()->getTable();
        $columns = Schema::getColumnListing($table);

        foreach (request()->except($this->excludeKeys) as $input => $value) {
            /**
             * Silently ignore filters that reference a column the table does not
             * have, instead of letting the database raise a 500 (issue: invalid
             * column name in query parameters).
             */
            if (! in_array($input, $columns, true)) {
                continue;
            }

            $query = $query->whereIn($input, array_map('trim', explode(',', $value)));
        }

        if ($sort = request()->input('sort')) {
            /**
             * Reject an unknown sort column with a 422 validation error rather
             * than a 500 database error (issue: invalid sort column).
             */
            if (! in_array($sort, $columns, true)) {
                throw ValidationException::withMessages([
                    'sort' => trans('validation.in', ['attribute' => 'sort']),
                ]);
            }

            $order = strtolower((string) request()->input('order'));
            $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

            $query = $query->orderBy($sort, $order);
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        if (is_null(request()->input('pagination')) || request()->input('pagination')) {
            return $query->paginate(request()->input('limit') ?? 10);
        }

        return $query->get();
    }

    /**
     * Fetch a single resource by id or fail with a 404.
     *
     * Centralizes the `findOrFail` call so `show`/`update` never pass a null
     * model into a JsonResource (the root cause of the "500 on non-existent
     * ID" reports). The thrown ModelNotFoundException is rendered as a friendly
     * JSON 404 by the package exception handler.
     *
     * @param  int|string  $id
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function findOrFailResource(Repository $repository, $id)
    {
        return $repository->findOrFail($id);
    }

    /**
     * Build a consistent success response.
     *
     * Mirrors the `{ data, message }` payload shape already used across the
     * controllers (wrapped by JsonResource under a top-level `data` key).
     */
    protected function respondSuccess(?string $message = null, $data = null): JsonResource
    {
        $payload = [];

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        if (! is_null($message)) {
            $payload['message'] = $message;
        }

        return new JsonResource($payload);
    }

    /**
     * Build a consistent error response with a real HTTP status code.
     *
     * Fixes the `new JsonResource([...], 500)` anti-pattern where the status
     * code was silently swallowed as the resource payload, so failures were
     * returned as HTTP 200.
     */
    protected function respondError(string $message, int $status = 500): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }

    /**
     * Delete a single resource, guaranteeing a 404 for unknown ids and a real
     * status code / message on failure.
     *
     * @param  int|string  $id
     */
    protected function destroyResource(
        Repository $repository,
        $id,
        string $messageKey,
        ?string $eventPrefix = null,
        ?string $failedMessageKey = null
    ): JsonResource|JsonResponse {
        $resource = $repository->findOrFail($id);

        try {
            if ($eventPrefix) {
                Event::dispatch("{$eventPrefix}.delete.before", $id);
            }

            $repository->delete($id);

            if ($eventPrefix) {
                Event::dispatch("{$eventPrefix}.delete.after", $id);
            }

            return $this->respondSuccess(trans($messageKey));
        } catch (\Exception $exception) {
            return $this->respondError(
                trans($failedMessageKey ?? $messageKey),
                500
            );
        }
    }

    /**
     * Delete many resources, skipping ids that do not exist and reporting the
     * real number of deleted rows so the caller can avoid the misleading
     * "deleted successfully" message when nothing matched.
     *
     * @return array{deleted: int, total: int}
     */
    protected function massDestroyResources(
        Repository $repository,
        array $indices,
        ?string $eventPrefix = null
    ): array {
        $deleted = 0;

        foreach ($indices as $id) {
            $resource = $repository->find($id);

            if (! $resource) {
                continue;
            }

            if ($eventPrefix) {
                Event::dispatch("{$eventPrefix}.delete.before", $id);
            }

            $resource->delete();

            if ($eventPrefix) {
                Event::dispatch("{$eventPrefix}.delete.after", $id);
            }

            $deleted++;
        }

        return [
            'deleted' => $deleted,
            'total'   => count($indices),
        ];
    }
}
