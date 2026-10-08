# 0017. Periodicidade de revisão por faixa

- **Situação:** Aceita; parcialmente revista pela 0018
- **Registrada em:** 2026-10-08

## Contexto

O intervalo de revisão era de 180 dias para qualquer sistema. As fontes do projeto (EU AI Act, ISO/IEC 42001, NIST) exigem proporcionalidade, mas não fixam números.

## Decisão

- **Intervalos:** 90 dias para alto risco, 180 para risco limitado e 365 para risco mínimo, escolhidos pelo grupo e justificados pela proporcionalidade.
- **Faixa inaceitável:** o sistema pode ser cadastrado e ter riscos e vínculos planejados, mas nunca é considerado em operação; não há revisão periódica (data nula) e a interface exibe alerta destacado.
- **Ponto de partida:** a contagem começa na verificação: próxima revisão = data da verificação + intervalo da faixa. Vínculos declarados não têm data de revisão (0018), o que altera a R-7, antes aplicada na criação.
- **Mudança de faixa:** entre faixas operáveis, o novo intervalo vale a partir da próxima verificação, sem recalcular datas existentes; para a inaceitável, as verificações são revertidas com registro automático (depende da etapa 4a).
- **Parâmetros** ficam no arquivo versionado do protocolo, incluindo as janelas do painel; não há intervalo próprio por sistema.
- **Vínculo monitorável:** não cancelado, verificado, com data de revisão e de sistema fora da faixa inaceitável. É a regra única usada pelo escopo de revisão, pelo comando diário e pelo painel.

## Consequências

- Mudar um sistema para a faixa inaceitável não apaga datas; se ele voltar a uma faixa operável, as datas voltam a valer.
- A validação dos intervalos pelo painel de especialistas está pendente (OE6).

## Implementação

`database/data/protocols/c3-monitoring-protocol.json`; `MonitoringProtocol`; escopos `monitorable()` e `dueForReview()` do `Link`; `next_review_date` anulável.

- **Ponto de partida:** a data de revisão é definida na verificação, por `RecordStatusChange::verify()`, como data da verificação + intervalo da faixa atual do sistema (`MonitoringProtocol::nextReviewDate()`). O `CreateLink` não calcula mais a data: o vínculo nasce com `next_review_date` nula. `RecordStatusChange::revert()` volta a data a nula.
- **Vínculo monitorável:** o escopo `monitorable()` exige vínculo não cancelado, verificado, com data de revisão e de sistema fora da faixa inaceitável. É usado por `dueForReview()`, pelo comando `links:flag-due-for-review` e pelos indicadores de revisão do painel.
- **Faixa inaceitável:** a verificação é recusada (`RecordStatusChange::verificationProblem()`), então os vínculos desses sistemas ficam sem data. A interface mostra "Não se aplica" (`ReviewDate`) e o alerta (`UnacceptableTierAlert`).

## Referências

- Documento do C3, Seção 9; UC005 (R-7); decisão 0018.

## Revisão

Em 2026-10-08, a decisão 0018 transferiu o ponto de partida da contagem da criação do vínculo para a verificação, alterando a R-7, e passou a exigir vínculo verificado na regra de vínculo monitorável. O texto acima já reflete a versão revista.
