<?php

use App\Models\User;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\Gateway;
use App\Services\Portal\VidaEscolar;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gateway = app(Gateway::class);
    $this->aluno = User::factory()->create(['login' => '123456']);
});

function vida(): VidaEscolar
{
    return app(VidaEscolar::class);
}

test('cada tela chama o mesmo método do e-cidade que o legado', function (string $uri, string $componente, string $metodo, array $params) {
    $this->actingAs($this->aluno)
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($componente)->where('erro', false)->has('dados'));

    $this->gateway->assertCalled($metodo, $params);
})->with([
    ['/boletim', 'aluno/boletim', 'MatriculaAluno', ['codigo_matricula' => 98765]],
    ['/faltas', 'aluno/faltas', 'FaltasMatriculaAluno', ['codigo_matricula' => 98765]],
    ['/historico', 'aluno/historico', 'HistoricoAluno', ['codigo_aluno' => '123456']],
    ['/horarios', 'aluno/horarios', 'HorarioAulaMatriculaAluno', ['codigo_matricula' => 98765]],
    ['/calendario', 'aluno/calendario', 'CalendarioEscolarAluno', ['codigo_matricula' => 98765]],
    ['/biblioteca', 'aluno/biblioteca', 'AlunoLeitorBiblioteca', ['codigo_aluno' => '123456', 'data_inicial' => '', 'data_final' => '']],
]);

test('as telas usam a matrícula selecionada', function () {
    $this->actingAs($this->aluno)->put('/matricula-ativa', ['matricula' => 87654]);

    $this->get('/faltas');

    $this->gateway->assertCalled('FaltasMatriculaAluno', ['codigo_matricula' => 87654]);
});

test('com o e-cidade fora do ar a tela abre com aviso', function (string $uri, string $metodo) {
    $this->gateway->respondWith($metodo, new ECidadeUnavailableException('timeout'));

    $this->actingAs($this->aluno)
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('erro', true)->where('dados', null));
})->with([
    ['/boletim', 'MatriculaAluno'],
    ['/historico', 'HistoricoAluno'],
    ['/calendario', 'CalendarioEscolarAluno'],
]);

test('boletim: cabeçalho, períodos e notas com indicação de parecer', function () {
    $this->actingAs($this->aluno);
    $b = vida()->boletim();

    expect($b['matricula'])->toMatchArray([
        'codigo' => 98765,
        'data' => '2026-01-20',
        'turma' => '8º ANO A',
        'resultado' => 'EM ANDAMENTO',
        'emAndamento' => true,
    ])
        ->and($b['periodos'])->toHaveCount(4)
        ->and($b['periodos'][0])->toBe(['codigo' => 1, 'nome' => '1º BIMESTRE', 'abreviatura' => '1º BIM', 'formaAvaliacao' => 'NOTA'])
        ->and($b['disciplinas'])->toHaveCount(8);

    $historia = collect($b['disciplinas'])->firstWhere('nome', 'HISTÓRIA');

    expect($historia['regencia'])->toBe(104)
        ->and($historia['frequencia'])->toBe(98.4)
        ->and($historia['notas'][1])->toBe(['periodo' => 2, 'nota' => '4.5', 'faltas' => 0, 'atingiuMinimo' => false, 'temParecer' => true])
        ->and($historia['notas'][3]['nota'])->toBeNull();

    expect($b['atividades'][0])->toMatchArray(['turma' => 'ESPORTES TARDE', 'tipo' => 'Atividade complementar', 'atividades' => ['FUTSAL', 'XADREZ']])
        ->and($b['atividades'][0]['profissionais'][0]['horarios'])->toHaveCount(2);
});

test('turma do tipo 5 é identificada como AEE', function () {
    $m = json_decode(file_get_contents(database_path('fixtures/ecidade/MatriculaAluno.json')), true);
    $m['atividades_complementares'][0]['tipo_turma'] = 5;
    $this->gateway->respondWith('MatriculaAluno', $m);
    $this->actingAs($this->aluno);

    expect(vida()->boletim()['atividades'][0]['tipo'])->toBe('AEE');
});

