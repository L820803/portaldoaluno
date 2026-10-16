<?php

use App\Models\User;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use App\Services\Portal\PainelAluno;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gateway = app(Gateway::class);
    $this->aluno = User::factory()->create(['login' => '123456']);
});

function painel(): PainelAluno
{
    return app(PainelAluno::class);
}

test('a página abre sem esperar o e-cidade e os cartões chegam adiados', function () {
    $this->actingAs($this->aluno)
        ->get('/inicio')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inicio')
            ->missing('matricula')
            ->missing('aulas')
            ->missing('emprestimos'));

    $this->gateway->assertNotCalled('MatriculaAluno');
});

test('os cartões são carregados em grupos independentes', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-14 08:00')); // quarta-feira

    $this->actingAs($this->aluno)
        ->get('/inicio')
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(['matricula', 'aulas', 'biblioteca'], fn (Assert $reload) => $reload
                ->where('matricula.codigo', 98765)
                ->where('matricula.frequencia', 98.5)
                ->where('aulas.hoje', true)
                ->where('aulas.dia', 'QUARTA-FEIRA')
                ->has('emprestimos.itens', 1)));
});

test('resumo da matrícula: frequência ponderada, faltas e notas abaixo da média', function () {
    $this->actingAs($this->aluno);

    expect(painel()->matricula())
        ->codigo->toBe(98765)
        ->situacao->toBe('MATRICULADO')
        ->resultado->toBe('EM ANDAMENTO')
        ->turma->toBe('8º ANO A')
        ->disciplinas->toBe(8)
        // Média das frequências ponderada pelo total de aulas de cada disciplina
        ->frequencia->toBe(98.5)
        ->faltas->toBe(11)
        ->ultimoPeriodo->toBe('3º BIMESTRE')
        ->abaixoDaMedia->toBe([
            ['disciplina' => 'MATEMÁTICA', 'periodo' => '1º BIM', 'nota' => '5.5'],
            ['disciplina' => 'HISTÓRIA', 'periodo' => '2º BIM', 'nota' => '4.5'],
        ]);
});

test('o resumo usa a matrícula selecionada', function () {
    $this->actingAs($this->aluno)->put('/matricula-ativa', ['matricula' => 87654]);

    painel()->matricula();

    $this->gateway->assertCalled('MatriculaAluno', ['codigo_matricula' => 87654]);
});

test('matrícula sem notas nem frequência não quebra o resumo', function () {
    $this->gateway->respondWith('MatriculaAluno', ['codigo_matricula' => 98765, 'grade_aproveitamento' => []]);
    $this->actingAs($this->aluno);

    expect(painel()->matricula())
        ->frequencia->toBeNull()
        ->faltas->toBe(0)
        ->abaixoDaMedia->toBe([]);
});

test('aulas de hoje em ordem de horário', function () {
    $this->actingAs($this->aluno);

    $aulas = painel()->aulas(CarbonImmutable::parse('2026-10-12 10:00')); // segunda-feira

    expect($aulas['dia'])->toBe('SEGUNDA-FEIRA')
        ->and($aulas['hoje'])->toBeTrue()
        ->and($aulas['aulas'])->toHaveCount(5)
        ->and($aulas['aulas'][0])->toBe(['inicio' => '07:00', 'fim' => '07:50', 'disciplina' => 'LÍNGUA PORTUGUESA', 'professor' => 'FERNANDA COSTA']);
});

test('no fim de semana mostra as aulas do próximo dia letivo', function () {
    $this->actingAs($this->aluno);

    $aulas = painel()->aulas(CarbonImmutable::parse('2026-10-17 10:00')); // sábado

    expect($aulas['dia'])->toBe('SEGUNDA-FEIRA')->and($aulas['hoje'])->toBeFalse();
});

test('turma sem horários cadastrados', function () {
    $this->gateway->respondWith('HorarioAulaMatriculaAluno', []);
    $this->actingAs($this->aluno);

    expect(painel()->aulas())->toBe(['dia' => null, 'hoje' => false, 'aulas' => []]);
});

test('livros não devolvidos, com atraso calculado', function () {
    $this->actingAs($this->aluno);

    expect(painel()->emprestimos(CarbonImmutable::parse('2026-10-15')))->toBe([[
        'titulo' => 'DOM CASMURRO',
        'biblioteca' => 'BIBLIOTECA ESCOLAR EMEF NAZARÉ',
        'retirada' => '2026-10-01',
        'devolverAte' => '2026-10-15',
        'atrasado' => false,
    ]])
        ->and(painel()->emprestimos(CarbonImmutable::parse('2026-10-16'))[0]['atrasado'])->toBeTrue();
});

test('falha do e-cidade em um cartão não afeta os outros', function () {
    $this->gateway->respondWith('HorarioAulaMatriculaAluno', new ECidadeUnavailableException('timeout'));

    $this->actingAs($this->aluno)
        ->get('/inicio')
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(['matricula', 'aulas'], fn (Assert $reload) => $reload
                ->where('aulas', ['erro' => true])
                ->where('matricula.codigo', 98765)));
});

test('usuário que não é aluno vê o início sem cartões', function () {
    $this->actingAs(User::factory()->create(['login' => 'admin']))
        ->get('/inicio')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inicio')->missing('matricula'));
});
