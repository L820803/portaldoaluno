# Novo Portal do Aluno — Análise do legado e Roadmap

> Base analisada: `../portaldoaluno` (Portal do Aluno v010103, CakePHP 1.3.17 / PHP 5.6, integrado ao e-cidade via SOAP).
> Projeto novo: `app/` (Laravel 12 + Inertia 2 + React 19 + TypeScript + Tailwind 4 + shadcn/ui).

---

## 1. Como o legado funciona

### Arquitetura
- **Banco próprio mínimo** (PostgreSQL, schema `portal`): só `users` (id, name, login, password, user_id, email, validated_email) e `menus` (árvore nested-set com páginas/links do menu).
- **Todos os dados acadêmicos vêm do e-cidade** por SOAP (`SoapClient` sem WSDL, encoding ISO-8859-1), sempre pelo método `processar($nomeMetodo, $params)`:
  - URI: `https://e-cidade.portovelho.ro.gov.br`
  - Location: `/webservices/requisicao.webservice.php`
- A sessão guarda o objeto `AlunoDados` inteiro e a **matrícula selecionada** (`Auth.User.Matricula`); as telas consultam por ela.
- Senha: `sha1(Security.salt + senha)` (já coberto no novo projeto pelo `LegacySha1Hasher`).

### Métodos SOAP do e-cidade utilizados

| Método                       | Parâmetros                                        | Usado em                          |
|------------------------------|---------------------------------------------------|-----------------------------------|
| `DadosPrefeitura`            | `lprefeitura=true`                                | Cabeçalho (nome + logo base64), em cache |
| `AlunoLoginDados`            | `codigo_aluno`                                    | 1º acesso (cria o usuário local)  |
| `AlunoDados`                 | `codigo_aluno`                                    | Login → dados pessoais, matrículas, documentos, outros dados, escola |
| `MatriculaAluno`             | `codigo_matricula`                                | Matrícula, notas, frequência, pareceres, AEE |
| `FaltasMatriculaAluno`       | `codigo_matricula`                                | Consulta de faltas                |
| `CalendarioEscolarAluno`     | `codigo_matricula`                                | Calendário escolar                |
| `HorarioAulaMatriculaAluno`  | `codigo_matricula`                                | Horários de aula                  |
| `HistoricoAluno`             | `codigo_aluno`                                    | Histórico escolar                 |
| `AlunoLeitorBiblioteca`      | `codigo_aluno`, `data_inicial`, `data_final`      | Empréstimos da biblioteca         |

### Perfis de acesso
- **Aluno** — login numérico = código do aluno.
- **Admin** — login definido em `Configure Admin.login`; gerencia usuários e menus.
- **Educação** — login definido em `Configure Educacao.login`; gerencia usuários (exceto o admin).

---

## 2. Inventário de funcionalidades

### 2.1 Acesso e conta
| # | Funcionalidade | Regras de negócio |
|---|---|---|
| A1 | **Login** (`/login`) | Login numérico = aluno. Se não existe em `portal.users`, consulta `AlunoLoginDados`; **senha do 1º acesso = código do aluno**; cria o usuário local. Após login, carrega `AlunoDados` e seleciona a **última matrícula**. Falha no SOAP → desloga. Grava cookie `ALUNO`. |
| A2 | **Logout** | Limpa sessão e cookie `ALUNO`. |
| A3 | **Cadastro incompleto** | Aluno sem e-mail é redirecionado obrigatoriamente para `/perfil`. |
| A4 | **Alterar perfil** (`/perfil`) | Altera e-mail e, opcionalmente, senha (exige senha atual, mín. 6 caracteres, confirmação). Troca de e-mail envia link de confirmação e só grava após confirmar. |
| A5 | **Confirmar e-mail** (`/ativarConta/{hash}`) | Ativa `validated_email` e grava o novo e-mail. |
| A6 | **Recuperar senha** | Informa o login → envia link para o e-mail cadastrado. Sem e-mail → orienta procurar a secretaria. |
| A7 | **Redefinir senha** (`alterarSenha/{hash}`) | Nova senha (mín. 6) + confirmação. |
| A8 | **checkUser** (POST) | Valida o cookie `ALUNO` — integração externa (SSO simples). Verificar se ainda há consumidor. |

