# NbSessions
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
