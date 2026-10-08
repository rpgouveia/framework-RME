# Decisões pendentes

Itens ainda em aberto. **Não implementar sem decisão do grupo registrada nesta pasta.** Atualizado em 2026-10-08.

## Etapa 4b: gatilhos

| Item                              | Proposta em discussão                                                                                                                          |
| --------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| Reavaliação dirigida              | O evento reverte só os vínculos cujo risco está em algum subdomínio do evento; evento fora do perfil aparece no painel como risco não mapeado. |
| Gravidade do evento               | Sem gravidade por ora; se necessária, qualitativa com incerteza explícita.                                                                     |
| Quase-incidentes                  | Disparam reavaliação como os incidentes.                                                                                                       |
| Natureza do evento                | Campo incidente ou quase-incidente.                                                                                                            |
| Evento interceptado por mitigação | Registrar como quase-incidente, com vínculo interceptador opcional.                                                                            |
| Data de detecção                  | Campo opcional.                                                                                                                                |
| Agendador                         | Serviço de agendamento no `compose.yml`.                                                                                                       |
| Reversões automáticas             | Por vencimento da revisão, por evento adverso e por reclassificação do sistema para a faixa inaceitável, com a origem registrada (0018).       |
| Registro de eventos               | Restrito a sistemas em operação, isto é, com ao menos um vínculo verificado (Tela 4).                                                          |

## Etapas 4c e 4d

| Item                                                 | Proposta em discussão                                                       |
| ---------------------------------------------------- | --------------------------------------------------------------------------- |
| Desfechos da reavaliação                             | Manter, ajustar ou substituir (a ida à Tela 2 corresponde só a substituir). |
| Fase de origem da causa                              | Registrada na reavaliação, não no evento.                                   |
| Gatilhos de mudança de dados e nova versão do modelo | Modelar como mudanças do sistema ou declarar como limitação.                |

## Definição de evento adverso (C3)

Ainda em amadurecimento: dano a quem, o que conta como operação e o posicionamento da definição em relação à literatura. O texto da definição **não** deve ser incluído no arquivo do protocolo até ser decidido.

## Outros

- Revisão das traduções das taxonomias de Saeri e do MIT.
- Idioma da barra lateral (as telas estão em português).
- Conteúdo real do catálogo (C2) e critério de curadoria.
- Planejamento do painel de especialistas (OE6).