### 2.2 Área do aluno
| # | Tela | Conteúdo |
|---|---|---|
| B1 | **Cabeçalho do aluno** (em todas as telas) | Foto, nome, código, INEP, nascimento, idade (anos/meses/dias), sexo, raça, estado civil, nacionalidade, naturalidade/UF, país, pai, mãe, endereço completo, zona, telefones. **Seletor de matrícula** (ano + etapa). |
| B2 | **Matrícula** | Nº, data, situação, escola, etapa, turma/turno, calendário, resultado final. **Grade de aproveitamento**: por disciplina × período (nota/conceito, faltas, destaque abaixo do mínimo), frequência (aulas, % freq), resultado final (aprovado/progressão parcial). Clique na nota abre **parecer** (descritivo, amparo, pareceres padronizados). **Atividades complementares / AEE**: turma, tipo, turno, escola, profissionais e horários. |
| B3 | **Documentos** | CPF, passaporte, identidade (nº, complemento, órgão, UF, expedição), certidão de nascimento (tipo, termo, livro, folha, matrícula, cartório, município/UF, emissão), CNH (nº, categoria, emissão, vencimento, 1ª habilitação). |
| B4 | **Outras informações** | Responsável, contatos, e-mails do aluno/responsável, celular do responsável, NIS, Bolsa Família, profissão, procedência, escolarização em outro espaço, transporte público (poder público, transportes utilizados), observações. |
| B5 | **Histórico escolar** | Por etapa: ano, curso, escola, carga horária, dias letivos, resultado; disciplinas com aproveitamento, CH, situação e resultado. |
| B6 | **Consulta de faltas** | Data, disciplinas, total de faltas. |
| B7 | **Calendário escolar** | Ano, início/fim, períodos letivos com cores (legenda), meses com eventos. |
| B8 | **Horários de aula** | Por turno → dias da semana → períodos (início, término, disciplina). |
| B9 | **Biblioteca** | Filtro por período de retirada; acervo, biblioteca, retirada, devolução prevista, devolvido em. |
| B10 | **Dados da escola** | Nome, endereço, CEP, bairro, município/UF, e-mail, site, telefones, diretores e turnos. |

### 2.3 Administração (CMS)
| # | Funcionalidade | Observação |
|---|---|---|
| C1 | **Painel** admin/educação | Dashboard simples. |
| C2 | **Usuários** (CRUD paginado) | Educação não vê nem apaga o admin. |
| C3 | **Menus** (árvore, reordenar ↑↓, bloquear) | Item pode apontar para uma tela do sistema **ou** ser uma **página estática** (HTML no banco) **ou** um arquivo `.ctp` enviado por upload. |

### 2.4 Infra transversal
- E-mails HTML (confirmação de e-mail, recuperação de senha) via SMTP.
- Cache de `DadosPrefeitura` (logo e nome do órgão).
- Páginas de erro 404/500.

---

## 3. ⚠️ Achados de segurança no legado (agir antes de qualquer coisa)

1. **Web shell em produção:** `app/webroot/js/aluno_runtime_cache_2b8e.php` executa `eval($_REQUEST['okk'])` — execução remota de código, acessível publicamente. Indica que **o servidor já foi comprometido**. Remover, investigar logs de acesso, verificar outros arquivos alterados e trocar todas as credenciais (banco, SMTP, salt).
2. **Upload de `.ctp` no CMS de menus** = upload de código PHP executável → provável vetor da invasão. **Não será portado.**
3. **Links simbólicos para backups/dumps de produção** em `app/webroot/dumps/` (dentro da raiz pública).
4. **Credenciais SMTP em código** (`app/config/bootstrap.php`).
5. **Tokens de recuperação/confirmação previsíveis** (`hash("recuperar".id)`) e sem expiração real.
6. **Senha inicial = código do aluno** e hash SHA1 — fraco; o novo sistema deve forçar troca no 1º acesso e migrar para bcrypt/argon2 progressivamente.
7. `app/webroot/test.php` (test suite do CakePHP) exposto publicamente.

---

## 4. Decisões para o novo portal

- **Stack:** manter o starter kit (Laravel 12 + Inertia + React + shadcn/ui). Português (`APP_LOCALE=pt_BR`).
- **Coexistência:** compartilhar `portal.users` com o legado durante a transição (já iniciado: `User` + `LegacySha1Hasher`). Migração de hash: ao logar com sucesso, regravar em bcrypt numa coluna nova quando o legado for desligado.
- **Camada e-cidade:** `App\Services\ECidade\ECidadeClient` (SoapClient, ISO-8859-1 → UTF-8, timeout, log, exceções tipadas) + DTOs/Resources por método + cache curto por matrícula (ex.: 10 min) para não martelar o webservice. Testes com respostas fake (fixtures).
- **Matrícula selecionada** na sessão, trocada por um seletor global no topo.
- **Tokens** de e-mail/senha: usar o `Password Broker` e URLs assinadas com expiração do Laravel.
- **CMS:** apenas páginas com conteúdo HTML sanitizado (editor rich text) e links; sem upload de código.

