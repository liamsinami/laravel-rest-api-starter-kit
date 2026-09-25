<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 bg-neutral-50 dark:bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400">
                        <flux:icon name="code-bracket" class="size-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('API Documentation') }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Interactive OpenAPI Reference') }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Explore and test available endpoints with generated documentation.') }}
                </p>
                <div class="mt-6">
                    <flux:button :href="url('/docs/api')" target="_blank" icon-trailing="arrow-top-right-on-square" variant="primary" class="w-full">
                        {{ __('Open API Docs') }}
                    </flux:button>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 bg-neutral-50 dark:bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-400">
                        <flux:icon name="key" class="size-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Authentication') }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Laravel Sanctum & Fortify') }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Supports Bearer Tokens for API clients and session authentication for web.') }}
                </p>
                <div class="mt-6">
                    <flux:button :href="url('/api/v1/health')" target="_blank" icon-trailing="arrow-top-right-on-square" variant="filled" class="w-full">
                        {{ __('Health Check API') }}
                    </flux:button>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 bg-neutral-50 dark:bg-zinc-900/50">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-purple-500/10 p-2 text-purple-600 dark:text-purple-400">
                        <flux:icon name="command-line" class="size-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('API Version') }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('RESTful v1 Endpoints') }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Endpoints are prefixed with /api/v1 for modularity and backwards compatibility.') }}
                </p>
                <div class="mt-6">
                    <flux:button :href="route('profile.edit')" variant="subtle" class="w-full">
                        {{ __('User Settings') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 bg-neutral-50 dark:bg-zinc-900/50">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Quick API Usage Guide') }}</h3>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('To access protected API endpoints, send your Sanctum API token in the Authorization header:') }}
            </p>
            <div class="mt-4 rounded-lg bg-zinc-900 p-4 text-xs font-mono text-zinc-200">
                <code>curl -H "Authorization: Bearer &lt;YOUR_API_TOKEN&gt;" -H "Accept: application/json" {{ url('/api/v1/user') }}</code>
            </div>
        </div>
    </div>
</x-layouts::app>
