# Tense

[![Packagist](https://img.shields.io/packagist/v/uiii/tense.svg)](https://packagist.org/packages/uiii/tense)
[![travis-linux](http://badge.uiii.cz/?service=Travis&repo=uiii/tense&branch=master&label=Linux&params[os]=linux)](https://travis-ci.org/uiii/tense)
[![travis-osx](http://badge.uiii.cz/?service=Travis&repo=uiii/tense&branch=master&label=Mac&params[os]=osx)](https://travis-ci.org/uiii/tense)
[![AppVeyor](https://img.shields.io/appveyor/ci/uiii/tense.svg?label=Windows)](https://ci.appveyor.com/project/uiii/tense)

Tense (**T**est **EN**vironment **S**etup & **E**xecution) is a command-line tool to easily run tests agains
multiple versions of [ProcessWire CMF](https://processwire.com).

Are you building a module, or a template and you need to make sure it works in all supported ProcessWire versions?
Then `Tense` is exactly what you need. Write the tests in any testing framework, tell `Tense` which ProcessWire versions you are interested in and it will do the rest for you.

**See [example](https://github.com/uiii/tense/tree/master/example)**
or **see [usage](https://github.com/uiii/ProcessWire-FieldtypePDF#test-multiple-processwire-versions-automatically) in a real project**.

[![video](example/asciicast.gif)](https://asciinema.org/a/109559)

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Usage](#usage)
7. [Configuration](#configuration)
8. [Troubleshooting](#troubleshooting)

## Requirements

- PHP 5.6 or higher
- Composer (https://getcomposer.org)
- Git (https://git-scm.com)
- MySQL or MariaDB 5.0.15 or higher

### php.ini

`php.ini` used by `php` cli command must have enabled these extensions:

- curl
- gd2
- mbstring
- mysqli
- openssl
- pdo_mysql

## Installation

> Don't forget to setup all [requirements](#requirements) first.

Install globally:
```
composer global require uiii/tense
```

or install as a project dependency:
```
cd <your-project>
composer require --dev uiii/tense
```

## Usage

Go to your **project's root** directory.

1. create [config](#configuration) file `tense.yml`:

    ```
    tense init
    ```

2. run tests:

    ```
    tense run
    ```

> if you've installed `Tense` locally as project's dependecy, use `vendor/bin/tense` instead of `tense`

## Configuration

Tense uses [YAML](http://yaml.org/) configuration files. There are two types of config files:

- **project** (`tense.yml`): It should contain options directly related to the project's testing. This config is intended to be shared (VCS, ...).
- **local** (`tense.local.yml`): It should contain options related to the machine's environment setup (database connection, ...), overwrites options from project's config. This is **not** intended to be shared.

The **project**'s config can be created either manually or interactively by running the command:

```
tense init
```

The **local** config is automatically initialized on each `tense run` when missing.

### tmpDir
> *optional*, config: project, local

Path to a directory where files needed for testing
(e.g ProcessWire installation, ...) are stored.

> Path is relative to the config file's parent directory.

*Default is `.tense`*

### db
> **required**, config: local

Database connection parameters.

> They are used to create the database
for ProcessWire installation, so the user
must have the privileges to create a database.

> `db.name` is a name of the database.

*Example:*
```yaml
db:
    host: localhost
    port: 3306
    user: root
    pass: ""
    name: tense
```

### testTags
> **required**, config: project

List of ProcessWire tags/versions used for testing.

It doesn't have to be exact version number.
For each tag/version will be found latest matching
existing tag (e.g. `3.0` -> `3.0.42`).

Versions are tested in the specified order.

> Minimal supported version is `2.5`.

> Version `2.8` is not currently supported.

*Example:*
```yaml
testTags:
    - "2.5"
    - "2.6"
    - "2.7.1"
    - "3.0"
```

### copySources
> *optional*, config: project

Copy source files into ProcessWire's' installation before testing.

It is a list of objects with `destination` and `source` properties.

> Destination paths are relative to ProcessWire's installation root.

> Source paths are relative to the config file's parent directory.

Sources can either be a single string or an array of strings.
If the source is a string, file to file copy is used.
If the source is an array of strings, the destination
is considered to be a directory where all sources are copied into.

> If source item is a directory, it will be copied recursively.

*Example:*

```yaml
copySources:
    - destination: "site/templates/HomeTemplate.php"
    source: "src/templates/home.php"

    - destination: "site/modules/Module"
    source:
        - "Libs"
        - "Module.module"
```

Consider `tense.yml` is in project's root and `<project-root>/Libs` is a directory. In this example these files will be copied:
- `<project-root>/templates/home.php` to `<pw-path>/site/templates/HomeTemplate.php`
- `<project-root>/Libs/*` to `<pw-path>/site/modules/Module/Libs`
- `<project-root>/Module.module` to `<pw-path>/site/modules/Module/Module.module`

### beforeCmd
> *optional*, config: project, local

Command to execute before a test suite, but after a PW instance is installed and [sources](#copySources) are copied.

This is just a single command, if you need to run multiple commands, put them into an external script.

> Remember, the command should be platform independen, so using `.sh` or `.bat` files are not recommended.
> Best option is to use PHP as you can see in the [example](https://github.com/uiii/tense/tree/master/example),
> but you can use any other platform independent scripting language.

> Path to the ProcessWire installation will be in `PW_PATH` environment variable.

*Example:*
```yaml
beforeCmd: "composer install"
```

### testCmd
> **required**, config: project

Command to execute a test suite.

> Path to the ProcessWire installation will be in `PW_PATH` environment variable.

*Example:*
```yaml
testCmd: "vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Test.php"
```

### pause
> *optional*, config: local

After each test suite against a ProcessWire instance
but before a clean up test runner can pause and ask the user what to do.

This is useful e.g. to examine the installed ProcessWire instance.

*Possible values are:*
- `never` - never pause (*default*)
- `onFailure` - pause after failed test suite
- `always` - always pause after a test suite

## Troubleshooting

### cURL error: SSL certificate problem: unable to get local issuer certificate

If you got this error, you haven't properly configured PHP's `curl` extension. You can solve this e.g. by

1. download the [https://curl.haxx.se/ca/cacert.pem](https://curl.haxx.se/ca/cacert.pem) file
2. place it somewhere, e.g. in PHP's installation directory
3. edit `php.ini` file and set `curl.cainfo = <aboslute-path-to-cacert-file>`
