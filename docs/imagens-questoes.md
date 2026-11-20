# Imagens nas questões

Este documento é a fonte única de verdade entre o conteúdo, o backend (`ESCANOR`) e as páginas (`BULMA`).

## Formato no conteúdo
- Os arquivos ficam em `conteudo/materias/<materia>/imagens/<id-da-questao>[-<letra>].svg`.
- Na questão:
  ```json
  "imagem": { "arquivo": "fis-cinematica-021.svg", "alt": "Gráfico de velocidade (m/s) por tempo (s): reta que sobe de 0 a 20 m/s entre 0 e 4 s." }
  ```
  O campo é opcional e também pode aparecer em cada alternativa, para alternativas que são gráficos ou figuras.
- **O `alt` é obrigatório e completo:** descreve tudo o que é preciso para resolver a questão (valores, eixos, unidades, rótulos), pensando em quem usa leitor de tela.
- **Formato e tamanho:** só SVG autoral, desenhado por nós (licença CC-BY-SA, como o resto do conteúdo), com até 60 KB.
- **Proibido no SVG:** `<script>`, atributos `on*`, `<foreignObject>`, `href` externo (só `#id` interno), `@import`, fonte externa e imagem embutida em base64.
- **Desenho:**
  - estilo didático e limpo, no traço do design system;
  - contorno `#1b1030`;
  - texto em fonte do sistema, `font-family="Nunito, Arial, sans-serif"`, com tamanho mínimo equivalente a 14px na largura final;
  - fundo transparente ou creme `#fff6dc`;
  - `viewBox` definido, sem `width`/`height` fixos.
- **Exatidão:** a figura não pode contradizer o enunciado nem o gabarito, e os valores do desenho têm que ser os mesmos do texto.

## Importador (`gabaritu:importar-conteudo`)
1. **Validação:**
   - o arquivo existe;
   - a extensão é `.svg`;
   - o tamanho está dentro de 60 KB;
   - o `alt` não é vazio;
   - o SVG passa no sanitizador: parse XML e lista branca de elementos e atributos. Se falhar, o importador recusa a questão com uma mensagem clara.
2. **Cópia:** copia o arquivo para `public/conteudo/img/<sha256-12>.svg`, com o nome pelo hash do conteúdo, o que permite cache longo. Grava `imagem_url` e `imagem_alt` na questão ou na alternativa.
3. **Idempotência:** rodar duas vezes não duplica nada; arquivos órfãos são removidos.

## Props (`Questao.vue`, resultado, revisão)
- `questao.imagem: { url, alt, largura, altura } | null`, com largura e altura tiradas do `viewBox`.
- `alternativas[].imagem: { url, alt, largura, altura } | null`.

## Tela
- **A tela de questão continua sem scroll vertical** (360×640 a 1366×768).
  - **Telas largas:** a imagem fica ao lado do enunciado.
  - **Telas estreitas:** a imagem fica acima do enunciado, com altura máxima calculada para caber.
  - **Zoom:** tocar ou clicar na imagem abre um diálogo de zoom com o `alt` visível. Com Esc, o foco volta para a imagem.
- **Alternativas com imagem:** grade de miniaturas com a letra, em que cada miniatura ainda é um botão de resposta. O zoom fica num botão próprio, para não responder sem querer.
- **Renderização:** o SVG aparece sempre via `<img>`, nunca inline, para nenhum código rodar.

## Questões de origem INEP (ENEM oficial), em análise jurídica
O dono pediu em 20/11 para copiar as questões oficiais do ENEM e marcar a origem; o advogado vai decidir se ficam ou se são apagadas.
- **Fora do repo público:** o material extraído fica em `~/workspace/gabaritu-enem` (git local, sem remote) e entra pelo importador com `--fonte-externa=<caminho>`. Nada de INEP vai para `conteudo/` nem para o GitHub.
- **Metadados na questão:**
  - `origem: "inep"`;
  - `origem_ref: { ano, aplicacao: "regular"|"ppl"|"reaplicacao" (com sufixo opcional, ex.: "regular_impressa_2021-01" no ENEM 2020), dia, caderno, cor, numero, url_prova, url_gabarito }`;
  - `revisao_juridica: "pendente"|"liberada"|"remover"`.
  As autorais ficam com `origem: "autoral"`.
- **Imagens INEP:** aceita PNG e WebP (recorte da prova), além de SVG, com até 400 KB por arquivo. O `alt` continua obrigatório; quando a extração automática não souber descrever, recebe `alt_pendente: true` e a questão não é exibida.
- **Visibilidade:** `config('gabaritu.exibir_inep')` (env `GABARITU_EXIBIR_INEP`, padrão `false`). Desligado, as questões INEP não entram em questionário, revisão nem sorteio. Fazem exceção o comando de listagem e o relatório.
- **Apagar em lote:** `php artisan gabaritu:inep --remover` apaga as questões INEP, as respostas a elas e as imagens, e grava o que apagou num log de auditoria. Já `--relatorio` lista a contagem por ano, área e status.
- **Piloto em produção (decisão do dono):** com `GABARITU_EXIBIR_INEP=true`, a questão INEP **classificada** (`materia` + `assunto`) entra em questionários próprios (`tipo: inep`, título "ENEM · <n>", por assunto e misturando os anos: ordem por ano decrescente e número, lotes de 10, e a sobra com menos de 5 questões se junta ao lote anterior, até 14; o ano aparece na fonte de cada questão. A atribuição é determinística (mesma ordem, mesmo lote). Na reimportação, o questionário que ficar vazio é removido só se não tiver tentativas; com tentativas, fica no banco e some das telas por estar vazio), separados dos autorais; sem classificação, anulada, `alt_pendente` ou `revisao_juridica: remover`, nunca aparece. Questão de inglês só pode ser classificada em Inglês e a de espanhol em Espanhol. Sem etiqueta nos distratores, o erro usa a fala genérica `erro`; sem explicação, a resposta devolve `explicacao: null` e a tela mostra "Explicação em breve" com o gabarito destacado. O material externo chega ao servidor fora do git (ver `docs/operacao/producao.md`).
- **Atribuição:** onde aparecerem, mostram "Fonte: ENEM <ano>, INEP/MEC".
