# 0029. Migrations e banco de desenvolvimento

- **Situação:** Aceita; implementada
- **Registrada em:** 2026-11-05

## Contexto

Desde setembro, as mudanças de esquema eram feitas editando a migration original da tabela, sem criar uma nova. Como o `migrate` não reaplica uma migration já executada, o banco de quem atualizava o repositório ficava diferente do esquema. Para resolver, o commit a148e18 (2026-10-08) trocou, no `docker/setup.sh`, o `migrate` por `migrate:fresh`. O setup roda a cada `docker compose up`, e o `fresh` apaga também os usuários, então a condição de semear só um banco vazio passou a valer sempre: todo `up` apagava o banco e semeava um portfólio novo, sorteado pelo seeder. Com isso, os dados lançados à mão, como as reconstruções de incidentes do OE5, só sobreviviam nos relatórios exportados; o portfólio de demonstração mudava a cada `up`; e o README afirmava o contrário, que os dados sobreviviam ao reinício.

## Decisão

1. **Migration publicada não se edita:** uma migration que já está na `main` não muda. Toda alteração de esquema é uma migration nova.
2. **O setup preserva o banco:** aplica as migrations pendentes com `migrate --force` e semeia só um banco sem usuários.
3. **Recomeçar do zero é um ato explícito:** `php artisan migrate:fresh --seed`, documentado no README. O comando apaga tudo o que foi lançado à mão, inclusive casos reconstruídos, cujos relatórios devem ser exportados antes.
4. **Aviso nos pull requests:** um PR que exija recomeçar o banco, por exemplo por mudar os seeders de forma que os dados existentes fiquem incoerentes, diz isso na descrição.

## Consequências

- Os dados lançados à mão sobrevivem ao `docker compose up` até que alguém recomece o banco.
- A troca não exige ação de ninguém: como cada `up` recriava o banco, todos os ambientes já estão no esquema atual, e o `migrate` não encontra nada pendente.
- O histórico das migrations passa a mostrar a evolução do esquema.
- Uma mudança nos seeders só chega a quem recomeçar o banco (item 4).

## Implementação

Implementada em 2026-11-05.

- **Item 2 (setup):** o `docker/setup.sh` voltou a aplicar só as migrations pendentes, com `php artisan migrate --force`, como o script `setup` do `composer.json`; a semeadura continua condicionada a um banco sem usuários. Verificado na stack em containers: depois de um responsável de controle criado à mão, o `setup` rodou de novo (`docker compose up -d --force-recreate setup`), respondeu "Nothing to migrate" sem semear, e o registro e os totais de sistemas, riscos e vínculos (13, 47 e 76) continuaram os mesmos.
- **Itens 1, 3 e 4 (convenção e documentação):** a seção _Database_ do README diz que toda mudança de esquema é uma migration nova, que uma migration já na `main` não se edita e que um PR que exija recomeçar o banco avisa na descrição; ao lado do `migrate:fresh --seed`, diz que o comando apaga também o que foi lançado à mão e que os relatórios devem ser exportados antes. O item "`setup` runs on every `up`" voltou a corresponder ao script e não mudou. O tutorial avisa que o comando apaga também o que o leitor tiver cadastrado.
- **Fora do escopo:** migrations, seeders e factories não mudaram. As migrations editadas até aqui continuam como estão, sem desmembrar as edições em migrations novas.

## Referências

- Commits 0463932 e 1118e24 (stack em containers e semeadura no primeiro `up`) e a148e18 (troca para `migrate:fresh`).
