# 0015. Eventos adversos classificados pelos subdomínios de risco do MIT

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

Os eventos tinham sete tipos fixos, que misturavam sintoma técnico, tipo de dano e consequência jurídica, e não se relacionavam com os riscos cadastrados. Um evento adverso é um risco que se materializou.

## Decisão

- O evento é classificado em **um ou mais** subdomínios do MIT AI Risk Repository, a mesma taxonomia dos riscos e das mitigações.
- Os sete tipos anteriores foram **descartados**.
- **Não existe opção "outro"**: só é evento adverso a ocorrência associável a ao menos um subdomínio.

## Consequências

- Viabiliza a reavaliação dirigida por subdomínio (pendente).
- Na avaliação, a concordância entre anotadores com múltiplos rótulos exige medida adequada.

## Implementação

Relação entre evento e subdomínio; `AdverseEventValidationRules`; filtro por domínio na listagem.

## Referências

- Documento do C3, Seção 6; decisão 0012.
