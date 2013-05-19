<?php

namespace tests\units\Symfony\Component\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher as tEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \atoum;

class EventDispatcher extends atoum
{
    /* Some pseudo events */
    const preFoo = 'pre.foo';
    const postFoo = 'post.foo';
    const preBar = 'pre.bar';
    const postBar = 'post.bar';

    private $dispatcher;

    private $listener;

    public function beforeTestMethod($method)
    {
        $this->dispatcher = new tEventDispatcher();
        $this->listener = new TestEventListener();
    }

    public function afterTestMethod($method)
    {
        $this->dispatcher = null;
        $this->listener = null;
    }

    public function testInitialState()
    {
        $this
            ->array($this->dispatcher->getListeners())
                ->isEmpty()
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isFalse()
            ->boolean($this->dispatcher->hasListeners(self::postFoo))
                ->isFalse();
    }

    public function testAddListener()
    {
        $this->dispatcher->addListener('pre.foo', array($this->listener, 'preFoo'));
        $this->dispatcher->addListener('post.foo', array($this->listener, 'postFoo'));

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
            ->boolean($this->dispatcher->hasListeners(self::postFoo))
                ->isTrue()
            ->array($this->dispatcher->getListeners(self::preFoo))
                ->hasSize(1)
            ->array($this->dispatcher->getListeners(self::postFoo))
                ->hasSize(1)
            ->array($this->dispatcher->getListeners())
                ->hasSize(2);
    }

    public function testGetListenersSortsByPriority()
    {
        $listener1 = new TestEventListener();
        $listener2 = new TestEventListener();
        $listener3 = new TestEventListener();
        $listener1->name = '1';
        $listener2->name = '2';
        $listener3->name = '3';

        $this->dispatcher->addListener('pre.foo', array($listener1, 'preFoo'), -10);
        $this->dispatcher->addListener('pre.foo', array($listener2, 'preFoo'), 10);
        $this->dispatcher->addListener('pre.foo', array($listener3, 'preFoo'));

        $expected = array(
            array($listener2, 'preFoo'),
            array($listener3, 'preFoo'),
            array($listener1, 'preFoo'),
        );

        $this
            ->array($this->dispatcher->getListeners('pre.foo'))
                ->isIdenticalTo($expected);
    }

    public function testGetAllListenersSortsByPriority()
    {
        $listener1 = new TestEventListener();
        $listener2 = new TestEventListener();
        $listener3 = new TestEventListener();
        $listener4 = new TestEventListener();
        $listener5 = new TestEventListener();
        $listener6 = new TestEventListener();

        $this->dispatcher->addListener('pre.foo', $listener1, -10);
        $this->dispatcher->addListener('pre.foo', $listener2);
        $this->dispatcher->addListener('pre.foo', $listener3, 10);
        $this->dispatcher->addListener('post.foo', $listener4, -10);
        $this->dispatcher->addListener('post.foo', $listener5);
        $this->dispatcher->addListener('post.foo', $listener6, 10);

        $expected = array(
            'pre.foo'  => array($listener3, $listener2, $listener1),
            'post.foo' => array($listener6, $listener5, $listener4),
        );

        $this
            ->array($this->dispatcher->getListeners())
                ->isIdenticalTo($expected);
    }

    public function testDispatch()
    {
        $this->dispatcher->addListener('pre.foo', array($this->listener, 'preFoo'));
        $this->dispatcher->addListener('post.foo', array($this->listener, 'postFoo'));
        $this->dispatcher->dispatch(self::preFoo);

        $this
            ->boolean($this->listener->preFooInvoked)
                ->isTrue()
            ->boolean($this->listener->postFooInvoked)
                ->isFalse()
            ->object($this->dispatcher->dispatch('noevent'))
                ->isInstanceOf('Symfony\Component\EventDispatcher\Event')
            ->object($this->dispatcher->dispatch(self::preFoo))
                ->isInstanceOf('Symfony\Component\EventDispatcher\Event');

        $event = new Event();
        $return = $this->dispatcher->dispatch(self::preFoo, $event);

        $this
            ->string($event->getName())
                ->isEqualTo('pre.foo')
            ->object($return)
                ->isIdenticalTo($event);
    }

    public function testDispatchForClosure()
    {
        $invoked = 0;
        $listener = function () use (&$invoked) {
            $invoked++;
        };
        $this->dispatcher->addListener('pre.foo', $listener);
        $this->dispatcher->addListener('post.foo', $listener);
        $this->dispatcher->dispatch(self::preFoo);

        $this
            ->integer($invoked)
                ->isEqualTo(1);
    }

    public function testStopEventPropagation()
    {
        $otherListener = new TestEventListener();

        // postFoo() stops the propagation, so only one listener should
        // be executed
        // Manually set priority to enforce $this->listener to be called first
        $this->dispatcher->addListener('post.foo', array($this->listener, 'postFoo'), 10);
        $this->dispatcher->addListener('post.foo', array($otherListener, 'preFoo'));
        $this->dispatcher->dispatch(self::postFoo);

        $this
            ->boolean($this->listener->postFooInvoked)
                ->isTrue()
            ->boolean($otherListener->postFooInvoked)
                ->isFalse();
    }