test('endpoint de parecer devolve o parecer da nota (parecerMatricula do legado)', function () {
    $this->actingAs($this->aluno)
        ->getJson('/boletim/parecer/104/2')
        ->assertOk()
        ->assertJson([
            'disciplina' => 'HISTÓRIA',
            'periodo' => '2º BIMESTRE',
            'nota' => '4.5',
            'amparo' => null,
            'padronizados' => [
                ['descricao' => 'Participa das atividades propostas', 'legenda' => 'S'],
                ['descricao' => 'Entrega as tarefas no prazo', 'legenda' => 'AV'],
            ],
        ])
        ->assertJsonPath('parecer', fn ($p) => str_starts_with($p, 'A aluna precisa'));
});

test('nota sem parecer ou inexistente responde 404', function (string $uri) {
    $this->actingAs($this->aluno)->getJson($uri)->assertNotFound();
})->with(['/boletim/parecer/101/1', '/boletim/parecer/999/1', '/boletim/parecer/104/9']);

test('parecer com o e-cidade fora do ar responde 503 com mensagem', function () {
    $this->gateway->respondWith('MatriculaAluno', new ECidadeUnavailableException('timeout'));

    $this->actingAs($this->aluno)->getJson('/boletim/parecer/104/2')->assertStatus(503)->assertJsonStructure(['message']);
});

test('parecer exige login', function () {
    $this->getJson('/boletim/parecer/104/2')->assertUnauthorized();
});

test('faltas da mais recente para a mais antiga, com disciplinas separadas', function () {
    $this->actingAs($this->aluno);

    expect(vida()->faltas())->toBe([
        'faltas' => [
            ['data' => '2026-08-19', 'disciplinas' => ['HISTÓRIA', 'GEOGRAFIA', 'ARTE'], 'total' => 3],
            ['data' => '2026-05-04', 'disciplinas' => ['LÍNGUA PORTUGUESA'], 'total' => 1],
            ['data' => '2026-03-12', 'disciplinas' => ['MATEMÁTICA', 'CIÊNCIAS'], 'total' => 2],
        ],
        'total' => 6,
        'dias' => 3,
    ]);
});

test('aluno sem faltas', function () {
    $this->gateway->respondWith('FaltasMatriculaAluno', []);
    $this->actingAs($this->aluno);

    expect(vida()->faltas())->toBe(['faltas' => [], 'total' => 0, 'dias' => 0]);
});

test('histórico com a etapa mais recente primeiro', function () {
    $this->actingAs($this->aluno);
    $h = vida()->historico();

    expect(array_column($h['etapas'], 'ano'))->toBe([2025, 2024])
        ->and($h['etapas'][0])->toMatchArray(['etapa' => '7º ANO', 'resultado' => 'APROVADO', 'cargaHoraria' => '800'])
        ->and($h['etapas'][0]['disciplinas'][1])->toBe([
            'nome' => 'MATEMÁTICA', 'situacao' => 'CONCLUÍDO', 'aproveitamento' => '6.5', 'resultado' => 'APROVADO', 'cargaHoraria' => '200',
        ]);
});

test('horários em grade: períodos × dias da semana', function () {
    $this->actingAs($this->aluno);
    $turno = vida()->horarios()['turnos'][0];

    expect($turno['nome'])->toBe('MANHÃ')
        ->and($turno['dias'])->toBe(['SEGUNDA-FEIRA', 'TERÇA-FEIRA', 'QUARTA-FEIRA', 'QUINTA-FEIRA', 'SEXTA-FEIRA'])
        ->and($turno['periodos'])->toHaveCount(5)
        ->and($turno['periodos'][0]['inicio'])->toBe('07:00')
        ->and($turno['periodos'][0]['aulas'][1])->toBe(['disciplina' => 'MATEMÁTICA', 'professor' => 'MARCOS VINÍCIUS ROCHA']);
});

