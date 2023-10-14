<?php

namespace NbSessions\Test;

use Mockery\Mock;
use NbSessions\SessionInstance;
use NbSessions\SessionNamespace;

class NamespaceTest extends TestCase
{
    /** @var SessionInstance */
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new SessionInstance([], $this->phpWrapper);
    }

    /** @test */
    public function returnsANamespace()
    {
        $session = $this->session;

        $namespace = $session->getNamespace('games');

        self::assertInstanceOf(SessionNamespace::class, $namespace);
    }

    /** @test */
    public function returnsTheSameObjectOnSecondCall()
    {
        $session = $this->session;
        $namespace = $session->getNamespace('games');

        $result = $session->getNamespace('games');

        self::assertSame($namespace, $result);
    }

    /** @test */
    public function doesNotOverwriteOthers()
    {
        $session = $this->session;
        $nsGames = $session->getNamespace('games');
        $nsWork = $session->getNamespace('work');

        $session->set('foo', 'root');
        $nsGames->set('foo', 'games');
        $nsWork->set('foo', 'work');

        self::assertSame('root', $session->get('foo'));
        self::assertSame('games', $nsGames->get('foo'));
        self::assertSame('work', $nsWork->get('foo'));
    }

    /** @test */
    public function namespaceCanReceiveArrayOfData()
    {
        $session = $this->session;
        $namespace = $session->getNamespace('games');

        $namespace->set([
            'foo' => 'bar',
            'name' => 'John Doe'
        ]);

        self::assertSame('bar', $namespace->get('foo'));
        self::assertSame('John Doe', $namespace->get('name'));
    }

    /** @test */
    public function doesNotStoreUnderGuessableName()
    {
        $key = '';
        /** @var Mock|SessionInstance $mock */
        $mock = \Mockery::mock(SessionInstance::class)->makePartial();
        $mock->shouldReceive('set')->andReturnUsing(function ($data) use (&$key, $mock) {
            $key = array_keys($data)[0];
            return $mock;
        });

        $namespace = $mock->getNamespace('games');
        $namespace->set('foo', 'bar');

        self::assertStringNotContainsString('games', $key);
    }
}
