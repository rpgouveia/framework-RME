# Decisões pendentes

Itens ainda em aberto. **Não implementar sem decisão do grupo registrada nesta pasta.** Atualizado em 2026-11-05.

## Para reavaliar após o MVP

- Gravidade do evento adverso (0019, item 7).
- Estado do sistema ao longo do tempo (0019, item 10): faixa, situação operacional e retirada. O sistema é hoje um cadastro editável, sem histórico do próprio estado, o que reúne três lacunas. A mudança de faixa sobrescreve a anterior: só a reclassificação para a faixa inaceitável deixa rastro, pelas reversões, e uma mudança entre faixas operáveis não aparece no relatório, embora altere o intervalo de revisão dos vínculos novos (0017). Não há campo de situação operacional. E, na reconstrução da Air Canada, a retirada do sistema não encerrou os vínculos não verificados, o sistema retirado continuou nas contagens da tela de acompanhamento e não havia tipo de mudança para registrar a retirada. Ponto de partida: um registro somente de inclusão, como os demais históricos (0007, 0008, 0014), com valor anterior, novo valor, data, responsável e justificativa. Nenhum dos dois casos reconstruídos mudou de faixa.
- Alcance da reversão dirigida por subdomínio (0019, item 3, e 0021). Na reconstrução da Optum, ela alcançou mitigações que o evento não afetava; na da Air Canada, não alcançou o vínculo cuja falha causou o evento, porque a causa estava em subdomínio diferente do dano. Hoje, a reversão manual com motivo cobre o segundo caso.
- Prazo para reavaliação (0020, item 9).
- Análise da concordância do OE1 por par de anotadores (0025): com 9 a 11 mitigações por par, o alfa de cada par teria intervalos largos demais para comparar os pares.
- Vínculo suspenso e verificado (0028): hoje continua verificado e na revisão periódica, embora a mitigação esteja parada.
- Lacunas do catálogo encontradas nas reconstruções (0024): nenhuma mitigação trata do subdomínio 1.3 nem da supervisão humana em decisões apoiadas por escore (5.1), e nenhuma avisa o usuário de que ele interage com um sistema de IA.
- Controle de acesso por papel (RNF04): as oito policies autorizam qualquer usuário autenticado e marcam o ponto com `@todo`, à espera da definição de como um usuário corresponde a um responsável.

## Outros

- Revisão das traduções dos nomes das taxonomias de Saeri e do MIT pelo grupo, e tradução das descrições, hoje em inglês e exibidas no seletor de subdomínios.
