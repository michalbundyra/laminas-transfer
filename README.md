# Laminas Transfer

Library to transfer ZF repositories to Laminas Project.

## Usage

Use the following command to calculate the order of repositories to transfer:

```console
$ bin/console dependencies <org> <github token>
```

Generate github token on: https://github.com/settings/tokens

Then to transfer the repository use the command:

```console
$ bin/console transfer <org>/<name>
```

It will create clone the repository and replace all references to
ZendFramework with Laminas.

## TODO

- [ ] update Travis CI configuration (change references to ZF)
- [ ] create new repository
- [ ] push all previous tags
- [ ] create new release in the new namespace
