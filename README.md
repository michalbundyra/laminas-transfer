# Laminas Transfer

[![Build Status](https://travis-ci.com/webimpress/laminas-transfer.svg?token=Zs3oDsZuNYyxCSQLbbkU&branch=master)](https://travis-ci.com/webimpress/laminas-transfer)
[![codecov](https://codecov.io/gh/webimpress/laminas-transfer/branch/master/graph/badge.svg?token=xoPBalZAZ5)](https://codecov.io/gh/webimpress/laminas-transfer)

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
- [ ] update all labels on github to have consistent across all packages
- [x] namespaced constants (like src/constant.php in expressive)
  (NOTICE: legacy constant values are changed - these are now aliases to new constants)
- [ ] DI keys - we need to alias legacy keys to new one
- [ ] Service Manager Plugin Managers - for example
  https://github.com/zendframework/zend-inputfilter/blob/master/src/InputFilterPluginManager.php#L46-L54
  (we need to move these to aliases to refer to new one).

### Additional tool/command to rewrite vendor packages

For testing purposes we would need to write another command
which will rewrite the whole vendor of the given project,
run composer dump-autoload. We need to rewrite all zend/zf
packages in vendor, but only the current version, so it should
be fairly quick.
We need to install bridge library and after this operation
the service should work as before.
