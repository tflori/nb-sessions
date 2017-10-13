<?php

require __DIR__ . '/../../vendor/autoload.php';

// reset session settings
ini_set('session.use_cookies', 1);
mkdir('/tmp/nbsess', 0777, true);
ini_set('session.save_path', '/tmp/nbsess');
session_set_cookie_params(0, null, null, false, false);

if (@$_GET['use_cookies'] === 'false') {
    ini_set('session.use_cookies', 0);
}

foreach (['path', 'domain', 'lifetime', 'secure', 'httponly'] as $parameter) {
    if (!empty($_GET['session_cookie_' . $parameter])) {
        setCookieParams([$parameter => $_GET['session_cookie_' . $parameter]]);
    }
}

$session = new \NbSessions\SessionInstance('nbsession');

if (@$_GET['write'] !== 'false') {
    $session->set('foo', 'bar');
} else {
    $session->get('foo');
}

if (@$_GET['destroy'] === 'true') {
    $session->destroy();
}

if (@$_GET['reuse'] === 'true') {
    $session->set('foo', 'bar');
}

header('Content-Type: application/json');
echo json_encode(session_id());

function setCookieParams($params)
{
    $params = array_merge(session_get_cookie_params(), $params);
    session_set_cookie_params(
        $params['lifetime'],
        $params['path'],
        $params['domain'],
        $params['secure'] === 'true' || $params['secure'] === true ? true : false,
        $params['httponly'] === 'true' || $params['httponly'] === true ? true : false
    );
}
