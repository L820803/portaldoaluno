<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | "soap": chama o webservice do e-cidade (produção/homologação).
    | "fake": responde com os JSONs de database/fixtures/ecidade, para
    |         desenvolver o front-end sem acesso ao e-cidade.
    |
    */

    'driver' => env('ECIDADE_DRIVER', 'soap'),

    /*
    |--------------------------------------------------------------------------
    | Webservice SOAP
    |--------------------------------------------------------------------------
    |
    | Mesmos valores de SOAP_URI / SOAP_LOCATION do legado (app/config/bootstrap.php).
    | Produção: https://e-cidade.portovelho.ro.gov.br (/webservices/requisicao.webservice.php).
    | O webservice não tem WSDL: toda chamada vai para o método "processar".
    |
    */

    // Também aceita SOAP_URI / SOAP_LOCATION, os nomes usados no Docker do legado
    'uri' => env('ECIDADE_SOAP_URI') ?: env('SOAP_URI'),

    'location' => env('ECIDADE_SOAP_LOCATION') ?: env('SOAP_LOCATION') ?: ((env('ECIDADE_SOAP_URI') ?: env('SOAP_URI'))
        ? rtrim(env('ECIDADE_SOAP_URI') ?: env('SOAP_URI'), '/').'/webservices/requisicao.webservice.php'
        : null),

    // Segundos para conectar e para aguardar a resposta
    'timeout' => (int) env('ECIDADE_TIMEOUT', 20),

    'verify_ssl' => (bool) env('ECIDADE_VERIFY_SSL', true),

    // Após uma falha de conexão, segundos sem novas tentativas (0 desativa)
    'pausa_apos_falha' => (int) env('ECIDADE_PAUSA_APOS_FALHA', 30),

    'fixtures_path' => database_path('fixtures/ecidade'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | As respostas ficam em cache para não sobrecarregar o e-cidade a cada
    | navegação. TTL em segundos; 0 desativa o cache do método.
    |
    */

    'cache' => [
        'store' => env('ECIDADE_CACHE_STORE'),
        'prefix' => 'ecidade',
        'ttl' => [
            'default' => (int) env('ECIDADE_CACHE_TTL', 600),
            'DadosPrefeitura' => 86400,
            'AlunoLoginDados' => 0,
            'CalendarioEscolarAluno' => 3600,
            'HistoricoAluno' => 3600,
        ],
    ],

];
