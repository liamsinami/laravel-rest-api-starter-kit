<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FcmController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserController;

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel()->ignoring([
    AttachmentController::class,
    AuthController::class,
    FcmController::class,
    SettingController::class,
    UserController::class,
]);
