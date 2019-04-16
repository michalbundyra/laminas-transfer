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

- [x] remove gh-branch, it will be recreated later (by travis)
- [ ] generate composer.lock at the very end, when all packages are rewritten and pushed
- [ ] keep original links to issues/PRs (also in CHANGELOG.md)
- [ ] keep references to original issues/PRs (starting with # and number)
- [ ] rewrite all commits messages and tag contents
- [ ] create new repository using GitHub API
- [ ] update repository description/website, keywords (as in composer?)
- [ ] push all branches and tags
- [ ] decide what to do with open issues (if we are going to move them or not)
- [ ] we are not going to move currently open PRs, so we need decide what to do with them before transfer
- [x] consistent release tags (without `release-`/`v` prefix, `rc`/`alpha`/`beta` - without dash and dot before number)
- [ ] register on packagist
- [ ] enable coveralls/Travis CI
- [ ] create labels on github
- [ ] rewrite assets in apigility
- [ ] issues with configuration in apigility - zf-apiglity-... keys
- [x] component-installer must support "zf" and "laminas"
- [ ] new PR/issues templates (remove old one and add new one into `.github` directory)
