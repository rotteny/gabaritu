# Registro simplificado das operações de tratamento (LGPD art. 37)

Formato simplificado, conforme o art. 9º da Resolução CD/ANPD nº 2/2022 (agente de tratamento de pequeno porte, que inclui pessoa natural, art. 2º, I).
Atualizar sempre que um dado novo entrar no código. Itens marcados **[verificar com advogado]** não são conclusão jurídica.

- **Controlador:** pessoa física dona do projeto (sem CNPJ, sem fins comerciais). **[verificar com advogado]** se o enquadramento como pequeno porte se mantém: o art. 3º, I exclui quem faz tratamento de alto risco, e o art. 4º, II, d cita dado de adolescente como critério específico (só vira alto risco somado a um critério geral: larga escala ou impacto significativo).
- **Canal do titular (no lugar de encarregado, art. 11 da Res. 2/2022):** `gabaritu@gorilash.com.br`, divulgado em `/privacidade`.
- **Operadores/terceiros:** hospedagem própria (servidor dedicado do dono). Serviço de e-mail transacional: **ainda não escolhido**; quando entrar, listar aqui (recebe o e-mail do aluno).
- **Compartilhamento:** nenhum. Sem anúncio, sem analytics de terceiros, sem Sentry.
- **Transferência internacional:** nenhuma hoje. Revisar ao escolher o e-mail transacional.

| Dado | Onde | Finalidade | Base legal (proposta) | Retenção |
| --- | --- | --- | --- | --- |
| E-mail | `users.email` | login e recuperação de senha | execução do serviço pedido pelo titular (art. 7º, V) **[verificar com advogado]**: titular adolescente, ver nota | até apagar a conta |
| Apelido | `users.apelido` | exibição para o próprio aluno | idem | até apagar a conta |
| Ano de nascimento | `users.ano_nascimento` | idade mínima e teto de zoeira | idem; também proteção do adolescente (art. 14) | até apagar a conta |
| Série | `users.serie` | organizar o estudo | idem | até apagar a conta |
| Respostas, tempos, notas, revisão | `tentativas`, `tentativa_questoes`, `respostas`, `desempenhos_assunto`, `desempenho_assunto_historico`, `revisoes_espacadas`, `feedbacks_fala` | nota estimada, revisão espaçada | idem | até apagar a conta |
| Preferências | `users` (zoeira, som, música, reduzir movimento, última matéria) | personalização | idem | até apagar a conta |
| Aceite da política | `users.privacidade_aceita_em`, `users.privacidade_versao` | prova do aceite (art. 8º, § 2º) | cumprimento de obrigação legal (art. 7º, II) | até apagar a conta |
| Último acesso | `users.ultimo_acesso_em` | operação (contas inativas) | legítimo interesse (art. 7º, IX) **[verificar com advogado]** | até apagar a conta |
| IP e navegador da sessão | `sessions.ip_address`, `sessions.user_agent` | manter o login e segurança | legítimo interesse (art. 7º, IX) | vida da sessão (120 min) + limpeza do coletor de sessões |
| IP, URL e navegador | log de acesso do nginx de borda | segurança e diagnóstico | legítimo interesse (art. 7º, IX) | rotação do servidor (ver `retencao.md`) |
| Senha | `users.password` (hash bcrypt) | autenticação | execução do serviço | até apagar a conta |
| Tipo e status da conta | `users.tipo`, `users.status`, `users.pendente_desde`, `users.aviso_remocao_em` | separar aluno de responsável e controlar a espera de autorização | cumprimento de obrigação legal (art. 24 do ECA Digital) **[verificar com advogado]** | até apagar a conta; pendente há mais de 30 dias é apagado |
| E-mail e parentesco do responsável informados pelo aluno | `users.email_responsavel`, `users.parentesco_responsavel(_outro)`, `convites_responsavel` | enviar o convite de autorização | idem | até o convite ser usado, vencer (7 dias) ou a conta ser apagada |
| Dados do responsável | `users` com `tipo=responsavel`: nome (em `apelido`), e-mail, ano de nascimento, senha (hash) | identificar quem autoriza e comprovar que tem 18 anos ou mais | idem; execução do serviço | enquanto houver aluno vinculado; sem aluno por 30 dias, apagado |
| Vínculo e registro do aceite | `vinculos_responsavel`: parentesco, data e hora, IP, versão dos termos e da política, declarações, status (`ativo`/`suspenso`) | prova da autorização (art. 8º, § 2º; ônus do controlador) | cumprimento de obrigação legal | até o vínculo ser encerrado ou o aluno/responsável ser apagado |
| Convite e pedido de vínculo | `convites_responsavel`: e-mail do destinatário, hash do token, parentesco, aceite do solicitante (IP e data) | convidar responsável, co-responsável, transferência ou pedido do responsável ao aluno | idem | vencimento em 7 dias; vencidos apagados pelo comando diário |
| Teto de zoeira do responsável | `users.zoeira_responsavel` | proteção do adolescente | melhor interesse (art. 14) | até apagar a conta |
| Avatar | `users.avatar` (slug de uma lista fixa de bichos marinhos) | personalização, sem foto | execução do serviço | até apagar a conta |

**Nota sobre adolescentes:** o Enunciado CD/ANPD nº 1/2023 admite qualquer hipótese dos arts. 7º e 11 para dado de criança e adolescente, desde que no melhor interesse deles (art. 14). O cadastro coleta aceite da política, mas o aceite de um adolescente de 13 a 15 anos não é, por si, consentimento válido para atos da vida civil. **[verificar com advogado]** qual base usar e se cabe ciência do responsável, também à luz do art. 24 da Lei 15.211/2025 (ECA Digital).

**Responsável legal:** o dono decidiu que todo aluno menor de 18 anos só usa o serviço depois da autorização de um responsável. Dados mínimos do responsável: nome, e-mail, ano de nascimento, parentesco e registro do aceite; nunca CPF, RG, telefone, endereço, data de nascimento completa, foto ou documento. O responsável vê o desempenho do aluno (notas, assuntos, diagnóstico) e pode limitar o teto de zoeira. O aluno vê quem o acompanha. **[verificar com advogado]**: se a autoafirmação do responsável (declaração + e-mail) basta como verificação da guarda e da idade, e se alguma regulamentação da ANPD exigirá verificação mais forte (entraria como mudança versionada nos Termos).

**Medidas de segurança (art. 46):** senha com hash bcrypt, HTTPS com HSTS, cookie de sessão `Secure`/`HttpOnly`/`SameSite=Lax`, CSRF, rate limit em login (aluno e responsável), cadastro, recuperação de senha, convite, reenvio e aceite, token de convite guardado só como hash e de uso único, login e rotas separadas por tipo, policy `UserPolicy` (IDOR do responsável), proteção contra pedido de adulto estranho (aceite do aluno e de um responsável atual), isolamento por aluno (policy `TentativaPolicy` e consultas filtradas por `user_id`), backup local com rotação de 7 dias.
