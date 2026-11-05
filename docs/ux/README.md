# UX e design

O material que orientou o redesign da interface no IBM Carbon sobre os
componentes shadcn/ui (PR #107). A interface implementada fica em
`resources/js` e `resources/css/app.css`; o que está aqui é a referência
de onde ela veio.

- **`claude-design/BRIEF.md`**: o brief do redesign, com a terminologia do
  `docs/glossary.md` e a lista do que não inventar.
- **`claude-design/screen-map.md`**: o mapa das telas, estados e modais do
  app.
- **`design-system/`**: o design system Framework RME. O `README.md` traz a
  voz, os termos, as cores, a tipografia e os componentes; o `tokens.json` e
  o `components/bundle.css` são os tokens e os componentes em CSS.
- **`prototype/`**: o protótipo de todas as telas, feito no Claude Design. Os
  arquivos `.dc.html` são o código-fonte de cada tela, e o `canvas.json`, a
  organização do canvas. Eles dependem do runtime do Claude Design, então não
  abrem direto no navegador: para ver o protótipo, use o artifact no
  claude.ai. As imagens (`/_blob/...`) só carregam lá.

Os screenshots de `claude-design/screenshots/`, citados no brief e no mapa,
mostram a interface anterior ao redesign e não são versionados, pelo peso.
