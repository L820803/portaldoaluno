<?php

use App\Services\ECidade\Exceptions\ECidadeRequestException;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use App\Services\ECidade\Gateways\SoapGateway;

/**
 * SoapClient que não acessa a rede: responde com $response ou lança $fault.
 */
function fakeSoapClient(mixed $response = null, ?SoapFault $fault = null, array &$captured = []): Closure
{
    return function (array $options) use ($response, $fault, &$captured) {
        $captured['options'] = $options;

        return new class(null, $options + ['uri' => 'x', 'location' => 'x'], $response, $fault, $captured) extends SoapClient
        {
            public function __construct(?string $wsdl, array $options, private mixed $response, private ?SoapFault $fault, private array &$captured)
            {
                parent::__construct($wsdl, $options);
            }

            public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
            {
                $this->captured['call'] = [$name, $args];

                if ($this->fault) {
                    throw $this->fault;
                }

                return $this->response;
            }
        };
    };
}

function gateway(Closure $factory): SoapGateway
{
    return new SoapGateway('https://ecidade.test', 'https://ecidade.test/webservices/requisicao.webservice.php', 5, true, $factory);
}

test('chama o método processar com o nome do método e os parâmetros', function () {
    $captured = [];
    gateway(fakeSoapClient([], captured: $captured))->call('AlunoDados', ['codigo_aluno' => 123]);

    expect($captured['call'])->toBe(['processar', ['AlunoDados', ['codigo_aluno' => 123]]])
        ->and($captured['options'])->toMatchArray([
            'uri' => 'https://ecidade.test',
            'location' => 'https://ecidade.test/webservices/requisicao.webservice.php',
            'connection_timeout' => 5,
        ])
        ->and($captured['options'])->not->toHaveKey('encoding');
});

test('converte objetos em arrays recursivamente', function () {
    $resposta = (object) [
        'nome' => 'Fulano',
        'matriculas' => [(object) ['codigo_matricula' => 1], (object) ['codigo_matricula' => 2]],
        'idade' => (object) ['anos' => 10],
    ];

    expect(gateway(fakeSoapClient($resposta))->call('AlunoDados'))->toBe([
        'nome' => 'Fulano',
        'matriculas' => [['codigo_matricula' => 1], ['codigo_matricula' => 2]],
        'idade' => ['anos' => 10],
    ]);
});

test('converte strings ISO-8859-1 para UTF-8 e mantém UTF-8 válido', function () {
    $latin1 = mb_convert_encoding('CIÊNCIAS', 'ISO-8859-1', 'UTF-8');

    expect(SoapGateway::normalize(['a' => $latin1, 'b' => 'MATEMÁTICA', 'c' => 7, 'd' => null]))
        ->toBe(['a' => 'CIÊNCIAS', 'b' => 'MATEMÁTICA', 'c' => 7, 'd' => null]);
});

test('falha de transporte vira ECidadeUnavailableException', function () {
    gateway(fakeSoapClient(fault: new SoapFault('HTTP', 'Could not connect to host')))->call('AlunoDados');
})->throws(ECidadeUnavailableException::class, 'Could not connect to host');

test('erro de negócio vira ECidadeRequestException com a mensagem do e-cidade', function () {
    try {
        gateway(fakeSoapClient(fault: new SoapFault('Server', 'Aluno não encontrado')))->call('AlunoLoginDados');
    } catch (ECidadeRequestException $e) {
        expect($e->userMessage())->toBe('Aluno não encontrado')
            ->and($e->method)->toBe('AlunoLoginDados');

        return;
    }

    $this->fail('Exceção não lançada');
});

test('sem URI configurada lança ECidadeUnavailableException', function () {
    (new SoapGateway('', ''))->call('DadosPrefeitura');
})->throws(ECidadeUnavailableException::class);
