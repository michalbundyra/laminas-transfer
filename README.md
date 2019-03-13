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
$ bin/console transfer <org>/<name> <path>
```

It is recommended to use RamDisk as a path for better performance.

It will clone the repository and rewrite the entire history (all commits)
by replacing all references to ZendFramework with Laminas.

## TODO

- [ ] what to do with gh-pages branch ?
- [ ] generate new composer.lock ?
- [ ] in rewrite keep links to original issues/PRs (also in CHANGELOG.md)
- [ ] keep references to original issues/PRs (starting with # and number)
- [ ] rewrite all commits messages and tag contents
- [ ] create new repository using GitHub API
- [ ] push all branches and tags
- [ ] move all open issues/PRs
