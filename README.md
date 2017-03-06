# Tense

> Project is still in **early development** and may change a lot.

Tense (**T**est **EN**vironment **S**etup & **E**xecution) is a command-line tool to easily run tests agains
multiple versions of [ProcessWire CMF](https://processwire.com).

Are you building a module, or a template and you need to make sure it works in all supported ProcessWire versions?
Then `Tense` is exactly what you need. Write the tests in any testing framework, tell `Tense` which ProcessWire versions you are interested in and it will do the rest for you.

> Tested on **Windows** and **Linux**

**See [example](https://github.com/uiii/tense/tree/master/example)**
or **see [usage](https://github.com/uiii/ProcessWire-FieldtypePDF#test-multiple-processwire-versions-automatically) in a real project**.

[![video](example/asciicast.gif)](https://asciinema.org/a/95368)

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Usage](#usage)
7. [Configuration](#configuration)

## Requirements

- PHP 5.6 or greater
- Composer (https://getcomposer.org)
- Git (https://git-scm.com)
- MySQL or MariaDB 5.0.15 or greater

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
composer global require uiii/tense:dev-master
```

or install as a project dependency:
```
cd <your-project>
composer require --dev uiii/tense:dev-master
```

## Usage

Go to your **project's root** directory.

[Create config](#configuration) file `tense.yml`,

then if you installed `Tense` globally:
```
tense
```

or if you've installed `Tense` as project's dependecy:
```
vendor/bin/tense
```

## Configuration

Tense uses [YAML](http://yaml.org/) configuration files. Copy [`tense.yml`](tense.yml) to your project's root directory and set options according to your needs.

### tmpDir
Path to a directory where files needed for testing
(e.g ProcessWire installation, ...) are stored.

> Path is relative to the config file's parent directory.

*Default is `.tense`*

### db
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

### testCmd
Command to execute a test suite.

> Path to the ProcessWire installation will be in `PW_PATH` environment variable.

*Example:*
```yaml
testCmd: "vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Test.php"
```

### waitAfterTests
Test runner can wait and ask the user what to do
after each test suite against a ProcessWire instance is completed.

*Possible values are:*
- `never` - never wait (*default*)
- `onFailure` - wait after failed test suite
- `always` - always wait

## Troubleshooting

### cURL error: SSL certificate problem: unable to get local issuer certificate
```
Error loading sha `master`, curl request failed (status code: 0, url: https://raw.githubusercontent.com/ryancramerdesign/ProcessWire/master/wire/core/ProcessWire.php).
cURL error: SSL certificate problem: unable to get local issuer certificate
```

If you got this error, you haven't properly configured PHP's `curl` extension. You can solve this e.g. by

1. download the [https://curl.haxx.se/ca/cacert.pem](https://curl.haxx.se/ca/cacert.pem) file
2. place it somewhere, e.g. in PHP's installation directory
3. edit `php.ini` file and set `curl.cainfo = <aboslute-path-to-cacert-file>`
