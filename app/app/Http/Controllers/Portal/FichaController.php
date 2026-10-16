<?php

namespace App\Http\Controllers\Portal;

use App\Services\Portal\AlunoAtual;
use App\Services\Portal\FichaAluno;
use Inertia\Response;

/**
 * Telas cadastrais do aluno, todas a partir de AlunoDados.
 */
class FichaController extends TelaController
{
    public function __construct(
        AlunoAtual $aluno,
        protected FichaAluno $ficha,
    ) {
        parent::__construct($aluno);
    }

    public function meusDados(): Response
    {
        return $this->tela('aluno/meus-dados', fn () => $this->ficha->identificacao());
    }

    public function documentos(): Response
    {
        return $this->tela('aluno/documentos', fn () => $this->ficha->documentos());
    }

    public function outrasInformacoes(): Response
    {
        return $this->tela('aluno/outras-informacoes', fn () => $this->ficha->outrasInformacoes());
    }

    public function minhaEscola(): Response
    {
        return $this->tela('aluno/minha-escola', fn () => $this->ficha->escola());
    }
}
