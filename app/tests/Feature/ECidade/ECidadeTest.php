<?php

use App\Services\ECidade\ECidade;
use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\FakeGateway;
use App\Services\ECidade\Gateways\Gateway;
use App\Services\ECidade\Gateways\SoapGateway;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config(['ecidade.driver' => 'fake']);
    app()->forgetInstance(Gateway::class);
    app()->forgetInstance(ECidade::class);

    $this->gateway = app(Gateway::class);
    $this->ecidade = app(ECidade::class);
});

test('o driver é escolhido pela configuração', function () {
    expect($this->gateway)->toBeInstanceOf(FakeGateway::class);

    config(['ecidade.driver' => 'soap']);
    app()->forgetInstance(Gateway::class);

    expect(app(Gateway::class))->toBeInstanceOf(SoapGateway::class);
});

test('cada método chama o e-cidade com os parâmetros do legado', function (string $metodo, array $args, string $soap, array $params) {
    $this->ecidade->{$metodo}(...$args);

    $this->gateway->assertCalled($soap, $params);
})->with([
    ['dadosPrefeitura', [], 'DadosPrefeitura', ['lprefeitura' => true]],
    ['alunoLoginDados', [123456], 'AlunoLoginDados', ['codigo_aluno' => 123456]],
    ['alunoDados', [123456], 'AlunoDados', ['codigo_aluno' => 123456]],
    ['matricula', [98765], 'MatriculaAluno', ['codigo_matricula' => 98765]],
    ['faltas', [98765], 'FaltasMatriculaAluno', ['codigo_matricula' => 98765]],
    ['calendario', [98765], 'CalendarioEscolarAluno', ['codigo_matricula' => 98765]],
    ['horarios', [98765], 'HorarioAulaMatriculaAluno', ['codigo_matricula' => 98765]],
    ['historico', [123456], 'HistoricoAluno', ['codigo_aluno' => 123456]],
    ['biblioteca', [123456], 'AlunoLeitorBiblioteca', ['codigo_aluno' => 123456, 'data_inicial' => '', 'data_final' => '']],
]);

test('biblioteca envia o período no formato Y-m-d', function () {
    $this->ecidade->biblioteca(123456, Carbon::parse('2026-03-01'), Carbon::parse('2026-06-30'));

    $this->gateway->assertCalled('AlunoLeitorBiblioteca', [
        'codigo_aluno' => 123456,
        'data_inicial' => '2026-03-01',
        'data_final' => '2026-06-30',
    ]);
});

test('as fixtures têm a estrutura usada pelo portal', function () {
    expect($this->ecidade->alunoDados(123456))
        ->toHaveKeys(['nome_aluno', 'matriculas', 'documentos', 'outros_dados', 'oEscola'])
        ->and($this->ecidade->matricula(98765)['grade_aproveitamento'][0])
        ->toHaveKeys(['iCodigoRegencia', 'sNome', 'aAproveitamento', 'oFrequencia', 'oResultadoFinal'])
        ->and($this->ecidade->calendario(98765))->toHaveKeys(['ano', 'periodos_letivos', 'meses'])
        ->and($this->ecidade->horarios(98765)['turma']['turnos'])->not->toBeEmpty();
});

test('respostas ficam em cache', function () {
    $this->ecidade->alunoDados(123456);
    $this->ecidade->alunoDados(123456);

    $this->gateway->assertCalled('AlunoDados', times: 1);
});

test('fresh() ignora o cache e atualiza o valor guardado', function () {
    $this->ecidade->alunoDados(123456);
    $this->gateway->respondWith('AlunoDados', ['nome_aluno' => 'NOVO NOME']);

    expect($this->ecidade->fresh()->alunoDados(123456)['nome_aluno'])->toBe('NOVO NOME')
        ->and($this->ecidade->alunoDados(123456)['nome_aluno'])->toBe('NOVO NOME');

    $this->gateway->assertCalled('AlunoDados', times: 2);
});

test('AlunoLoginDados nunca usa cache', function () {
    $this->ecidade->alunoLoginDados(123456);
    $this->ecidade->alunoLoginDados(123456);

    $this->gateway->assertCalled('AlunoLoginDados', times: 2);
});

test('forget() remove a resposta do cache', function () {
    $this->ecidade->matricula(98765);
    $this->ecidade->forget('MatriculaAluno', ['codigo_matricula' => 98765]);
    $this->ecidade->matricula(98765);

    $this->gateway->assertCalled('MatriculaAluno', times: 2);
});

test('retorno vazio do e-cidade vira array vazio', function () {
    $this->gateway->respondWith('CalendarioEscolarAluno', null);

    expect($this->ecidade->calendario(98765))->toBe([]);
});

test('falhas não são guardadas em cache', function () {
    $this->gateway->respondWith('AlunoDados', new ECidadeRequestException('erro', 'AlunoDados'));

    expect(fn () => $this->ecidade->alunoDados(123456))->toThrow(ECidadeRequestException::class);

    $this->gateway->respondWith('AlunoDados', ['nome_aluno' => 'OK']);

    expect($this->ecidade->alunoDados(123456)['nome_aluno'])->toBe('OK');
});

test('comando ecidade:chamar exibe a resposta', function () {
    $this->artisan('ecidade:chamar', ['metodo' => 'AlunoDados', 'parametros' => ['codigo_aluno=123456']])
        ->expectsOutputToContain('MARIA EDUARDA')
        ->assertSuccessful();

    $this->gateway->assertCalled('AlunoDados', ['codigo_aluno' => '123456']);
});

test('comando ecidade:chamar informa a falha', function () {
    $this->gateway->respondWith('DadosPrefeitura', new ECidadeUnavailableException('Could not connect to host'));

    $this->artisan('ecidade:chamar')
        ->expectsOutputToContain('Could not connect to host')
        ->assertFailed();
});

test('após falha de conexão o disjuntor evita novas chamadas durante a pausa', function () {
    $this->gateway->respondWith('AlunoDados', new ECidadeUnavailableException('timeout', 'AlunoDados'));

    expect(fn () => $this->ecidade->alunoDados(123456))->toThrow(ECidadeUnavailableException::class)
        ->and(fn () => $this->ecidade->matricula(98765))->toThrow(ECidadeUnavailableException::class);

    $this->gateway->assertNotCalled('MatriculaAluno');

    $this->travel(31)->seconds();

    expect($this->ecidade->matricula(98765))->not->toBeEmpty();
});

test('erro de negócio não aciona o disjuntor', function () {
    $this->gateway->respondWith('AlunoLoginDados', new ECidadeRequestException('Aluno não encontrado'));

    expect(fn () => $this->ecidade->alunoLoginDados(1))->toThrow(ECidadeRequestException::class)
        ->and($this->ecidade->matricula(98765))->not->toBeEmpty();
});
