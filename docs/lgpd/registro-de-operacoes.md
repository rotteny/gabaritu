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

**Nota sobre adolescentes:** o Enunciado CD/ANPD nº 1/2023 admite qualquer hipótese dos arts. 7º e 11 para dado de criança e adolescente, desde que no melhor interesse deles (art. 14). O cadastro coleta aceite da política, mas o aceite de um adolescente de 13 a 15 anos não é, por si, consentimento válido para atos da vida civil. **[verificar com advogado]** qual base usar e se cabe ciência do responsável, também à luz do art. 24 da Lei 15.211/2025 (ECA Digital).

**Medidas de segurança (art. 46):** senha com hash bcrypt, HTTPS com HSTS, cookie de sessão `Secure`/`HttpOnly`/`SameSite=Lax`, CSRF, rate limit em login, cadastro e recuperação de senha, isolamento por aluno (policy `TentativaPolicy` e consultas filtradas por `user_id`), backup local com rotação de 7 dias.
