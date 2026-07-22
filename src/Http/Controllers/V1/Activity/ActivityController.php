<?php

namespace Webkul\RestApi\Http\Controllers\V1\Activity;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Activity\Repositories\FileRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\RestApi\Http\Controllers\V1\Controller;
use Webkul\RestApi\Http\Request\MassDestroyRequest;
use Webkul\RestApi\Http\Request\MassUpdateRequest;
use Webkul\RestApi\Http\Resources\V1\Activity\ActivityResource;

class ActivityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ActivityRepository $activityRepository,
        protected FileRepository $fileRepository,
        protected LeadRepository $leadRepository
    ) {}

    /**
     * Returns a listing of the activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $activities = $this->allResources($this->activityRepository);

        return ActivityResource::collection($activities);
    }

    /**
     * Show resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $resource = $this->activityRepository->findOrFail($id);

        return new ActivityResource($resource);
    }

    /**
     * Store a newly created activity in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'type'          => 'required|in:call,meeting,lunch,note,file,email',
            'comment'       => 'required_if:type,note',
            'schedule_from' => 'required_unless:type,note,file',
            'schedule_to'   => 'required_unless:type,note,file',
            'file'          => 'required_if:type,file',
        ]);

        /**
         * Normalize `participants` into the nested `{users, persons}` shape the
         * core ActivityRepository expects, so a flat `participants:[1,2]` (user
         * ids) actually links participants. Must run before the overlap check
         * below, which also relies on the nested shape.
         */
        $this->normalizeParticipantsInput();

        if (request('type') === 'meeting') {
            /**
             * Check if meeting is overlapping with other meetings.
             */
            $isOverlapping = $this->activityRepository->isDurationOverlapping(
                request()->input('schedule_from'),
                request()->input('schedule_to'),
                request()->input('participants'),
                request()->input('id')
            );

            if ($isOverlapping) {
                if (request()->ajax()) {
                    return response()->json([
                        'message' => trans('admin::app.activities.overlapping-error'),
                    ], 400);
                }

                session()->flash('success', trans('admin::app.activities.overlapping-error'));

                return redirect()->back();
            }
        }

        Event::dispatch('activity.create.before');

        $activity = $this->activityRepository->create(array_merge(request()->all(), [
            'is_done' => request('type') == 'note' ? 1 : 0,
            'user_id' => auth()->user()->id,
        ]));

        Event::dispatch('activity.create.after', $activity);

        return new JsonResponse([
            'data'    => new ActivityResource($activity->load('participants', 'files', 'user')),
            'message' => trans('rest-api::app.activities.create-success'),
        ]);
    }

    /**
     * Update the specified activity in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $this->findOrFailResource($this->activityRepository, $id);

        $this->validate(request(), [
            'type'    => 'sometimes|required|in:call,meeting,lunch,note,file,email',
            'lead_id' => 'nullable|exists:leads,id',
        ]);

        /**
         * Normalize `participants` into the nested `{users, persons}` shape so a
         * flat `participants:[1,2]` links participants. The core repository's
         * update() already deletes + recreates participants from this shape, so
         * we no longer sync them here (that duplicate loop also read the wrong,
         * flat keys and did nothing).
         */
        $this->normalizeParticipantsInput();

        Event::dispatch('activity.update.before', $id);

        $activity = $this->activityRepository->update(request()->all(), $id);

        if ($leadId = request()->input('lead_id')) {
            $lead = $this->leadRepository->find($leadId);

            if (! $lead->activities->contains($id)) {
                $lead->activities()->attach($id);
            }
        }

        Event::dispatch('activity.update.after', $activity);

        return new JsonResponse([
            'data'    => new ActivityResource($activity->load('participants', 'files', 'user')),
            'message' => trans('rest-api::app.activities.update-success'),
        ]);
    }

    /**
     * Normalize and validate the request's `participants` input in place.
     *
     * Accepts either the nested shape the Krayin core consumes
     * (`participants[users][]` / `participants[persons][]`, sent by the panel
     * and multipart form-data) or a flat array of user ids (`participants:[1,2]`,
     * as REST clients naturally send). A flat array is treated as user ids;
     * persons require the nested shape. The normalized value is merged back into
     * the request so the core ActivityRepository create()/update() can sync it,
     * and the overlap check receives the shape it expects.
     *
     * @return void
     */
    protected function normalizeParticipantsInput()
    {
        if (! request()->has('participants')) {
            return;
        }

        $participants = $this->normalizeParticipants(request()->input('participants'));

        Validator::make($participants, [
            'users.*'   => 'integer|distinct|exists:users,id',
            'persons.*' => 'integer|distinct|exists:persons,id',
        ])->validate();

        request()->merge(['participants' => $participants]);
    }

    /**
     * Reshape a raw `participants` value into the nested `{users, persons}` form.
     *
     * A value already carrying `users`/`persons` keys is kept as-is (each side
     * defaulting to an empty list); anything else is treated as a flat list of
     * user ids. Blank/null entries are dropped and the rest are cast to ints.
     * Pure (no request/DB access) so it can be unit-tested directly.
     *
     * @param  mixed  $raw
     * @return array{users: array<int, int>, persons: array<int, int>}
     */
    protected function normalizeParticipants($raw): array
    {
        if (is_array($raw) && (array_key_exists('users', $raw) || array_key_exists('persons', $raw))) {
            $users   = $raw['users'] ?? [];
            $persons = $raw['persons'] ?? [];
        } else {
            $users   = $raw ?? [];
            $persons = [];
        }

        $normalize = fn ($ids) => array_values(array_map(
            'intval',
            array_filter((array) $ids, fn ($value) => $value !== '' && $value !== null)
        ));

        return [
            'users'   => $normalize($users),
            'persons' => $normalize($persons),
        ];
    }

    /**
     * Download file from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $file = $this->fileRepository->findOrFail($id);

        return Storage::download($file->path);
    }

    /**
     * Remove the specified activity from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->activityRepository->findOrFail($id);

        try {
            Event::dispatch('activity.delete.before', $id);

            $this->activityRepository->delete($id);

            Event::dispatch('activity.delete.after', $id);

            return $this->respondSuccess(trans('rest-api::app.activities.delete-success'));
        } catch (\Exception $exception) {
            return $this->respondError(trans('rest-api::app.activities.delete-failed'), 500);
        }
    }

    /**
     * Mass update the specified activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest)
    {
        $this->validate(request(), [
            'value' => 'required|boolean',
        ]);

        $count = 0;

        foreach ($massUpdateRequest->input('indices', []) as $activityId) {
            $activity = $this->activityRepository->find($activityId);

            if (! $activity) {
                continue;
            }

            Event::dispatch('activity.update.before', $activity);

            $activity->update([
                'is_done' => $massUpdateRequest->input('value'),
            ]);

            Event::dispatch('activity.update.after', $activity);

            $count++;
        }

        if (! $count) {
            return $this->respondError(trans('rest-api::app.common.nothing-to-delete'), 404);
        }

        return $this->respondSuccess(trans('rest-api::app.activities.update-success'));
    }

    /**
     * Mass delete the specified activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest)
    {
        $result = $this->massDestroyResources(
            $this->activityRepository,
            $massDestroyRequest->input('indices', []),
            'activity',
        );

        if ($result['deleted'] === 0) {
            return $this->respondError(trans('rest-api::app.common.nothing-to-delete'), 404);
        }

        return $this->respondSuccess(trans('rest-api::app.activities.delete-success'));
    }
}
