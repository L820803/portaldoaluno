<?php

namespace App\Console\Commands;

use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeException;
use Illuminate\Console\Command;

class ECidadeChamar extends Command
{
    protected $signature = 'ecidade:chamar
        {metodo=DadosPrefeitura : Método do webservice (ex.: AlunoDados)}
        {parametros?* : Parâmetros no formato chave=valor (ex.: codigo_aluno=123)}';

    protected $description = 'Chama um método do webservice do e-cidade (sem cache) e exibe a resposta';

    public function handle(ECidade $ecidade): int
    {
        $metodo = $this->argument('metodo');
        $params = [];

        foreach ($this->argument('parametros') as $par) {
            if (! str_contains($par, '=')) {
                $this->error("Parâmetro inválido \"{$par}\": use chave=valor.");

                return self::INVALID;
            }

            [$chave, $valor] = explode('=', $par, 2);
            $params[$chave] = match ($valor) {
                'true' => true,
                'false' => false,
                default => $valor,
            };
        }

        if ($metodo === 'DadosPrefeitura' && ! $params) {
            $params = ['lprefeitura' => true];
        }

        $this->components->info(sprintf(
            'Driver: %s | %s',
            config('ecidade.driver'),
            config('ecidade.driver') === 'fake' ? config('ecidade.fixtures_path') : config('ecidade.location'),
        ));

        $inicio = microtime(true);

        try {
            $resposta = $ecidade->fresh()->call($metodo, $params);
        } catch (ECidadeException $e) {
            $this->components->error(class_basename($e).': '.$e->getMessage());

            return self::FAILURE;
        }

        // Abrevia strings longas (logo/foto em base64)
        array_walk_recursive($resposta, function (&$valor) {
            if (is_string($valor) && mb_strlen($valor) > 200) {
                $valor = mb_substr($valor, 0, 60).'… ('.mb_strlen($valor).' caracteres)';
            }
        });

        $this->line(json_encode($resposta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->components->info(sprintf('OK em %d ms', (microtime(true) - $inicio) * 1000));

        return self::SUCCESS;
    }
}
