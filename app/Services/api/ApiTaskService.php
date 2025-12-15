<?php

namespace App\Services\api;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use App\Mail\TaskCreatedMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ApiTaskService
{
    public static function get_tasks(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'status' => 'sometimes|in:planned,in_progress,done',
            'user_id' => 'sometimes|exists:users,id',
            'done_at' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте параметры фильтрации.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $filters = $validator->validated();

        $query = Task::with(['user', 'media'])->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['done_at'])) {
            $query->whereDate('done_at', Carbon::parse($filters['done_at'])->toDateString());
        }

        $tasks = $query->get()->map(fn (Task $task) => self::formatTask($task));

        return ApiService::successResponse('Список задач получен', ['tasks' => $tasks]);
    }

    public static function create_tasks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:planned,in_progress,done',
            'done_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240',
        ], [
            'title.required' => 'Заголовок обязателен.',
            'title.string' => 'Заголовок должен быть строкой.',
            'status.in' => 'Недопустимый статус задачи.',
            'user_id.exists' => 'Указанный исполнитель не найден.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $data = $validator->validated();
        $assigneeId = $data['user_id'] ?? $request->user()->id;

        $task = new Task();
        $task->name = $data['title'];
        $task->description = $data['description'] ?? null;
        $task->status = $data['status'] ?? 'planned';
        $task->done_at = $data['done_at'] ?? null;
        $task->user_id = $assigneeId;
        $task->save();

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $task->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $task->load(['user', 'media']);

        $assignee = User::find($assigneeId);
        if ($assignee?->email) {
            Mail::to($assignee->email)->send(new TaskCreatedMail($task));
        }

        return ApiService::successResponse('Задача создана', self::formatTask($task));
    }

    public static function get_task(int $id)
    {
        $task = Task::with(['user', 'media'])->find($id);

        if (! $task) {
            return ApiService::errorResponse(
                'Задача не найдена.',
                ApiService::TASK_NOT_FOUND,
                [],
                404
            );
        }

        return ApiService::successResponse('Задача получена', self::formatTask($task));
    }

    public static function edit_task(int $id, Request $request)
    {
        $task = Task::with(['user', 'media'])->find($id);

        if (! $task) {
            return ApiService::errorResponse(
                'Задача не найдена.',
                ApiService::TASK_NOT_FOUND,
                [],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:planned,in_progress,done',
            'done_at' => 'sometimes|nullable|date',
            'user_id' => 'sometimes|exists:users,id',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240',
        ], [
            'title.required' => 'Заголовок обязателен.',
            'status.in' => 'Недопустимый статус задачи.',
            'user_id.exists' => 'Указанный исполнитель не найден.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $data = $validator->validated();

        if (array_key_exists('title', $data)) {
            $task->name = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $task->description = $data['description'] ?? null;
        }
        if (array_key_exists('status', $data)) {
            $task->status = $data['status'];
        }
        if (array_key_exists('done_at', $data)) {
            $task->done_at = $data['done_at'] ?? null;
        }
        if (array_key_exists('user_id', $data)) {
            $task->user_id = $data['user_id'];
        }

        $task->save();

        if ($request->hasFile('attachments')) {
            $task->clearMediaCollection('attachments');
            foreach ($request->file('attachments') as $file) {
                $task->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $task->load(['user', 'media']);

        return ApiService::successResponse('Задача обновлена', self::formatTask($task));
    }

    public static function delete_task(int $id)
    {
        $task = Task::find($id);

        if (! $task) {
            return ApiService::errorResponse(
                'Задача не найдена.',
                ApiService::TASK_NOT_FOUND,
                [],
                404
            );
        }

        $task->delete();

        return ApiService::successResponse('Задача удалена');
    }

    protected static function formatTask(Task $task): array
    {
        $task->loadMissing(['user', 'media']);

        return [
            'id' => $task->id,
            'title' => $task->name,
            'description' => $task->description,
            'status' => $task->status,
            'done_at' => $task->done_at?->toISOString(),
            'assignee' => $task->user ? [
                'id' => $task->user->id,
                'name' => $task->user->name,
                'email' => $task->user->email,
            ] : null,
            'attachments' => $task->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                ];
            })->toArray(),
            'created_at' => $task->created_at?->toISOString(),
            'updated_at' => $task->updated_at?->toISOString(),
        ];
    }
}
