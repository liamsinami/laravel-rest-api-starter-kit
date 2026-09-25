<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait HasApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::success($data, $message, $status, $meta, $headers);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    protected function createdResponse(
        mixed $data = null,
        ?string $message = 'Resource created successfully.',
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::created($data, $message, $meta, $headers);
    }

    protected function noContentResponse(): JsonResponse
    {
        return ApiResponse::noContent();
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  class-string|null  $resourceClass
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        ?string $resourceClass = null,
        ?string $message = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::paginated($paginator, $resourceClass, $message, $meta, $headers);
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, string>  $headers
     */
    protected function errorResponse(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        ?ApiErrorCode $errorCode = null,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::error($message, $status, $errorCode, $errors, $headers);
    }
}
