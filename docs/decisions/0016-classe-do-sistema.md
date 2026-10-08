# 0016. Classe do sistema no protocolo de monitoramento

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O RF04 diz que os tipos de evento disponíveis dependem da classe do sistema. Foram consideradas três leituras: faixa de risco do EU AI Act, domínio de aplicação e perfil de risco do sistema.

## Decisão

- A **faixa do EU AI Act** define a intensidade do monitoramento (periodicidade, ver 0017) e é a noção de "categoria" do critério de sucesso do OE3.
- O **perfil de risco** do sistema (subdomínios dos riscos cadastrados) define os eventos esperados: no registro de evento, esses subdomínios aparecem primeiro, e os demais continuam disponíveis, porque um evento fora do perfil pode revelar um risco não mapeado.
- O **domínio de aplicação** é um campo descritivo livre e opcional do sistema, sem efeito na classificação.

## Consequências

- O RF04 recebeu nova redação proposta (documento do C3, Seção 13).

## Implementação

Coluna `application_domain`; `MonitoringProtocol::expectedRiskSubdomainsBySystem()`; função pura `splitByRiskProfile()` em `resources/js/lib/risk-profile.ts`.

## Referências

- Documento do C3, Seção 7; Especificação, RF04.
