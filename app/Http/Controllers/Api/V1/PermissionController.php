<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PermissionResource;
use App\Query\Definitions\PermissionQueryDefinition;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

#[Group('Role & Permission')]
final class PermissionController extends ApiController
{
    protected string $model = Permission::class;

    protected string $resource = PermissionResource::class;

    /**
     * Display a list of all permissions.
     */
    public function index(Request $request): Response
    {
        return $this->handleIndex(PermissionQueryDefinition::class, $request);
    }
}