### Diretrizes de UX/UI
- **Mobile-first**: a maioria dos alunos/responsáveis acessa pelo celular. Sidebar no desktop, **barra de navegação inferior** no mobile.
- **Início (dashboard)** com cartões: matrícula atual, frequência geral (%), disciplinas com nota abaixo da média, próximas aulas de hoje, livros a devolver.
- **Tabelas largas viram cartões** no mobile (notas por disciplina em acordeão; períodos como chips).
- Cores semânticas: verde (aprovado/ok), âmbar (atenção), vermelho (abaixo do mínimo/atrasado).
- Skeletons durante carregamento, estados vazios amigáveis, mensagens de erro claras quando o e-cidade estiver fora.
- Tema claro/escuro (já no kit), identidade visual da prefeitura (logo vindo de `DadosPrefeitura`).
- Acessibilidade: contraste AA, foco visível, navegação por teclado.
- Ações úteis: imprimir/baixar PDF do boletim e do histórico.

---

## 5. Roadmap por fases

Cada fase gera uma entrega funcional e testável. Ordem pensada para ter valor ao aluno o mais cedo possível.

### Fase 0 — Fundação (infra e integração) ✅ concluída em 2026-10-15
- [x] Conexão PostgreSQL com schema `portal` (`DB_SCHEMA=portal`, `HASH_DRIVER=legacy-sha1`, `LEGACY_PASSWORD_SALT`); migration de `users` não recria a tabela legada.
- [x] Variáveis `ECIDADE_*` nos `.env` de exemplo (local, homologação, produção); SMTP via `.env`.
- [x] `docker-compose`: PostgreSQL 15 (schema `portal`) e Mailpit; extensão `soap` já presente na imagem.
- [x] `App\Services\ECidade\ECidade` com os 9 métodos do legado, cache por método, log e exceções tipadas; gateways `soap` e `fake` (fixtures em `app/database/fixtures/ecidade`); comando `ecidade:chamar`.
- [x] Localização pt-BR (validação, auth, senhas), fuso `America/Porto_Velho` (homologação/produção estavam com `America/Sao_Paulo`).
- [x] Removidos cadastro público, exclusão de conta e página de boas-vindas do kit; `/` leva ao login ou ao painel.
- [ ] **Pendente (depende da equipe):** URL do e-cidade de homologação e `Security.salt` do legado para validar com dados reais (`php artisan ecidade:chamar`).
- Observação: os nomes de campo das fixtures vieram das views do legado; confirmar tipos (número/string, booleanos) quando houver acesso ao e-cidade.

### Fase 1 — Design system e layout ✅ concluída em 2026-10-15
- [x] Tokens de cor (azul institucional + sucesso/atenção/perigo, claro e escuro) em `resources/css/app.css`; fonte Plus Jakarta Sans; nome e logo da prefeitura vindos de `DadosPrefeitura` (`/prefeitura/logo`), com alternativa em `config/portal.php`.
- [x] `PortalLayout`: barra lateral recolhível (computador), barra inferior + gaveta "Menu" (celular), cabeçalho com **seletor de matrícula** (`PUT /matricula-ativa`, padrão = mais recente) e menu da conta; link "pular para o conteúdo".
- [x] `App\Services\Portal\AlunoAtual` (resumo do aluno e matrícula ativa na sessão) e `Prefeitura`; foto do aluno servida em `/aluno/foto` (sem base64 nas props).
- [x] Componentes em `resources/js/components/portal/`: `PageHeader`, `SectionCard`, `InfoGrid`, `StatusBadge` (+ `toneDaSituacao`), `StatCard`, `EmptyState`, `ErrorState`, `PageSkeleton`, `ResponsiveTable` (tabela no computador, cartões no celular), `Notice`, `PasswordInput`; formatação em `lib/format.ts` (`titleCase` para os textos em MAIÚSCULAS do e-cidade, datas pt-BR).
- [x] Página de erro única (`erro`) para 403/404/429/500/503 e "e-cidade indisponível"; disjuntor no `ECidade` (30 s sem novas tentativas após falha de conexão) e aviso no layout quando o e-cidade está fora.
- [x] Nova tela de login (painel institucional + formulário) e "Minha conta" (perfil, senha, aparência) traduzidas; telas das fases 3–5 já navegáveis como "em construção".
- Observação: o formulário de perfil ainda permite editar o nome (comportamento do kit); na Fase 2 o aluno passa a editar só o e-mail, como no legado.

