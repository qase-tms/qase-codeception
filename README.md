> # Qase TMS Codeception reporter
>
> Publish results simple and easy.

## How to integrate

```bash
composer require qase/codeception-reporter
```

## Example of usage

The Codeception reporter has the ability to auto-generate test cases
and suites from your test data.

But if necessary, you can independently register the ID of already
existing test cases from TMS before the executing tests. For example:

```php
/**
 * @qaseId 3
 */
public function testCanBeUsedAsString(): void
{
    $this->assertEquals(
        'user@example.com',
        Email::fromString('user@example.com')
    );
}
```

You should also have an active item in the project settings at

```
https://app.qase.io/project/QASE_PROJECT_CODE/settings/options
```

options in the `Test Runs` block:

```
Auto create test cases
```
and
```
Allow submitting results in bulk
```

To run tests and create a test run, execute the command:

```bash
$ ./vendor/bin/codecept run
```

A test run will be performed and available at:
```
https://app.qase.io/run/QASE_PROJECT_CODE
```

If test fails, a defect will be automatically created

## Configuration

Add to your `codeception.yml` extension:

```xml
extensions:
        enabled: [Qase\Codeception\Reporter]
```

Reporter options (* - required):

- `QASE_REPORT` - toggles sending reports to Qase.io, set `1` to enable
- *`QASE_API_TOKEN` - access token, you can find more information [here][auth].
- *`QASE_PROJECT_CODE` - code of your project (can be extracted from main page of your project,
  as example, for `https://app.qase.io/project/DEMO` -> `DEMO` is project code here.
- *`QASE_API_BASE_URL` - URL endpoint API from Qase TMS, default is `https://api.qase.io/v1`.
- `QASE_RUN_ID` - allows you to use an existing test run instead of creating new.
- `QASE_RUN_COMPLETE` - performs the "complete" function after passing the test run.
- `QASE_ENVIRONMENT_ID` - environment ID from Qase TMS
- `QASE_LOGGING` - toggles debug logging, set `1` to enable
