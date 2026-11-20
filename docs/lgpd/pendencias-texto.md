# Pendências dos textos legais (`/termos` e `/privacidade`)

O que ficou **fora** do texto público por ser duvidoso, ou que o texto afirma mas precisa de conferência. Nada aqui é conclusão jurídica: **verificar com advogado**.

## Ficou fora do texto

- **Base legal formal** de cada tratamento (LGPD art. 7º): a política diz só "para entregar o serviço, registrar a autorização e manter o site seguro" e cita o melhor interesse do adolescente (art. 14). Qual hipótese do art. 7º usar para o aluno de 13 a 17 anos, e para o responsável, segue em aberto (ver nota em `registro-de-operacoes.md`).
- **Prazo dos logs de acesso** do servidor (IP, URL, navegador): o texto diz só que existem. Falta definir o prazo e, se for o caso, a guarda do Marco Civil, art. 15 (ver `retencao.md`).
- **Prova da condição de responsável legal**: hoje é uma declaração em checkbox. Se a ANPD ou um advogado exigir verificação mais forte da idade ou da guarda, entra como nova versão dos Termos.
- **Foro, lei aplicável e responsabilidade civil**: os Termos só dizem que o projeto é gratuito, oferecido como está e sem garantia de aprovação. Cláusulas além disso ficaram de fora.
- **Idade em que o aluno encerra o acompanhamento** (18 anos): regra 9 do doc, fora do MVP; o texto não promete.
- **Encarregado / canal do titular** e prazo de resposta aos pedidos: o texto só indica o e-mail de contato, sem prazo.
- **Responsável pedir dados do aluno** (direitos do titular em nome do menor): o texto diz que ele pode pedir; conferir o alcance.
- **Contas inativas** (apagar após 24 meses): ainda é proposta em `retencao.md`, não consta no texto.

## Conferir no código antes de publicar

- **Versão e data**: o front mostra `VERSAO_TEXTOS = '2026-11-20'` e "20 de novembro de 2026" (`resources/js/lib/responsavel.ts`). Subir `gabaritu.privacidade_versao` no backend para o mesmo valor, e guardar o aceite dos Termos com essa versão.
- **Retenção de 30 dias, aviso 3 dias antes, convites vencidos e conta de responsável sem aluno**: o texto repete o doc `responsaveis.md`; vale só se o comando `gabaritu:limpar-pendentes` fizer isso.
- **Backup em até 7 dias**: conforme `retencao.md`.
