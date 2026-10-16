<?php

namespace App\Services\Portal;

use App\Services\ECidade\Exceptions\ECidadeException;

/**
 * Dados cadastrais do aluno (AlunoDados) organizados para as telas
 * "Meus dados", "Documentos", "Outras informações" e "Minha escola"
 * (elements/dados_aluno, documentos, outras_informacoes e dados_escola no legado).
 *
 * Textos vêm como o e-cidade envia (em maiúsculas); valores vazios viram null.
 * Datas ficam em AAAA-MM-DD e são formatadas no front-end.
 *
 * @throws ECidadeException
 */
class FichaAluno
{
    public function __construct(protected AlunoAtual $aluno) {}

    public function identificacao(): array
    {
        $d = $this->aluno->dados();
        $outros = $d['outros_dados'] ?? [];

        return [
            'nome' => self::texto($d, 'nome_aluno'),
            'codigo' => self::texto($d, 'codigo_aluno') ?? $this->aluno->codigo(),
            'inep' => self::texto($d, 'codigo_inep'),
            'foto' => $this->aluno->urlFoto(),
            'nascimento' => self::data($d, 'data_nascimento_aluno'),
            'idade' => isset($d['idade_aluno']['anos']) ? (int) $d['idade_aluno']['anos'] : null,
            'sexo' => self::texto($d, 'sexo_aluno'),
            'raca' => self::texto($d, 'raca_aluno'),
            'estadoCivil' => self::texto($d, 'estado_civil_aluno'),
            'nacionalidade' => self::texto($d, 'nacionalidade_aluno'),
            'pais' => self::texto($d, 'pais_aluno'),
            'naturalidade' => self::juntar([self::texto($d, 'municipio_naturalidade_aluno'), self::texto($d, 'uf_naturalidade_aluno')], ' - '),
            'filiacao' => [
                'mae' => self::texto($outros, 'mae_aluno'),
                'pai' => self::texto($outros, 'pai_aluno'),
            ],
            'endereco' => [
                'logradouro' => self::texto($d, 'endereco_residencia_aluno'),
                'numero' => self::texto($d, 'numero_residencia_aluno'),
                'complemento' => self::texto($d, 'complemento_residencia_aluno'),
                'bairro' => self::texto($d, 'bairro_residencia_aluno'),
                'municipio' => self::texto($d, 'municipio_residencia_aluno'),
                'uf' => self::texto($d, 'uf_residencia_aluno'),
                'cep' => self::texto($d, 'cep_residencia_aluno'),
                'zona' => self::texto($d, 'zona_residencia_aluno'),
            ],
            'contatos' => [
                'telefone' => self::texto($d, 'telefone_aluno'),
                'celular' => self::texto($d, 'telefone_celular_aluno'),
            ],
        ];
    }

    public function documentos(): array
    {
        $doc = $this->aluno->dados()['documentos'] ?? [];
        $rg = $doc['identidade'] ?? [];
        $certidao = $doc['certidao_nascimento'] ?? [];
        $cnh = $doc['cnh'] ?? [];

        return [
            'cpf' => self::texto($doc, 'cpf'),
            'passaporte' => self::texto($doc, 'passaporte'),
            'identidade' => self::ouNull([
                'numero' => self::texto($rg, 'numero'),
                'complemento' => self::texto($rg, 'complemento'),
                'orgaoEmissor' => self::texto($rg, 'orgao_emissor'),
                'uf' => self::texto($rg, 'uf_emissao'),
                'expedicao' => self::data($rg, 'data_expedicao'),
            ]),
            'certidao' => self::ouNull([
                'tipo' => self::texto($certidao, 'tipo_certidao'),
                'matricula' => self::texto($certidao, 'matricula'),
                'termo' => self::texto($certidao, 'numero_termo'),
                'livro' => self::texto($certidao, 'livro'),
                'folha' => self::texto($certidao, 'folha'),
                'cartorio' => self::texto($certidao, 'cartorio'),
                'municipio' => self::juntar([self::texto($certidao, 'municipio'), self::texto($certidao, 'uf')], ' - '),
                'emissao' => self::data($certidao, 'data_emissao'),
            ]),
            'cnh' => self::ouNull([
                'numero' => self::texto($cnh, 'numero'),
                'categoria' => self::texto($cnh, 'categoria'),
                'emissao' => self::data($cnh, 'data_emissao'),
                'vencimento' => self::data($cnh, 'data_vencimento'),
                'primeiraHabilitacao' => self::data($cnh, 'primeira_cnh'),
            ]),
        ];
    }

