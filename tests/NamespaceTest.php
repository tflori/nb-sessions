<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;
use NbSessions\SessionNamespace;

class NamespaceTest extends TestCase
{
    public function testReturnsANamespace()
    {
        $session = new SessionInstance('session');

        $namespace = $session->getNamespace('games');

        self::assertInstanceOf(SessionNamespace::class, $namespace);
    }

    public function testReturnsTheSameObjectOnSecondCall()
    {
        $session = new SessionInstance('session');
        $namespace = $session->getNamespace('games');

        $result = $session->getNamespace('games');

        self::assertSame($namespace, $result);
    }

    public function testDoesNotOverwriteOthers()
    {
        $session = new SessionInstance('session');
        $nsGames = $session->getNamespace('games');
        $nsWork = $session->getNamespace('work');

        $session->set('foo', 'root');
        $nsGames->set('foo', 'games');
        $nsWork->set('foo', 'work');

        self::assertSame('root', $session->get('foo'));
        self::assertSame('games', $nsGames->get('foo'));
        self::assertSame('work', $nsWork->get('foo'));
    }

    public function testNamespaceCanReceiveArrayOfData()
    {
        $session = new SessionInstance('session');
        $namespace = $session->getNamespace('games');

        $namespace->set([
            'foo' => 'bar',
            'name' => 'John Doe'
        ]);

        self::assertSame('bar', $namespace->get('foo'));
        self::assertSame('John Doe', $namespace->get('name'));
    }

    public function testDoesNotStoreUnderGuessableName()
    {
        $key = '';
        $mock = \Mockery::mock(SessionInstance::class)->makePartial();
        $mock->shouldReceive('set')->andReturnUsing(function ($data) use (&$key) {
            $key = array_keys($data)[0];
        });

        $namespace = $mock->getNamespace('games');
        $namespace->set('foo', 'bar');

        self::assertNotContains('games', $key);
    }
}
