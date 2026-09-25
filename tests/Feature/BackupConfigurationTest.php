<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\DbDumper\Compressors\GzipCompressor;

test('pgsql connection has database dump configuration defined', function (): void {
    $dumpConfig = config('database.connections.pgsql.dump');

    expect($dumpConfig)->toBeArray()
        ->and($dumpConfig['timeout'])->toBe(300)
        ->and($dumpConfig['exclude_tables'])->toContain('sessions', 'cache', 'cache_locks');
});

test('backup configuration enables gzip compression for database dump', function (): void {
    expect(config('backup.backup.database_dump_compressor'))->toBe(GzipCompressor::class)
        ->and(config('backup.backup.source.files.exclude'))->toContain(base_path('.git'));
});

test('backup tasks are properly registered in scheduler', function (): void {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $commands = $events->mapWithKeys(function (Event $event): array {
        return [(string) $event->command => $event->expression];
    });

    $hasClean = $commands->contains(function (string $expression, string $command): bool {
        return str_contains($command, 'backup:clean') && $expression === '0 1 * * *';
    });

    $hasOnlyDb = $commands->contains(function (string $expression, string $command): bool {
        return str_contains($command, 'backup:run --only-db') && $expression === '30 1 * * *';
    });

    $hasWeeklyFull = $commands->contains(function (string $expression, string $command): bool {
        return str_contains($command, 'backup:run')
            && ! str_contains($command, '--only-db')
            && $expression === '0 2 * * 0';
    });

    $hasMonitor = $commands->contains(function (string $expression, string $command): bool {
        return str_contains($command, 'backup:monitor') && $expression === '0 6 * * *';
    });

    expect($hasClean)->toBeTrue()
        ->and($hasOnlyDb)->toBeTrue()
        ->and($hasWeeklyFull)->toBeTrue()
        ->and($hasMonitor)->toBeTrue();
});
