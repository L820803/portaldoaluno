<?php

namespace App\Http\Controllers\Portal;

use App\Services\ECidade\Exceptions\ECidadeException;
use App\Services\Portal\AlunoAtual;
use App\Services\Portal\VidaEscolar;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Boletim, faltas, histórico, horários, calendário e biblioteca.
 */
class VidaEscolarController extends TelaController
{
    public function __construct(
        AlunoAtual $aluno,
        protected VidaEscolar $vida,
    ) {
        parent::__construct($aluno);
    }

    public function boletim(): Response
    {
        return $this->tela('aluno/boletim', fn () => $this->vida->boletim());
    }

    /**
     * Parecer de uma nota, carregado ao tocar nela (GET parecerMatricula no legado).
     */
    public function parecer(int $regencia, int $periodo): JsonResponse
    {
        abort_unless($this->aluno->ehAluno(), 404);

        try {
            $parecer = $this->vida->parecer($regencia, $periodo);
        } catch (ECidadeException) {
            return response()->json(['message' => 'O sistema da Secretaria de Educação não respondeu. Tente novamente.'], 503);
        }

        return $parecer
            ? response()->json($parecer)
            : response()->json(['message' => 'Nenhum parecer para esta nota.'], 404);
    }

    public function faltas(): Response
    {
        return $this->tela('aluno/faltas', fn () => $this->vida->faltas());
    }

    public function historico(): Response
    {
        return $this->tela('aluno/historico', fn () => $this->vida->historico());
    }

    public function horarios(): Response
    {
        return $this->tela('aluno/horarios', fn () => $this->vida->horarios());
    }

    public function calendario(): Response
    {
        return $this->tela('aluno/calendario', fn () => $this->vida->calendario());
    }

    /**
     * Biblioteca com filtro pelo período de retirada (?de=AAAA-MM-DD&ate=AAAA-MM-DD).
     */
    public function biblioteca(Request $request): Response
    {
        $filtro = $request->validate([
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
        ], attributes: ['de' => 'data inicial', 'ate' => 'data final']);

        $de = isset($filtro['de']) ? CarbonImmutable::parse($filtro['de']) : null;
        $ate = isset($filtro['ate']) ? CarbonImmutable::parse($filtro['ate']) : null;

        return $this->tela('aluno/biblioteca', fn () => $this->vida->biblioteca($de, $ate), [
            'filtro' => ['de' => $filtro['de'] ?? null, 'ate' => $filtro['ate'] ?? null],
        ]);
    }
}
