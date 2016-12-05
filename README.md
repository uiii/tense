# PW-Test

Command-line tool to easily run tests agains multiple ProcessWire versions.

Having your tests (PHPUnit, CodeCeption, ...), you will just specify the command
which run them and list of ProcessWire versions. PW-Test will run you test suite
for each one of them.

See [example](https://github.com/uiii/pw-test/tree/master/example)

# Requirements

- PHP 5.4 or greater
- Composer (https://getcomposer.org)
- Git (https://git-scm.com)
- MySQL or MariaDB 5.0.15 or greater

## PHP.ini

`php.ini` used by `php` cli command must have enabled these extensions:

- curl
- gd2
- mbstring
- mysqli
- openssl
- pdo_mysql

# Installation

> Don't forget to setup all [requirements](#requirements) first.

Install globally:
```
composer global require uiii/pw-test
```

or install as a project dependency:
```
cd <your-project>
composer require --dev uiii/pw-test
```

# Usage

Go to your project's directory and [create config](#configuration) file `pw-test.json`.

Then if you installed `PW-Test` globally, run in your project's directory:
```
pw-test
```

If you've installed `PW-Test` as projects dependecy, run in your project's directory:
```
vendor/bin/pw-test
```

# Configuration

TODO