O visual do Framework RME: o IBM Carbon aplicado aos componentes shadcn/ui que o app já usa. Um app de gestão denso, em português, para equipes de governança, risco e compliance de sistemas de IA. Reto, sóbrio e preciso; a cor só aparece onde carrega significado.

## Conteúdo

### Voz

- Escreva em **português do Brasil**, frases curtas, na terceira pessoa ou no imperativo: "Registre uma evidência antes de verificar o vínculo." Nada de "você" em excesso, nada de exclamação, nada de emoji.
- **Sentence case** em tudo: títulos, botões, rótulos e status ("Vence hoje", nunca "VENCE HOJE"; "O que o framework mantém", nunca "O QUE O FRAMEWORK MANTÉM").
- Botões são verbos no infinitivo com o objeto: "Registrar evidência", "Criar vínculo", "Cadastrar responsável". O botão de envio repete o título da tela.
- Toda ação bloqueada diz o motivo, logo abaixo: "Indisponível: registre uma evidência nova; a última reversão foi em 13/09/2026."
- Toda gravação permanente avisa antes: "Depois de registrado, o evento não pode ser editado nem excluído."

### Termos

A terminologia vem de `docs/glossary.md` do repositório (decisão 0028); quando houver dúvida, vale o glossário. Os erros mais comuns:

| Use | Nunca |
| --- | --- |
| Faixa de risco (Inaceitável, Alto, Limitado, Mínimo) | "Categoria" para o sistema (categoria é só a de Saeri, das mitigações) |
| Acompanhamento (a tela inicial) | "Painel", "Dashboard" |
| Progresso: Planejado, Em andamento, Implementado, Suspenso, Cancelado | "Em monitoramento", percentual de conclusão |
| Verificação: Declarada, Verificada | "Aprovada", "Validada", "Vencida" |
| Custo: Baixo, Médio, Alto, o **esforço** de implementação | Dinheiro, R$, OPEX, horas |
| Incerteza: grau de confiança numa avaliação (alta = confiança baixa) | Gravidade ou prioridade |
| Responsável: um papel (cargo e área) | Nome ou foto de pessoa |
| Quase-incidente | "Quase-acidente" |

### O que não inventar

Todo número, status, campo, ação e texto de uma tela precisa existir no app. Não existem: percentuais de conformidade ou maturidade, scores de risco ou de severidade, telemetria, valores em dinheiro, comitês, pareceres, aprovações, notificações, nomes de pessoas, hash ou checksum, upload de arquivos (evidência é só texto), códigos de registro como "VNC-2024-064", integrações, checagem de normas além de EU AI Act, MIT AI Risk Repository, Saeri et al. e ISO/IEC 22989. Uma proposta nova (filtro, busca, ação em linha) vem marcada como proposta.

## Fundamentos visuais

### Cor

- O tema claro é o **White** do Carbon; o escuro, o **Gray 100**. Os nomes dos tokens são os do shadcn em `resources/css/app.css` (`background`, `foreground`, `primary`, `muted`…), então trocar os valores lá aplica o sistema ao app inteiro.
- Página em `background`; cartões em `card` com linha `border`; faixas e painéis de informação em `muted`. A hierarquia vem da troca de superfície e da linha de 1px, nunca de sombra.
- Texto principal em `foreground`; secundário em `muted-foreground`; auxiliar e placeholder em `subtle-foreground`.
- **`primary` (IBM Blue `#0f62fe`) é escasso:** um botão primário por área, o item ativo da navegação, a aba ativa e a página atual. Links usam `link`. Nunca use azul em fundo de cartão, título ou rótulo.
- Status usam os tons de tag (`tag-gray`, `tag-blue`, `tag-green`, `tag-yellow`, `tag-red`), sempre com texto, e o mapeamento fixo de **LinkStatus** e **ClassificationTags**. Verde: verificado, implementado, em dia. Amarelo: pendente, suspenso, vence hoje, faixa Alto. Vermelho: incidente e erro. `destructive` cheio só para a faixa Inaceitável e botões destrutivos.
- `warning` é só preenchimento de ícone; texto de aviso usa `tag-yellow-fg`.
- Todos os pares de texto passam de 4.5:1 nos dois temas; linhas de campo, foco e ícones, de 3:1. As notas de cada token dizem a razão e o fundo.