    public function testDispatchByPriority()
    {
        $invoked = array();
        $listener1 = function () use (&$invoked) {
            $invoked[] = '1';
        };
        $listener2 = function () use (&$invoked) {
            $invoked[] = '2';
        };
        $listener3 = function () use (&$invoked) {
            $invoked[] = '3';
        };
        $this->dispatcher->addListener('pre.foo', $listener1, -10);
        $this->dispatcher->addListener('pre.foo', $listener2);
        $this->dispatcher->addListener('pre.foo', $listener3, 10);
        $this->dispatcher->dispatch(self::preFoo);

        $this
            ->array($invoked)
                ->isIdenticalTo(array('3', '2', '1'));
    }

    public function testRemoveListener()
    {
        $this->dispatcher->addListener('pre.bar', $this->listener);

        $this
            ->boolean($this->dispatcher->hasListeners(self::preBar))
                ->isTrue()
        ->then($this->dispatcher->removeListener('pre.bar', $this->listener))
            ->boolean($this->dispatcher->hasListeners(self::preBar))
                ->isFalse();

        $this->dispatcher->removeListener('notExists', $this->listener);
    }

    public function testAddSubscriber()
    {
        $eventSubscriber = new TestEventSubscriber();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
            ->boolean($this->dispatcher->hasListeners(self::postFoo))
                ->isTrue();
    }

    public function testAddSubscriberWithPriorities()
    {
        $eventSubscriber = new TestEventSubscriber();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $eventSubscriber = new TestEventSubscriberWithPriorities();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $listeners = $this->dispatcher->getListeners('pre.foo');

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
            ->array($listeners)
                ->hasSize(2)
            ->object($listeners[0][0])
                ->isInstanceOf('tests\units\Symfony\Component\EventDispatcher\TestEventSubscriberWithPriorities');
    }

    public function testAddSubscriberWithMultipleListeners()
    {
        $eventSubscriber = new TestEventSubscriberWithMultipleListeners();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $listeners = $this->dispatcher->getListeners('pre.foo');

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
            ->array($listeners)
                ->hasSize(2)
            ->string($listeners[0][1])
                ->isEqualTo('preFoo2');
    }

    public function testRemoveSubscriber()
    {
        $eventSubscriber = new TestEventSubscriber();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
            ->boolean($this->dispatcher->hasListeners(self::postFoo))
                ->isTrue()
        ->then($this->dispatcher->removeSubscriber($eventSubscriber))
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isFalse()
            ->boolean($this->dispatcher->hasListeners(self::postFoo))
                ->isFalse();
    }

    public function testRemoveSubscriberWithPriorities()
    {
        $eventSubscriber = new TestEventSubscriberWithPriorities();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
        ->then($this->dispatcher->removeSubscriber($eventSubscriber))
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isFalse();
    }

    public function testRemoveSubscriberWithMultipleListeners()
    {
        $eventSubscriber = new TestEventSubscriberWithMultipleListeners();
        $this->dispatcher->addSubscriber($eventSubscriber);

        $this
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isTrue()
            ->array($this->dispatcher->getListeners(self::preFoo))
                ->hasSize(2)
        ->then($this->dispatcher->removeSubscriber($eventSubscriber))
            ->boolean($this->dispatcher->hasListeners(self::preFoo))
                ->isFalse();
    }

    public function testEventReceivesTheDispatcherInstance()
    {
        $test = $this;
        $this->dispatcher->addListener('test', function ($event) use (&$dispatcher) {
            $dispatcher = $event->getDispatcher();
        });
        $this->dispatcher->dispatch('test');

        $this
            ->object($dispatcher)
                ->isEqualTo($this->dispatcher);
    }

    /**
     * @see https://bugs.php.net/bug.php?id=62976
     *
     * This bug affects:
     *  - The PHP 5.3 branch for versions < 5.3.18
     *  - The PHP 5.4 branch for versions < 5.4.8
     *  - The PHP 5.5 branch is not affected
     */
    public function testWorkaroundForPhpBug62976()
    {
        $dispatcher = new tEventDispatcher();
        $dispatcher->addListener('bug.62976', new CallableClass());
        $dispatcher->removeListener('bug.62976', function() {});

        $this
            ->boolean($dispatcher->hasListeners('bug.62976'))
                ->isTrue();
    }
}

class CallableClass
{
    public function __invoke()
    {
    }
}

class TestEventListener
{
    public $preFooInvoked = false;
    public $postFooInvoked = false;

    /* Listener methods */

    public function preFoo(Event $e)
    {
        $this->preFooInvoked = true;
    }

    public function postFoo(Event $e)
    {
        $this->postFooInvoked = true;

        $e->stopPropagation();
    }
}

class TestEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array('pre.foo' => 'preFoo', 'post.foo' => 'postFoo');
    }
}

class TestEventSubscriberWithPriorities implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'pre.foo' => array('preFoo', 10),
            'post.foo' => array('postFoo'),
            );
    }
}

class TestEventSubscriberWithMultipleListeners implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array('pre.foo' => array(
            array('preFoo1'),
            array('preFoo2', 10)
        ));
    }
}
