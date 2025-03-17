# Qase TMS Codeception Reporter

Publish test results easily and efficiently.

## Installation

To install the latest version, run:

```sh
composer require qase/codeception-reporter 
```

Add the following lines to the `codeception.yml` file:

```yml
...
extensions:
        enabled:
        - Codeception\Extension\RunFailed
        - Qase\Codeception\Reporter
```

## Getting Started

The Codeception reporter can auto-generate test cases and suites based on your test data.
Test results of subsequent test runs will match the same test cases as long as their names and file paths don’t change.

You can also annotate tests with the IDs of existing test cases from Qase.io before executing them.
This is a more reliable way to bind automated tests to test cases, ensuring they persist when you rename, move, or
parameterize your tests.

For example:

```php
<?php

namespace Tests\Unit;

use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Suite;
use Tests\Support\UnitTester;

class FirstTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before()
    {
    }

    #[QaseId(1)]
    #[Field('description', 'My description')]
    public function testSomeFeature()
    {
        $this->assertTrue(true);
    }

    #[Suite("My suite")]
    public function testSomeFeatureFailed()
    {
        $this->assertTrue(false);
    }
}
```

To execute Codeception tests and report them to Qase.io, run the command:

```bash
QASE_MODE=testops ./vendor/bin/codecept run
```

or, if configured in a script:

```bash
composer test
```

A test run will be created and accessible at:

https://app.qase.io/run/QASE_PROJECT_CODE

## Configuration

Qase Codeception Reporter can be configured using:

1. A separate configuration file qase.config.json.
2. Environment variables (which override the values in the configuration file).

For a full list of configuration options, refer to
the [Configuration Reference](https://github.com/qase-tms/qase-php-commons/blob/main/README.md#configuration).

Example qase.config.json

```json
{
  "mode": "testops",
  "debug": true,
  "testops": {
    "api": {
      "token": "api_key"
    },
    "project": "project_code",
    "run": {
      "complete": true
    }
  }
}
```

## Requirements

We maintain the reporter on LTS versions of PHP.

- php >= 8.1
- codeception >= 5.2