### Tipografia

- **IBM Plex Sans** em tudo, carregada do Google Fonts (pesos 300, 400 e 600); **IBM Plex Mono** só para códigos (`code`: "4.2", identificadores de Saeri).
- Texto do app em `body` (14px/20px, `letter-spacing` 0.16px: não remova). Título de tela em `page-title` (28px, 400); seção em `section-title` (20px); cartão e modal em `card-title` (16px, 600); rótulos e ajuda em `label` e `helper` (12px, 0.32px).
- Números grandes do Acompanhamento em `number` (42px, **peso 300**). `display` (42px, 300) só na página inicial. Nunca negrito em título grande.

### Espaço e forma

- Escala de 4px: `spacing-03` (8px) entre rótulo e campo e entre botões; `spacing-05` (16px) de padding em botões, campos e células; `spacing-06` (24px) em cartões e entre campos; `spacing-07` (32px) entre seções.
- Alturas de controle: `control-sm` 32px em tabelas e filtros, `control-md` 40px padrão, `control-lg` 48px em linhas de tabela, envio de formulário e alvos de toque.
- **`radius` é 0** em botões, campos, cartões, tabelas, menus, modais e alertas. Só os rótulos de status são pílulas (`radius-pill`); o checkbox tem `radius-xs`.
- Sem sombras, gradientes nem bordas coloridas à esquerda em cartões. A única faixa colorida à esquerda é a do Alert.

### Campos e foco

- Campos com fundo `field` e linha inferior de 1px `input`; rótulo acima em `label`; ajuda e erro abaixo em `helper`.
- Foco em todo controle: contorno de 2px `ring`, por dentro do elemento (`outline-offset: -2px`).
- Erro: contorno `destructive-foreground` no campo e a mensagem em `helper--error`.

### Estados

- Hover em linhas, menus e botões ghost: `accent`. Primário: `primary-hover`; pressionado: `primary-active`.
- Desabilitado: fundo `secondary`, texto `subtle-foreground`, e o motivo escrito abaixo.
- Vazio: uma frase do que falta e o botão da ação que resolve.

### Movimento

Discreto: abrir e fechar de modais, menus e tooltips em até 150ms, sem quicar. Nada de animação decorativa.

## Iconografia

- **lucide-react**, o conjunto que o app já usa: traço de 2px, 16px no corpo do texto e em botões, 20px em alertas. A cor herda do texto.
- Ícones acompanham rótulo, nunca o substituem, exceto na barra lateral recolhida (com tooltip) e no botão de toggletip (com `aria-label`).
- Sem emoji.
- O símbolo do app está em `assets/Logos/`.

## Ajuda contextual

Dois padrões, e os textos vêm da tabela de tooltips do BRIEF (códigos G1 a G47), que deriva do glossário:

- **DefinitionTooltip** para termos curtos (Declarada, Aguardando reavaliação, Próxima revisão).
- **Toggletip** para explicações longas, listas e a rubrica de custo, ao lado do rótulo do campo ou do título da seção.

## Componentes

Cada componente corresponde a um arquivo de `resources/js/components/ui` (ou a um componente de domínio do app) e descreve o visual Carbon que ele deve ter. As prévias são renditions estáticas em HTML, com os tokens deste sistema: mostram o alvo, não o código atual.

**Adições intencionais**, a instalar pelo CLI do shadcn, sem criar do zero: `popover` (Toggletip), `radio-group` (RadioTile), `tabs` (Tabs) e `command` (busca de mitigação e de subdomínio, sem prévia ainda).

## Não sincronizado

- Componentes não foram compilados a partir do código: as prévias são estáticas e as props estão nos READMEs, não num `index.d.ts`.
- `command`, `sheet`, `dropdown-menu`, `navigation-menu`, `input-otp`, `toggle-group` e `sonner` ainda não têm prévia.
- As fontes vêm do Google Fonts; não há arquivos de fonte no sistema.
