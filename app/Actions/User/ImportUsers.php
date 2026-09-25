<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

final readonly class ImportUsers
{
    /**
     * @return array{created_count: int, updated_count: int, errors: array<int, string>}
     */
    public function handle(UploadedFile $file): array
    {
        $results = [
            'created_count' => 0,
            'updated_count' => 0,
            'errors' => [],
        ];

        try {
            $import = new class implements \Maatwebsite\Excel\Concerns\ToArray
            {
                public function array(array $array): void {}
            };

            /** @var array<int, array<int, array<int, mixed>>> $sheets */
            $sheets = Excel::toArray($import, $file);
            $rows = $sheets[0] ?? [];
        } catch (Exception $e) {
            $results['errors'][] = 'Failed to read spreadsheet: '.$e->getMessage();

            return $results;
        }

        if ($rows === []) {
            $results['errors'][] = 'The spreadsheet is empty.';

            return $results;
        }

        $header = array_shift($rows);
        if (! is_array($header)) {
            $results['errors'][] = 'Invalid spreadsheet header.';

            return $results;
        }

        $headerKeys = array_map(fn (mixed $col): string => mb_strtolower(mb_trim((string) $col)), $header);

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            if (! is_array($row)) {
                continue;
            }

            $props = [];
            foreach ($headerKeys as $colIndex => $key) {
                if ($key !== '') {
                    $props[$key] = $row[$colIndex] ?? null;
                }
            }

            // Skip empty rows
            if (empty(array_filter($props, fn (mixed $v): bool => $v !== null && $v !== ''))) {
                continue;
            }

            $name = $this->extractValue($props, ['name', 'nama', 'full_name', 'fullname']);
            $email = $this->extractValue($props, ['email', 'email_address', 'surel']);
            $username = $this->extractValue($props, ['username', 'user_name']);
            $password = $this->extractValue($props, ['password', 'pass', 'kata_sandi']) ?? 'Password123!';
            $rolesStr = $this->extractValue($props, ['role', 'roles', 'peran']);

            if (! $name || ! $email) {
                $results['errors'][] = "Row {$rowNumber}: Name and email are required.";

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results['errors'][] = "Row {$rowNumber}: Invalid email format ({$email}).";

                continue;
            }

            try {
                DB::transaction(function () use ($name, $email, $username, $password, $rolesStr, &$results): void {
                    $existingUser = User::withTrashed()->where('email', $email)->first();

                    if ($existingUser) {
                        if ($existingUser->trashed()) {
                            $existingUser->restore();
                        }

                        $existingUser->update([
                            'name' => $name,
                            'username' => $username ?: $existingUser->username,
                        ]);

                        if ($rolesStr !== null && $rolesStr !== '') {
                            $roles = array_filter(array_map('trim', explode(',', $rolesStr)));
                            $validRoles = \Spatie\Permission\Models\Role::whereIn('name', $roles)->pluck('name')->all();
                            if ($validRoles !== []) {
                                $existingUser->syncRoles($validRoles);
                            }
                        }

                        $results['updated_count']++;
                    } else {
                        /** @var User $newUser */
                        $newUser = User::query()->create([
                            'name' => $name,
                            'email' => $email,
                            'username' => $username,
                            'password' => Hash::make($password),
                            'email_verified_at' => now(),
                        ]);

                        if ($rolesStr !== null && $rolesStr !== '') {
                            $roles = array_filter(array_map('trim', explode(',', $rolesStr)));
                            $validRoles = \Spatie\Permission\Models\Role::whereIn('name', $roles)->pluck('name')->all();
                            if ($validRoles !== []) {
                                $newUser->syncRoles($validRoles);
                            }
                        }

                        $results['created_count']++;
                    }
                });
            } catch (Exception $e) {
                $results['errors'][] = "Row {$rowNumber} ({$email}): ".$e->getMessage();
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<int, string>  $keys
     */
    private function extractValue(array $props, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($props[$k]) && (string) $props[$k] !== '' && mb_strtolower((string) $props[$k]) !== 'null') {
                return (string) $props[$k];
            }
        }

        return null;
    }
}
