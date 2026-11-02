# Revisão de acessibilidade — Ponto de Contacto

## Referência e âmbito

Revisão dos fluxos principais da interface — lista, pesquisa e filtro de contactos, criação/edição, ações por contacto e leitura de cartões — com referência à [WCAG 2.2 do W3C](https://www.w3.org/TR/WCAG22/), nível AA nos critérios aplicáveis. A revisão não é uma certificação de conformidade integral da aplicação.

## Diagnóstico e alterações

| Diagnóstico antes das alterações | Critério WCAG | Correção e evidência depois |
|---|---|---|
| Texto auxiliar `#71817b` sobre branco: **4,09:1**; texto de exemplo `#a0aaa4` sobre branco: **2,39:1**. O rodapé `#84918b` sobre o fundo `#f5f6f2` tinha **3,02:1**. Estes textos são pequenos e não atingiam 4,5:1. | [1.4.3 Contraste (Mínimo)](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) — AA | Texto auxiliar passou a `#52635c`: **6,37:1** sobre branco e **5,87:1** sobre o fundo principal. Texto de exemplo passou a `#64756d`: **4,88:1** sobre branco. |
| O indicador de foco `#91c6b5` tinha **1,92:1** sobre branco. O limite dos campos `#dfe6df` tinha **1,27:1** sobre branco, tornando o foco e os controlos difíceis de distinguir. | [1.4.11 Contraste Não Textual](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html) — AA; [2.4.7 Foco Visível](https://www.w3.org/WAI/WCAG22/Understanding/focus-visible.html) — AA | O foco usa agora `#216b59` (**6,34:1** sobre branco). Os limites dos campos usam `#6f7f76` (**4,22:1** sobre branco). O estilo explícito abrange botões, ligações e campos de texto/seleção. |
| O seletor de fotografia tinha uma classe de ocultação visual, mas continuava na ordem de tabulação sem um indicador visível. Fechar o formulário também não devolvia o foco ao controlo que o abriu. | [2.4.7 Foco Visível](https://www.w3.org/WAI/WCAG22/Understanding/focus-visible.html) — AA; [2.4.3 Ordem do Foco](https://www.w3.org/WAI/WCAG22/Understanding/focus-order.html) — A | O seletor de ficheiro deixa de ser uma paragem de tabulação invisível; o botão visível **Escolher fotografia** continua a abri-lo. Ao cancelar/fechar, o foco regressa ao acionador; após guardar/remover, regressa ao título da lista. |
| Os campos tinham etiquetas e existia uma mensagem textual de erro, mas a sugestão de correção e a associação do erro ao campo eram inconsistentes; a validação de e-mail dependia da mensagem do navegador. | Melhoria alinhada com [3.3.3 Sugestão de Erro](https://www.w3.org/WAI/WCAG22/Understanding/error-suggestion.html) — AA e [4.1.2 Nome, Função, Valor](https://www.w3.org/WAI/WCAG22/Understanding/name-role-value.html) — A. A mensagem textual existente não permite classificar [3.3.1 Identificação de Erros](https://www.w3.org/WAI/WCAG22/Understanding/error-identification.html) — A como falha confirmada. | Os erros de nome e e-mail são apresentados junto ao campo, relacionados por `aria-describedby`, assinalados com `aria-invalid` e recebem foco. A mensagem do e-mail indica o formato esperado. A etiqueta visível explicita que o nome é obrigatório e o e-mail opcional. |
| Os botões de editar/remover mostravam apenas ícones; as ligações de telefone/e-mail não tinham sublinhado nem cor diferente do texto envolvente. Os botões já possuíam nomes acessíveis, por isso não se confirma uma falha de [2.4.6 Cabeçalhos e Etiquetas](https://www.w3.org/WAI/WCAG22/Understanding/headings-and-labels.html) — AA ou [1.4.1 Utilização da Cor](https://www.w3.org/WAI/WCAG22/Understanding/use-of-color.html) — A. | Melhoria de clareza visual, sem falha formal confirmada. | Os botões mostram agora **Editar** e **Eliminar**, mantendo nomes acessíveis específicos por contacto. Telefone e e-mail aparecem sublinhados e com cor de ligação de contraste suficiente. |
| Em ecrãs estreitos, as duas ações principais continuavam numa grelha de duas colunas, com risco de os rótulos dos botões excederem a largura disponível. | Risco relacionado com [1.4.10 Reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) — AA; a falha não foi confirmada visualmente nesta sessão. | Até 520 px, as ações principais e os botões do formulário passam a uma coluna; os campos e ações dos contactos também se reorganizam. O resultado visual a 320 px ainda precisa de validação manual num navegador. |

As etiquetas dos campos já existiam no início da revisão; por isso, não se assinala a [3.3.2 Etiquetas ou Instruções](https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html) como falha confirmada. O estado OCR já era anunciado com `role="status"` e foi preservado, em linha com [4.1.3 Mensagens de Estado](https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html).

**Falhas confirmadas no código inicial:** 1.4.3, 1.4.11 e 2.4.7. Os pontos de reflow, clareza das ações e associação de erros foram tratados como riscos/melhorias, não como violações formais confirmadas sem validação visual e manual.

O critério [2.1.1 Teclado](https://www.w3.org/WAI/WCAG22/Understanding/keyboard.html) também foi considerado. Os controlos e ações identificados são elementos nativos de formulário, botões ou ligações; a operação completa com teclado não foi, porém, validada manualmente nesta sessão e não é declarada como aprovada.

## Exemplos antes/depois

| Antes | Depois |
|---|---|
| `#a0aaa4` como cor do texto de exemplo: **2,39:1** sobre branco. | `#64756d`: **4,88:1** sobre branco; passa o mínimo AA de 4,5:1 para texto normal. |
| Foco `#91c6b5`: **1,92:1**; limite do campo `#dfe6df`: **1,27:1** sobre branco. | Foco `#216b59`: **6,34:1**; limite `#6f7f76`: **4,22:1**; ambos excedem 3:1 para indicadores não textuais. |
| `Nome *`, uma mensagem de erro geral e ações apresentadas apenas por ícones. | `Nome (obrigatório)`, erro junto ao campo e ligado programaticamente; ações com os rótulos visíveis **Editar** e **Eliminar**. |

As razões de contraste foram calculadas a partir das cores CSS e da fórmula de luminância relativa da WCAG, sem arredondar os limiares para decidir conformidade.

## Demonstração e verificações efetuadas

1. Verificação reproduzível da estrutura, associações dos campos, estado/foco, reflow declarado, preservação do OCR e rácios de contraste:

   ```sh
   /Applications/XAMPP/xamppfiles/bin/php tests/accessibility-smoke.php
   ```

   **Resultado:** 21 verificações passaram, 0 falhas. O ficheiro também passou `php -l`.
2. O bloco JavaScript inline foi analisado localmente pelo JavaScriptCore: sintaxe válida.
3. A aplicação local e a API responderam HTTP 200. Num servidor local com base SQLite isolada, foram executados: rejeição de e-mail inválido (422), criação (201), edição (200), pesquisa/filtro, remoção (200) e confirmação da lista vazia. Todos passaram.
4. A leitura de cartões foi verificada no código: continuam a existir a criação do trabalhador Tesseract em português e o reconhecimento do ficheiro; nenhuma etapa do OCR foi removida.

### Demonstração manual recomendada

Em `http://localhost/PHP/contractos/`, usar apenas o teclado: abrir **Adicionar contacto** com Enter, percorrer os campos e ações com Tab/Shift+Tab, submeter sem nome e com e-mail inválido, confirmar foco visível, mensagem associada e retorno do foco ao fechar. Redimensionar para **320 CSS px** (ou zoom de 400% a partir de 1280 px) e confirmar ausência de deslocamento horizontal e acesso a todas as ações. Por fim, usar **Ler cartão de visita** e **Escolher fotografia**, confirmar o estado anunciado e rever os campos reconhecidos antes de guardar.

## Limitações e estado

- Não foi possível executar a demonstração visual automatizada nesta sessão: o Safari WebDriver instalado não iniciou sem configuração/autenticação adicional. Assim, teclado real, leitor de ecrã, zoom/reflow visual a 320 px e o comportamento dos erros foram verificados por código e pelo smoke test, não por interação visual automatizada. Recomenda-se concluir esses testes em Safari/VoiceOver e num segundo navegador.
- A leitura OCR real e a câmara não foram exercitadas com uma imagem/dispositivo nesta sessão. O OCR continua a depender da biblioteca e dos dados de idioma servidos externamente; a câmara depende de permissão do navegador e de HTTPS ou `localhost`.
- Os testes cobrem os critérios diretamente relacionados com os problemas pedidos, não todos os critérios WCAG 2.2 AA nem todas as combinações de navegador, sistema operativo e tecnologia de apoio. Não se declara conformidade global.
