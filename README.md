# Préparation de la V2 de Kard
TODO:

- [ ] Faire le front
- [ ] Adapter les controller en mode api
- [ ] Refactorer le code lié aux jeux
- [ ] Refaire les tests
- [ ] Si possible: ajouter les replays
- [ ] Si replay fait: tests sur avec des replays

## Installation

```bash
git clone git@github.com:Fan2Shrek/kard.git
cd kard
make start
```

## Users

| Login  | Password | Role        |
| ------ | -------- | ----------- |
| admin  | aaa      | ROLE_ADMIN  |
| user   | aaa      | ROLE_USER   |
| banned | aaa      | ROLE_BANNED |

Ensuite allez sur [localhost:8000](http://localhost:8000) et vous êtes prêt à jouer !
