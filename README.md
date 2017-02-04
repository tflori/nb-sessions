# NbSessions

[![Build Status](https://travis-ci.org/tflori/nb-sessions.svg?branch=master)](https://travis-ci.org/tflori/nb-sessions)
[![Coverage Status](https://coveralls.io/repos/github/tflori/nb-sessions/badge.svg)](https://coveralls.io/github/tflori/nb-sessions)
[![Latest Stable Version](https://poser.pugx.org/tflori/nb-sessions/v/stable.svg)](https://packagist.org/packages/tflori/nb-sessions) 
[![Total Downloads](https://poser.pugx.org/tflori/nb-sessions/downloads.svg)](https://packagist.org/packages/tflori/nb-sessions) 
[![License](https://poser.pugx.org/tflori/nb-sessions/license.svg)](https://packagist.org/packages/tflori/nb-sessions)

A non-blocking session handler for PHP. This library is inspired by 
[duncan3dc/sessions](https://github.com/duncan3dc/sessions).

## Examples

### basic

```php
$session = new \NbSessions\SessionInstance('my-app');
$session->set('login', 'jdoe');
$login = $session->get('login');
```

### namespaces

To avoid key collisions you can use namespaces.

```php
$session->set('foo', 'bar');

$namespace = $session->getNamespace('my-module');
$namespace->set('foo', 'baz');

$session->get('foo'); // 'bar'
$namespace->get('foo'); // 'baz'
```

### static class

For easier access you can use the static class. But remember: it's more hard to test.

```php
$namespace = \NbSessions\Session::getNamespace('my-module');
\NbSessions\Session::get('foo');
```

## Setup

Install it via composer and use without configuration.

```bash
composer require tflori/nb-sessions
```

Read [the docs](https://tflori.github.io/nb-sessions) for more information.
