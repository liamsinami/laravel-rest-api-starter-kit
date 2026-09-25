<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

trait HandlesBatchOperations
{
    /**
     * Batch Delete (Soft delete if model uses SoftDeletes, or permanent delete otherwise).
     *
     * @param  class-string<Model>|Model|null  $model
     */
    public function batchDestroyInternal(Request $request, string|Model|null $model = null): JsonResponse
    {
        $instance = $this->resolveBatchModel($model);

        /** @var array{ids: array<int, string>} $data */
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deletedCount = 0;

        try {
            DB::transaction(function () use ($instance, $data, &$deletedCount): void {
                $instance->newQuery()
                    ->whereIn($instance->getKeyName(), $data['ids'])
                    ->get()
                    ->each(function (Model $item) use (&$deletedCount): void {
                        if ($item->delete()) {
                            $deletedCount++;
                        }
                    });
            });
        } catch (Throwable) {
            return ApiResponse::serverError('Failed to batch delete resources.');
        }

        $modelName = class_basename($instance);
        $plural = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::lower($modelName));

        return ApiResponse::success(
            data: ['deleted_count' => $deletedCount],
            message: "Successfully deleted {$deletedCount} {$plural}.",
        );
    }

    /**
     * Batch Restore soft-deleted records.
     *
     * @param  class-string<Model>|Model|null  $model
     */
    public function batchRestoreInternal(Request $request, string|Model|null $model = null): JsonResponse
    {
        $instance = $this->resolveBatchModel($model);

        /** @var array{ids: array<int, string>} $data */
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $restoredCount = 0;

        try {
            DB::transaction(function () use ($instance, $data, &$restoredCount): void {
                $query = $instance->newQuery();

                if (in_array(SoftDeletes::class, class_uses_recursive($instance), true)) {
                    $query->onlyTrashed();
                }

                $query->whereIn($instance->getKeyName(), $data['ids'])
                    ->get()
                    ->each(function (Model $item) use (&$restoredCount): void {
                        if (method_exists($item, 'restore') && $item->restore()) {
                            $restoredCount++;
                        }
                    });
            });
        } catch (Throwable) {
            return ApiResponse::serverError('Failed to batch restore resources.');
        }

        return ApiResponse::success(
            data: ['restored_count' => $restoredCount],
            message: "Successfully restored {$restoredCount} resources.",
        );
    }

    /**
     * Batch Force Delete (Permanent removal from database).
     *
     * @param  class-string<Model>|Model|null  $model
     */
    public function batchForceDestroyInternal(Request $request, string|Model|null $model = null): JsonResponse
    {
        $instance = $this->resolveBatchModel($model);

        /** @var array{ids: array<int, string>} $data */
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);

        $deletedCount = 0;

        try {
            DB::transaction(function () use ($instance, $data, &$deletedCount): void {
                $query = $instance->newQuery();

                if (in_array(SoftDeletes::class, class_uses_recursive($instance), true)) {
                    $query->withTrashed();
                }

                $query->whereIn($instance->getKeyName(), $data['ids'])
                    ->get()
                    ->each(function (Model $item) use (&$deletedCount): void {
                        if (method_exists($item, 'forceDelete') ? $item->forceDelete() : $item->delete()) {
                            $deletedCount++;
                        }
                    });
            });
        } catch (Throwable) {
            return ApiResponse::serverError('Failed to batch permanently delete resources.');
        }

        return ApiResponse::success(
            data: ['deleted_count' => $deletedCount],
            message: "Successfully permanently deleted {$deletedCount} resources.",
        );
    }

    private function resolveBatchModel(string|Model|null $model): Model
    {
        $modelClass = $model ?? (property_exists($this, 'model') ? $this->model : null);

        if (is_string($modelClass)) {
            $modelClass = new $modelClass();
        }

        throw_unless($modelClass instanceof Model, RuntimeException::class, 'Invalid model provided for Batch Operations.');

        return $modelClass;
    }
}
