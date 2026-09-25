<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Override;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request): ?\App\Models\User {
            $login = (string) ($request->input('login') ?: $request->input(Fortify::username()));

            /** @var \App\Models\User|null $user */
            $user = \App\Models\User::query()
                ->where('email', $login)
                ->orWhere('username', $login)
                ->first();

            if ($user && \Illuminate\Support\Facades\Hash::check((string) $request->input('password'), (string) $user->password)) {
                return $user;
            }

            return null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (): Factory|View => view('pages::auth.login'));
        Fortify::verifyEmailView(fn (): Factory|View => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn (): Factory|View => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn (): Factory|View => view('pages::auth.confirm-password'));
        Fortify::registerView(fn (): Factory|View => view('pages::auth.register'));
        Fortify::resetPasswordView(fn (): Factory|View => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn (): Factory|View => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
