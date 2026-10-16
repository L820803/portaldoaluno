<?php

namespace App\Services\Portal;

use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeException;

/**
 * Identidade do órgão (nome e logo) para o cabeçalho do portal.
 */
class Prefeitura
{
    /** @var array<string, mixed>|null */
    protected ?array $dados = null;

    public function __construct(protected ECidade $ecidade) {}

    /**
     * @return array{prefeitura: string, orgao: string, logo: string|null}
     */
    public function resumo(): array
    {
        $dados = $this->dados();

        return [
            'prefeitura' => trim((string) ($dados['sDescricao'] ?? '')) ?: config('portal.prefeitura'),
            'orgao' => config('portal.orgao'),
            'logo' => $this->logo() !== null ? route('prefeitura.logo') : null,
        ];
    }

    /**
     * Conteúdo binário do logo enviado pelo e-cidade (base64), se houver.
     */
    public function logo(): ?string
    {
        $base64 = $this->dados()['sLogoPrefeituraBaseEncode'] ?? '';
        $binario = $base64 !== '' ? base64_decode($base64, true) : false;

        return $binario ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function dados(): array
    {
        if ($this->dados === null) {
            try {
                $this->dados = $this->ecidade->dadosPrefeitura();
            } catch (ECidadeException) {
                // Sem e-cidade o portal segue com os valores de config/portal.php
                $this->dados = [];
            }
        }

        return $this->dados;
    }
}
