# Novo Portal do Aluno

Reescrita do Portal do Aluno (legado CakePHP em `../portaldoaluno`) em Laravel 12 + Inertia + React.
Plano de desenvolvimento e inventário de funcionalidades: [`docs/ROADMAP.md`](docs/ROADMAP.md).

## Ambiente local

Serviços (`docker-compose.yaml`): aplicação (`app-scie`, porta 80), Redis e Mailpit (e-mails em http://localhost:8025).

```bash
docker-compose up -d
cp app/.env.example app/.env          # primeira vez
docker exec app-scie php artisan key:generate
```

O portal **não usa banco de dados**. Os usuários ficam em `app/storage/app/private/usuarios.json`, criado na primeira
leitura a partir de `app/database/fixtures/usuarios.json`. Apague o arquivo para voltar ao estado inicial.

Com `ECIDADE_DRIVER=fake` (dados fictícios, incluindo a foto do aluno):

- Aluno pronto: **123456 / aluno123**.
- Primeiro acesso: qualquer outro código numérico, usando o próprio código como senha (ex.: **108150 / 108150**). O portal pede
  a data de nascimento (**21/03/2013**), a nova senha e um e-mail — o link de confirmação chega no Mailpit (http://localhost:8025).

> Ao trocar o driver, limpe o cache (`php artisan cache:clear`) e apague `usuarios.json`.

## Integração com o e-cidade

Todos os dados acadêmicos vêm do webservice SOAP do e-cidade, acessado pelo serviço `App\Services\ECidade\ECidade`:

```php
$ecidade = app(ECidade::class);
$ecidade->alunoDados($codigoAluno);      // dados pessoais, matrículas, documentos, escola
$ecidade->matricula($codigoMatricula);   // notas, frequência, pareceres, AEE
$ecidade->fresh()->alunoDados($codigo);  // ignora o cache
```

| `ECIDADE_DRIVER` | Uso |
|---|---|
| `fake` | Responde com `app/database/fixtures/ecidade/*.json` (dados fictícios). Padrão no ambiente local e nos testes. |
| `soap` | Chama o webservice real (`ECIDADE_SOAP_URI`, `ECIDADE_SOAP_LOCATION`). |

Erros: `ECidadeUnavailableException` (e-cidade fora do ar/timeout) e `ECidadeRequestException` (erro de negócio, com mensagem do e-cidade para o usuário).

### Usar o e-cidade real (como o legado)

O legado usa o webservice de `https://e-cidade.portovelho.ro.gov.br/webservices/requisicao.webservice.php`. Para o novo portal usar o mesmo:

```dotenv
ECIDADE_DRIVER=soap
ECIDADE_SOAP_URI=https://e-cidade.portovelho.ro.gov.br
# Salt do legado (Security.salt em app/config/core.php do portaldoaluno)
LEGACY_PASSWORD_SALT=...
```

Homologação (`.env.homologacao`) já vem assim. No login, o e-cidade é consultado como no legado:
`AlunoLoginDados` no primeiro acesso (senha = código do aluno) e `AlunoDados` ao entrar; a senha é conferida
em `usuarios.json` (o webservice não valida senhas). Para entrar com o e-cidade real é preciso um código de
aluno real e fazer o primeiro acesso (senha = código).

Diagnóstico da conexão:

```bash
docker exec app-scie php artisan ecidade:chamar                                   # DadosPrefeitura
docker exec -e ECIDADE_DRIVER=soap app-scie php artisan ecidade:chamar             # e-cidade real
docker exec app-scie php artisan ecidade:chamar AlunoDados codigo_aluno=123456
```

## Testes e qualidade

```bash
docker exec app-scie php artisan test
cd app && npx tsc --noEmit && npx eslint resources/js
```
