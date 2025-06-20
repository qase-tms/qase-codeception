# Qase Codeception Reporter - Usage Guide

## Overview

Qase Codeception Reporter allows you to automatically publish test results to Qase TMS. The reporter can auto-generate test cases and test suites based on your test data, and also link existing tests to test cases in Qase.io through annotations.

## Annotations

### Core Annotations

#### QaseId
Links a test to an existing test case in Qase.io by its ID.

```php
use Qase\Codeception\Attributes\QaseId;

#[QaseId(123)]
public function testLogin(): void
{
    $this->assertTrue(true);
}
```

#### QaseIds
Links a test to multiple test cases in Qase.io. All IDs must be integers.

```php
use Qase\Codeception\Attributes\QaseIds;

#[QaseIds([1, 2, 3])]
public function testMultipleCases(): void
{
    $this->assertTrue(true);
}
```

#### Title
Sets a custom title for the test in the Qase report.

```php
use Qase\Codeception\Attributes\Title;

#[Title('User Authentication Test')]
public function testLogin(): void
{
    $this->assertTrue(true);
}
```

#### Suite
Defines test suites for grouping in Qase.io. Can be used multiple times for a single test.

```php
use Qase\Codeception\Attributes\Suite;

#[Suite('Authentication')]
#[Suite('Critical Features')]
public function testLogin(): void
{
    $this->assertTrue(true);
}
```

#### Field
Adds custom fields to the test case. Can be used multiple times for a single test.

```php
use Qase\Codeception\Attributes\Field;

#[Field('description', 'Test for user login functionality')]
#[Field('severity', 'high')]
#[Field('priority', 'critical')]
public function testLogin(): void
{
    $this->assertTrue(true);
}
```

#### Parameter
Adds parameters to the test case. Useful for parameterized tests.

```php
use Qase\Codeception\Attributes\Parameter;

#[Parameter('browser', 'chrome')]
#[Parameter('version', 'latest')]
public function testCrossBrowser(): void
{
    $this->assertTrue(true);
}
```

### Combined Usage

```php
<?php

namespace Tests\Unit;

use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Title;
use Qase\Codeception\Attributes\Suite;
use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\Parameter;
use Tests\Support\UnitTester;

class LoginTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(123)]
    #[Title('Successful Authentication Test')]
    #[Suite('Authentication')]
    #[Suite('Critical Features')]
    #[Field('description', 'Test verifies successful user login')]
    #[Field('severity', 'high')]
    #[Parameter('user_type', 'admin')]
    public function testSuccessfulLogin(): void
    {
        $this->assertTrue(true);
    }

    #[QaseIds([124, 125])]
    #[Title('Failed Authentication Test')]
    #[Suite('Authentication')]
    #[Field('description', 'Test verifies handling of invalid credentials')]
    #[Parameter('scenario', 'invalid_credentials')]
    public function testFailedLogin(): void
    {
        $this->assertTrue(false);
    }
}
```

## Test Types

### Unit Tests

```php
<?php

namespace Tests\Unit;

use Qase\Codeception\Attributes\QaseId;
use Tests\Support\UnitTester;

class ExampleTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(8)]
    public function testFail(): void
    {
        $this->assertTrue(false);
    }

    public function testSuccess(): void
    {
        $this->assertTrue(true);
    }

    public function testSkipped(): void
    {
        $this->markTestSkipped();
    }
}
```

### Acceptance Tests

```php
<?php

namespace Tests\Acceptance;

use Qase\Codeception\Attributes\QaseId;
use Tests\Support\AcceptanceTester;

class SignInCest
{
    #[QaseId(5)]
    public function loginPageWorks(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->see('Login');
    }
}
```

### Functional Tests

```php
<?php

namespace Tests\Functional;

use Qase\Codeception\Attributes\QaseId;
use Tests\Support\FunctionalTester;

class LoginCest
{
    #[QaseId(1)]
    public function tryLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->fillField('Email', 'user@example.com');
        $I->fillField('Password', 'password');
        $I->click('Login');
        $I->see('These credentials do not match our records.');
    }
}
```

## Running Tests

### Basic Run

```bash
QASE_MODE=testops ./vendor/bin/codecept run
```

### Run via Composer

Add to your `composer.json`:

```json
{
    "scripts": {
        "test": "QASE_MODE=testops ./vendor/bin/codecept run"
    }
}
```

Then run:

```bash
composer test
```

### Run Specific Test Types

```bash
QASE_MODE=testops ./vendor/bin/codecept run unit
QASE_MODE=testops ./vendor/bin/codecept run acceptance
QASE_MODE=testops ./vendor/bin/codecept run functional
```

## Examples

### Complete Test Class Example

```php
<?php

namespace Tests\Unit;

use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Title;
use Qase\Codeception\Attributes\Suite;
use Qase\Codeception\Attributes\Field;
use Qase\Codeception\Attributes\Parameter;
use Tests\Support\UnitTester;

class UserAuthenticationTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    #[QaseId(1001)]
    #[Title('Valid User Login')]
    #[Suite('Authentication')]
    #[Suite('User Management')]
    #[Field('description', 'Test user login with valid credentials')]
    #[Field('severity', 'high')]
    #[Field('priority', 'critical')]
    #[Parameter('user_role', 'admin')]
    #[Parameter('environment', 'production')]
    public function testValidUserLogin(): void
    {
        // Test implementation
        $this->assertTrue(true);
    }

    #[QaseId(1002)]
    #[Title('Invalid User Login')]
    #[Suite('Authentication')]
    #[Field('description', 'Test user login with invalid credentials')]
    #[Field('severity', 'medium')]
    #[Parameter('scenario', 'wrong_password')]
    public function testInvalidUserLogin(): void
    {
        // Test implementation
        $this->assertTrue(false);
    }

    #[QaseIds([1003, 1004])]
    #[Title('Password Reset Functionality')]
    #[Suite('Authentication')]
    #[Suite('Security')]
    #[Field('description', 'Test password reset functionality')]
    #[Field('severity', 'high')]
    public function testPasswordReset(): void
    {
        // Test implementation
        $this->markTestSkipped('Not implemented yet');
    }
}
```

### Cest Example

```php
<?php

namespace Tests\Acceptance;

use Qase\Codeception\Attributes\QaseId;
use Qase\Codeception\Attributes\Title;
use Qase\Codeception\Attributes\Suite;
use Qase\Codeception\Attributes\Field;
use Tests\Support\AcceptanceTester;

class UserManagementCest
{
    #[QaseId(2001)]
    #[Title('User Registration Process')]
    #[Suite('User Management')]
    #[Suite('Registration')]
    #[Field('description', 'Complete user registration flow')]
    #[Field('severity', 'high')]
    public function testUserRegistration(AcceptanceTester $I): void
    {
        $I->amOnPage('/register');
        $I->fillField('name', 'John Doe');
        $I->fillField('email', 'john@example.com');
        $I->fillField('password', 'password123');
        $I->click('Register');
        $I->see('Registration successful');
    }

    #[QaseId(2002)]
    #[Title('User Profile Update')]
    #[Suite('User Management')]
    #[Field('description', 'Test user profile information update')]
    public function testUserProfileUpdate(AcceptanceTester $I): void
    {
        $I->amOnPage('/profile');
        $I->fillField('name', 'Jane Doe');
        $I->click('Update Profile');
        $I->see('Profile updated successfully');
    }
}
```