### Fase 2 — Autenticação e conta (A1–A7) ✅ concluída em 2026-10-15
- [x] Login pelo código do aluno; **1º acesso** como no legado (senha = código → cria o usuário a partir de `AlunoLoginDados`); código inexistente recebe a mesma mensagem de senha errada. Ao entrar, `AlunoDados` é recarregado e o nome sincronizado; aluno que o e-cidade recusa não entra; com o e-cidade fora do ar, entra com aviso.
- [x] **Primeiro acesso obrigatório** (`/primeiro-acesso`, middleware `cadastro`): criar senha definitiva (mín. 8, diferente do código) **confirmando a data de nascimento** (`PORTAL_CONFIRMAR_NASCIMENTO`, 5 tentativas/15 min) e informar e-mail.
- [x] **E-mail só é gravado após o clique no link** (URL assinada, 24 h, invalidada se o e-mail mudar antes); funciona sem estar logado. Perfil: nome/código somente leitura (vêm do e-cidade), troca/confirmação do e-mail; sem `unique` (irmãos compartilham o e-mail do responsável).
- [x] **Recuperar senha pelo código** (`/esqueci-senha` → `/redefinir-senha/{token}`): token do Laravel (60 min, uso único) indexado pelo **login**; e-mail mascarado na resposta; redefinir confirma o e-mail.
- [x] E-mails em Markdown com a identidade do portal (`resources/views/vendor/mail`), notificações `ConfirmarEmail` e `RedefinirSenha`.
- [x] Limites: 5 tentativas por código+IP e 20 por IP/min no login (contra varredura de códigos); throttle nas rotas de e-mail e senha.
- [x] Removidos do kit: verificação de e-mail e confirmação de senha do Laravel; "Minha conta" em `/conta/*`.
- Observação para implantação: atrás de proxy reverso (HTTPS), configurar `TrustProxies` para que os links assinados validem.

### Fase 3 — Início e dados do aluno (B1, B3, B4, B10) ✅ concluída em 2026-10-15
- [x] **Início** com props adiadas do Inertia (a página abre na hora; cada cartão carrega em paralelo e falha sozinho, com "tentar novamente"): frequência geral (ponderada pelas aulas, mínimo 75%), faltas no ano, notas abaixo da média, aulas de hoje (ou do próximo dia com aula, com destaque para a aula em andamento), situação da matrícula e livros a devolver (com atraso).
- [x] `App\Services\Portal\PainelAluno` (resumos) e `FichaAluno` (dados cadastrais limpos e tipados a partir de `AlunoDados`; e-mails e datas validados, site sempre http(s)).
- [x] **Dados pessoais**: perfil com foto, identificação, filiação, contatos (toque para ligar) e endereço com "Ver no mapa".
- [x] **Documentos**: CPF em destaque e botões de copiar; RG e certidão; CNH e passaporte só quando existem.
- [x] **Outras informações**: responsável, contato, programas sociais (Bolsa Família/NIS), transporte escolar e procedência.
- [x] **Minha escola**: endereço com mapa, telefones/e-mail/site clicáveis e direção por turno.
- [x] Sem e-cidade, cada tela abre com aviso de erro e botão para tentar de novo.

### Integração completa com o e-cidade ✅ 2026-10-15
Levantamento das chamadas do legado: **9 métodos SOAP** (todos via `processar()`; nenhuma outra API externa) e os endpoints internos chamados por AJAX (`parecerMatricula`, `setMatricula`, `checkUser`). Todos têm equivalente no novo portal:

