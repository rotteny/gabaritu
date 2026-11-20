# Retenção e exclusão de dados

O que existe hoje no código e no servidor, e o que falta decidir. Itens **[decidir]** dependem do dono; **[verificar com advogado]** não são conclusão jurídica.

## Exclusão pelo titular

Responsável: `DELETE /responsavel/alunos/{aluno}` (apaga o aluno, com a senha do responsável) e `DELETE /responsavel/conta` (apaga a própria conta; bloqueado enquanto for o único responsável de um aluno menor). Os vínculos e convites caem em cascata.

`DELETE /conta` (`ContaController::destroy`), com senha:

- apaga o usuário; as tabelas do aluno têm `cascadeOnDelete` (tentativas, itens, respostas, desempenho, histórico, revisão, feedback de fala, passkeys);
- apaga as sessões e o token de recuperação de senha do e-mail;
- é exclusão física, não soft delete.

O que **não** é apagado na hora:

- **Backups** (`/data/backups/gabaritu/`, pg_dump diário): o dado some sozinho em até **7 dias**, com a rotação. A página `/privacidade` diz "tudo some" sem citar esse prazo. **[decidir]** se acrescenta a frase "cópias de segurança são apagadas em até 7 dias".
- **Log de acesso do nginx de borda** (IP, URL, navegador): segue a rotação do servidor. **[decidir]** o prazo e configurar o logrotate.
- **Log da aplicação**: vai para o stderr do container (`LOG_CHANNEL=stderr`, `LOG_LEVEL=warning`); a rotação é a do driver de log do Docker. **[decidir]** `max-size`/`max-file` no compose.

## Prazos

| Dado | Prazo hoje | Proposta |
| --- | --- | --- |
| Conta e dados de estudo | até o titular apagar | **[decidir]** apagar conta sem acesso há 24 meses, com aviso por e-mail antes (exige o e-mail transacional) |
| Sessões no banco | expiram em 120 min; a linha sai na limpeza do coletor de sessões | manter |
| Token de recuperação de senha | expira em 60 min | manter |
| Aluno `aguardando_responsavel` | 30 dias; aviso por e-mail 3 dias antes; só é apagado depois do aviso (`gabaritu:limpar-pendentes`, diário) | manter; exige scheduler em produção (`docs/operacao/producao.md`) |
| Convites de responsável e pedidos de vínculo | 7 dias; vencidos apagados pelo mesmo comando | manter |
| Conta de responsável sem aluno ativo | 30 dias, depois apagada pelo mesmo comando | manter |
| Vínculo e registro do aceite (data e hora, IP, versões, declarações) | enquanto o vínculo existir; some junto com o aluno ou com o responsável | **[verificar com advogado]** se a prova do aceite deve sobreviver ao fim do vínculo e por quanto tempo |
| Backups | 7 dias | manter |
| Log de acesso (nginx) | rotação do servidor (não documentada) | **[decidir]** 30 dias, ou 6 meses se a guarda do Marco Civil for aplicável **[verificar com advogado]** |

## Marco Civil da Internet, art. 15

O dever de guardar registros de acesso a aplicações por 6 meses vale para o provedor "constituído na forma de pessoa jurídica" que exerce a atividade "de forma organizada, profissionalmente e com fins econômicos". Projeto de pessoa física, sem CNPJ e sem fins econômicos, em princípio fica fora. **[verificar com advogado]** Prudente: manter o log do nginx com prazo curto e definido, e não guardar além do que a política declara.

## Prova do aceite (`aceites_responsavel`)
- Guarda, por convite ou pedido usado: data e hora do aceite, IP, versão dos Termos e da Privacidade, quem aprovou e quando o aluno aceitou.
- Fica fora de `convites_responsavel` de propósito: o convite vencido é apagado pela limpeza e a prova continua.
- Apaga junto com a conta do aluno. Prazo de guarda após a exclusão: verificar com advogado.