test('dia com menos períodos fica com a célula vazia', function () {
    $h = json_decode(file_get_contents(database_path('fixtures/ecidade/HorarioAulaMatriculaAluno.json')), true);
    array_pop($h['turma']['turnos'][0]['dias_semana'][4]['periodos']);
    $this->gateway->respondWith('HorarioAulaMatriculaAluno', $h);
    $this->actingAs($this->aluno);

    expect(vida()->horarios()['turnos'][0]['periodos'][4]['aulas'][4])->toBeNull();
});

test('calendário com períodos, meses e eventos', function () {
    $this->actingAs($this->aluno);
    $c = vida()->calendario();

    expect($c['ano'])->toBe(2026)
        ->and($c['inicio'])->toBe('2026-02-09')
        ->and($c['periodos'])->toHaveCount(4)
        ->and($c['meses'])->toHaveCount(11)
        ->and($c['meses'][0]['nome'])->toBe('FEVEREIRO');

    $carnaval = collect($c['meses'][0]['dias'])->firstWhere('data', '2026-02-16');

    expect($carnaval)->toBe(['data' => '2026-02-16', 'letivo' => false, 'periodo' => '1º BIMESTRE', 'eventos' => [['nome' => 'CARNAVAL', 'letivo' => false]]]);
});

test('matrícula sem calendário cadastrado', function () {
    $this->gateway->respondWith('CalendarioEscolarAluno', null);

    $this->actingAs($this->aluno)
        ->get('/calendario')
        ->assertInertia(fn (Assert $page) => $page->where('dados', null)->where('erro', false));
});

test('biblioteca: situação de cada empréstimo', function () {
    $this->actingAs($this->aluno);

    expect(vida()->biblioteca(hoje: CarbonImmutable::parse('2026-10-20'))['emprestimos'])->toBe([
        ['titulo' => 'DOM CASMURRO', 'biblioteca' => 'BIBLIOTECA ESCOLAR EMEF NAZARÉ', 'retirada' => '2026-10-01', 'devolverAte' => '2026-10-15', 'devolvidoEm' => null, 'situacao' => 'atrasado'],
        ['titulo' => 'O PEQUENO PRÍNCIPE', 'biblioteca' => 'BIBLIOTECA ESCOLAR EMEF NAZARÉ', 'retirada' => '2026-09-01', 'devolverAte' => '2026-09-15', 'devolvidoEm' => '2026-09-14', 'situacao' => 'devolvido'],
    ]);
});

test('biblioteca filtra pelo período de retirada', function () {
    $this->actingAs($this->aluno)
        ->get('/biblioteca?de=2026-09-01&ate=2026-09-30')
        ->assertInertia(fn (Assert $page) => $page->where('filtro', ['de' => '2026-09-01', 'ate' => '2026-09-30']));

    $this->gateway->assertCalled('AlunoLeitorBiblioteca', ['codigo_aluno' => '123456', 'data_inicial' => '2026-09-01', 'data_final' => '2026-09-30']);
});

test('filtro da biblioteca com datas inválidas é rejeitado', function (string $query) {
    $this->actingAs($this->aluno)->get("/biblioteca?{$query}")->assertSessionHasErrors();

    $this->gateway->assertNotCalled('AlunoLeitorBiblioteca');
})->with(['de=01/09/2026', 'de=2026-09-30&ate=2026-09-01']);

test('as telas exigem login e cadastro completo', function (string $uri) {
    $this->get($uri)->assertRedirect('/login');

    $this->actingAs(User::factory()->primeiroAcesso()->create(['login' => '222222']))
        ->get($uri)
        ->assertRedirect(route('primeiro-acesso'));
})->with(['/boletim', '/faltas', '/historico', '/horarios', '/calendario', '/biblioteca']);
