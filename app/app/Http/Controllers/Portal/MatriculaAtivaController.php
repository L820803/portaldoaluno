<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\AlunoAtual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MatriculaAtivaController extends Controller
{
    /**
     * Troca a matrícula consultada pelas telas (setMatricula no legado).
     */
    public function update(Request $request, AlunoAtual $aluno): RedirectResponse
    {
        $dados = $request->validate([
            'matricula' => ['required', 'integer'],
        ]);

        abort_unless($aluno->selecionarMatricula((int) $dados['matricula']), 403);

        return back();
    }
}
