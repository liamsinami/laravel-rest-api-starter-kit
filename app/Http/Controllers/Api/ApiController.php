<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Concerns\HandlesApiRequest;
use App\Concerns\HandlesBatchOperations;
use App\Concerns\HasApiResponse;
use App\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    use HandlesApiRequest;
    use HandlesBatchOperations;
    use HasApiResponse;
}