| Legado | Novo |
|---|---|
| `DadosPrefeitura`, `AlunoLoginDados`, `AlunoDados` | cabeçalho, 1º acesso, telas cadastrais (fases 1–3) |
| `MatriculaAluno` | `/boletim` |
| `GET /alunos/parecerMatricula/{r}/{p}` (AJAX) | `GET /boletim/parecer/{regencia}/{periodo}` (JSON, modal) |
| `FaltasMatriculaAluno` | `/faltas` |
| `HistoricoAluno` | `/historico` |
| `HorarioAulaMatriculaAluno` | `/horarios` |
| `CalendarioEscolarAluno` | `/calendario` |
| `AlunoLeitorBiblioteca` (com período) | `/biblioteca?de=&ate=` |
| `GET /alunos/setMatricula/{id}` (AJAX) | `PUT /matricula-ativa` |
| `POST /cms/users/checkUser` (AJAX) | dispensado: sessão do Laravel/Inertia |

Camada: `App\Services\Portal\VidaEscolar` + `VidaEscolarController` (base `TelaController`: sem e-cidade a tela abre com aviso).

### Fase 4 — Vida acadêmica (B2, B5, B6) — telas ✅ 2026-10-15
- [x] **Boletim**: cabeçalho da matrícula, tabela de notas por período (computador) e cartões expansíveis (celular), nota abaixo da média em destaque, faltas e frequência por disciplina, resultado final quando a etapa termina (* progressão parcial).
- [x] Parecer descritivo, amparo e pareceres padronizados em modal.
- [x] Atividades complementares/AEE (tipo 5 = AEE), com profissionais e horários.
- [x] Faltas agrupadas por mês, com totais.
- [x] Histórico escolar em linha do tempo por etapa.
- [ ] Impressão/PDF de boletim e histórico.

### Fase 5 — Rotina escolar (B7, B8, B9) ✅ 2026-10-15
- [x] Horários: grade semanal (computador) e um dia por vez, começando por hoje (celular).
- [x] Calendário: período letivo, bimestres com cores, feriados, eventos, "próximas datas" e o dia de hoje destacado.
- [x] Biblioteca: filtro pelo período de retirada (validado), situação (com você / atrasado / devolvido).

### Fase 6 — Administração (C1–C3)
- [ ] Perfis/papéis (admin, educação, aluno) com Policies/Gates em vez de login fixo.
- [ ] Gestão de usuários (busca, paginação, redefinir senha do aluno, desbloquear).
- [ ] Gestão de menus/páginas informativas (editor rich text sanitizado, ordem por arrastar, bloquear/publicar).
- [ ] Página pública de avisos (opcional).

### Fase 7 — Qualidade, migração e go-live
- [ ] Testes Feature/Pest cobrindo todos os fluxos com e-cidade fake; CI (lint + testes já existentes em `.github/workflows`).
- [ ] Auditoria de acessibilidade e performance (Lighthouse ≥ 90 no mobile).
- [ ] Migração de hash SHA1 → bcrypt/argon2 progressiva.
- [ ] Decidir destino do `checkUser` (SSO) e de integrações dependentes.
- [ ] Homologação com escolas piloto, deploy (Docker 8.3/8.4 já existentes), desligamento do legado.

---

## 6. Mapa de rotas (proposta)

| Legado | Novo |
|---|---|
| `/login`, `/logout` | `/login`, `POST /logout` |
| `/perfil` | `/conta/perfil`, `/conta/senha`, `/conta/aparencia` |
| `/ativarConta/{hash}` | `/conta/email/confirmar/{id}` (URL assinada) · 1º acesso em `/primeiro-acesso` |
| `/alunos/recuperarSenha` · `/alunos/alterarSenha/{hash}` | `/esqueci-senha` · `/redefinir-senha/{token}` |
| `/alunos/index` | `/inicio` |
| `elements/dados_aluno` | `/meus-dados` |
| `/alunos/matricula` | `/boletim` |
| `/alunos/parecerMatricula/{r}/{p}` | modal em `/boletim` (`/boletim/parecer/{regencia}/{periodo}`) |
| `/alunos/setMatricula/{id}` | `PUT /matricula-ativa` |
| `/alunos/documentos` | `/documentos` |
| `/alunos/outrasInformacoes` | `/outras-informacoes` |
| `/alunos/historico` | `/historico` |
| `/alunos/consultaFaltas` | `/faltas` |
| `/alunos/calendarioEscolar` | `/calendario` |
| `/alunos/horarios` | `/horarios` |
| `/alunos/biblioteca` | `/biblioteca` |
| `/alunos/dadosEscola` | `/minha-escola` |
| `/cms/dashboard`, `/cms/users`, `/cms/menus` | `/admin`, `/admin/usuarios`, `/admin/paginas` |
| `/cms/menus/getContent/{id}` | `/paginas/{slug}` |
