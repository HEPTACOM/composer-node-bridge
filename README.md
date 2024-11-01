# Composer Node Bridge

Have you ever wanted to use `node` in your PHP project, but had no guarantee, it available in the `$PATH` of your environment?
This composer plugin aims to solve that.

1. Install the plugin as a normal composer dependency in your project.
2. Allow the plugin to execute code in your project.
3. You will now have `vendor/bin/node` and `vendor/bin/npm`.

## Installation

```shell
composer require heptacom/composer-node-bridge
```

## How Does It Work?

This plugin uses the [`Node Version Manager`](https://github.com/nvm-sh/nvm) to install both Node and NPM into your `vendor` directory.
It will not be added to your `$PATH` environment variable.
NVM will not be available in your Shell.
It only affects your PHP project.

The goal is to allow PHP code to rely on the availability of `node` without putting additional dependencies on your environment.

## Limitations

Currently, only Node version 22 is supported.
