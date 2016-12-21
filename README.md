# PW-Test

> Project is still in **early development** and may change a lot.

Command-line tool to easily run tests agains multiple versions of [ProcessWire CMF](https://processwire.com).

Are you building a module, or a template and you need to make sure it works in all supported ProcessWire versions?
Then `PW-Test` is exactly what you need. Write the tests (PHPUnit, CodeCeption, ...).
Tell `PW-Test` which ProcessWire versions you are interested in and it will do the rest for you.

> Tested on **Windows** and **Linux**

**See [example](https://github.com/uiii/pw-test/tree/master/example)**
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
composer global require uiii/pw-test:dev-master
```

or install as a project dependency:
```
cd <your-project>
composer require --dev uiii/pw-test:dev-master
```

## Usage

Go to your **project's root** directory.

[Create config](#configuration) file `pw-test.json`,

then if you installed `PW-Test` globally:
```
pw-test
```

or if you've installed `PW-Test` as projects dependecy:
```
vendor/bin/pw-test
```

## Configuration

Copy example configuration [`conf/pw-test.json`](conf/pw-test.json) to your project's root directory and set options according to your needs.

### tmpDir
Path to a directory where are stored files needed for testing
(e.g ProcessWire installation, ...).

> Path is relative to the config file's parent directory.

*Default is `.pw-test`*

### db
Database connection parameters.

> They are used to create the database
for ProcessWire installation, so the user
must have the privileges to create a database.

> `db.name` is a name of the database.

*Example:*
```json
"db": {
	"host": "localhost",
	"port": 3306,
	"user": "root",
	"pass": "",
	"name": "pw_test"
}
```

### testTags
List of ProcessWire tags/versions used for testing.

It doesn't have to be exact version number.
For each tag/version will be found latest matching
existing tag (e.g. `3.0` -> `3.0.42`).

Versions are tested in the specified order.

> Minimal supported version is `2.5`.

> Version `2.8` currently not supported.

*Example:*
```json
"testTags": ["2.5", "2.6", "2.7.1", "3.0"]
```

### copySources
Copy tested project source files to specified
destinations in ProcessWire installation.

> Destination paths are relative to ProcessWire
installation root.

> Source paths are relative to the config file's parent directory.

Sources can be either array or a single string.
If array of sources is specified, the destination
is considered a directory where all sources are copied.
If single string source is specified, one to one copy is used.

> If source item is a directory, it will be copied recursively.

*Example:*

```json
"copySources": {
	"site/templates/HomeTemplate.php": "templates/home.php",
	"site/modules/Module": [
		"Libs",
		"Module.module"
	]
}
```

Consider `pw-test.json` is in project's root and `<project-root>/Libs` is a directory. In this example these files will be copied:
- `<project-root>/templates/home.php` to `<pw-path>/site/templates/HomeTemplate.php`
- `<project-root>/Libs/*` to `<pw-path>/site/modules/Module/Libs`
- `<project-root>/Module.module` to `<pw-path>/site/modules/Module/Module.module`

### testCmd
Command to execute a test suite.

> Path to the ProcessWire installation will be in `PW_PATH` environment variable.

*Example:*
```json
"testCmd": "vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Test.php"
```

### waitAfterTests
Test runner can wait and ask the user what to do
after each test suite against a ProcessWire instance is completed.

*Possible values are:*
- `never` - never wait (*default*)
- `onFailure` - wait after failed test suite
- `always` - always wait
