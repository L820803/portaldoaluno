<?php

namespace App\Providers;

use App\Auth\ProvedorUsuarios;
use App\Hashing\LegacySha1Hasher;
use App\Services\ECidade\ECidade;
use App\Services\ECidade\Gateways\FakeGateway;
use App\Services\ECidade\Gateways\Gateway;
use App\Services\ECidade\Gateways\SoapGateway;
use App\Services\Portal\AlunoAtual;
use App\Services\Portal\Prefeitura;
use App\Services\Usuarios\RepositorioUsuarios;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Gateway::class, function () {
            $config = config('ecidade');

            if ($config['driver'] === 'fake') {
                return new FakeGateway($config['fixtures_path']);
            }

            return new SoapGateway(
                uri: (string) $config['uri'],
                location: (string) $config['location'],
                timeout: (int) $config['timeout'],
                verifySsl: (bool) $config['verify_ssl'],
            );
        });

        $this->app->singleton(RepositorioUsuarios::class, fn () => new RepositorioUsuarios(
            arquivo: config('portal.usuarios.arquivo') ?: null,
            semente: config('portal.usuarios.semente'),
        ));

        $this->app->singleton(ECidade::class, fn ($app) => new ECidade(
            gateway: $app->make(Gateway::class),
            cache: Cache::store(config('ecidade.cache.store')),
            ttl: config('ecidade.cache.ttl', []),
            cachePrefix: config('ecidade.cache.prefix', 'ecidade'),
            pausaAposFalha: (int) config('ecidade.pausa_apos_falha', 0),
        ));

        // Por requisição: dependem do usuário logado e da sessão
        $this->app->scoped(Prefeitura::class);
        $this->app->scoped(AlunoAtual::class, fn ($app) => new AlunoAtual(
            ecidade: $app->make(ECidade::class),
            session: $app->make('session.store'),
            user: $app->make('auth')->user(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Hash::extend('legacy-sha1', fn () => new LegacySha1Hasher((string) config('auth.legacy_password_salt')));

        Auth::provider('portal', fn ($app) => new ProvedorUsuarios($app['hash']));
    }
}