    public function outrasInformacoes(): array
    {
        $o = $this->aluno->dados()['outros_dados'] ?? [];
        $meios = array_values(array_filter(array_map(
            fn ($m) => is_string($m) ? (trim($m) ?: null) : null,
            (array) ($o['transportes_utilizados'] ?? []),
        )));

        return [
            'responsavel' => [
                'nome' => self::texto($o, 'responsavel_aluno'),
                'email' => self::email($o, 'email_responsavel_aluno'),
                'celular' => self::texto($o, 'celular_responsavel_aluno'),
            ],
            'contato' => [
                'email' => self::email($o, 'email_aluno'),
                'telefone' => self::texto($o, 'contato_aluno'),
            ],
            'programas' => [
                'bolsaFamilia' => self::texto($o, 'bolsa_familia_aluno'),
                'nis' => self::texto($o, 'numero_nis_aluno'),
            ],
            'transporte' => [
                'publico' => self::texto($o, 'transporte_publico_aluno'),
                'poderPublico' => self::texto($o, 'poder_publico_transporte'),
                'meios' => $meios,
            ],
            'procedencia' => [
                'local' => self::texto($o, 'local_procedencia'),
                'data' => self::data($o, 'data_procedencia'),
            ],
            'profissao' => self::texto($o, 'profissao_aluno'),
            'escolarizacaoOutroEspaco' => self::texto($o, 'escolarizacao_outro_espaco_aluno'),
            'observacao' => self::texto($o, 'observacao_aluno'),
        ];
    }

    public function escola(): ?array
    {
        $e = $this->aluno->dados()['oEscola'] ?? [];

        if (self::texto($e, 'sNome') === null) {
            return null;
        }

        $telefones = array_values(array_filter(array_map(
            fn ($t) => preg_replace('/\D/', '', ($t['iDDD'] ?? '').($t['iNumero'] ?? '')) ?: null,
            (array) ($e['aTelefones'] ?? []),
        )));

        $diretores = array_values(array_filter(array_map(fn ($d) => self::texto($d, 'sNome') ? [
            'nome' => self::texto($d, 'sNome'),
            'turno' => self::texto($d, 'sTurno'),
        ] : null, (array) ($e['aDiretores'] ?? []))));

        $site = self::texto($e, 'sUrl');

        return [
            'nome' => self::texto($e, 'sNome'),
            'endereco' => [
                'logradouro' => self::texto($e, 'sEndereco'),
                'numero' => self::texto($e, 'iNumeroEndereco'),
                'complemento' => self::texto($e, 'sComplementoEndereco'),
                'bairro' => self::texto($e, 'sBairro'),
                'municipio' => self::texto($e, 'sMunicipio'),
                'uf' => self::texto($e, 'sUf'),
                'cep' => self::texto($e, 'sCep'),
            ],
            'telefones' => $telefones,
            'email' => self::email($e, 'sEmail'),
            // Só links http(s): o valor vem de cadastro livre
            'site' => $site && ! preg_match('#^https?://#i', $site) ? 'http://'.$site : $site,
            'diretores' => $diretores,
        ];
    }

    protected static function texto(array $dados, string $chave): ?string
    {
        $valor = $dados[$chave] ?? null;

        if (! is_scalar($valor)) {
            return null;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    protected static function data(array $dados, string $chave): ?string
    {
        $valor = self::texto($dados, $chave);

        return $valor && preg_match('/^\d{4}-\d{2}-\d{2}/', $valor) ? substr($valor, 0, 10) : null;
    }

    protected static function email(array $dados, string $chave): ?string
    {
        $valor = self::texto($dados, $chave);

        return $valor && filter_var($valor, FILTER_VALIDATE_EMAIL) ? mb_strtolower($valor) : null;
    }

    protected static function juntar(array $partes, string $separador): ?string
    {
        $partes = array_filter($partes);

        return $partes ? implode($separador, $partes) : null;
    }

    /**
     * Grupo de campos todo vazio vira null ("não informado").
     */
    protected static function ouNull(array $grupo): ?array
    {
        return array_filter($grupo, fn ($v) => $v !== null) ? $grupo : null;
    }
}
