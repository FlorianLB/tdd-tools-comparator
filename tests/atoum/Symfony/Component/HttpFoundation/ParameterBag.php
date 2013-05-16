<?php

namespace tests\units\Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\ParameterBag as tParameterBag;
use \atoum;

class ParameterBag extends \atoum
{
    public function testAll()
    {
        $bag = new tParameterBag(array('foo' => 'bar'));
        $this
            ->array($bag->all())
                ->isIdenticalTo(array('foo' => 'bar'));
    }

    public function testKeys()
    {
        $bag = new tParameterBag(array('foo' => 'bar'));
        $this
            ->array($bag->keys())
                ->isIdenticalTo(array('foo'));
    }

    public function testAdd()
    {
        $bag = new tParameterBag(array('foo' => 'bar'));
        $bag->add(array('bar' => 'bas'));
        $this
            ->array($bag->all())
                ->isIdenticalTo(array('foo' => 'bar', 'bar' => 'bas'));
    }

    public function testRemove()
    {
        $bag = new tParameterBag(array('foo' => 'bar'));
        $bag->add(array('bar' => 'bas'));
        $this
            ->array($bag->all())
                ->isIdenticalTo(array('foo' => 'bar', 'bar' => 'bas'));
        $bag->remove('bar');
        $this
            ->array($bag->all())
                ->isIdenticalTo(array('foo' => 'bar'));
    }

    public function testReplace()
    {
        $bag = new tParameterBag(array('foo' => 'bar'));
        $bag->replace(array('FOO' => 'BAR'));
        $this
            ->array($bag->all())
                ->isIdenticalTo(array('FOO' => 'BAR'))
            ->boolean($bag->has('foo'))
                ->isFalse();
    }

    public function testGet()
    {
        $bag = new tParameterBag(array('foo' => 'bar', 'null' => null));
        $this
            ->string($bag->get('foo'))
                ->isEqualTo('bar')
            ->string($bag->get('unknown', 'default'))
                ->isEqualTo('default')
            ->variable($bag->get('null', 'default'))
                ->isNull();
    }

    public function testGetDoesNotUseDeepByDefault()
    {
        $bag = new tParameterBag(array('foo' => array('bar' => 'moo')));
        $this
            ->variable($bag->get('foo[bar]'))
                ->isNull();
    }

    /**
     * @dataProvider getInvalidPaths
     */
    public function testGetDeepWithInvalidPaths($path)
    {
        $bag = new tParameterBag(array('foo' => array('bar' => 'moo')));
        $this
            ->exception(function() use ($bag, $path) {
                    $bag->get($path, null, true);
            })
                ->isInstanceOf('\InvalidArgumentException');
    }

    public function getInvalidPaths()
    {
        return array(
            array('foo[['),
            array('foo[d'),
            array('foo[bar]]'),
            array('foo[bar]d'),
        );
    }

    public function testGetDeep()
    {
        $bag = new tParameterBag(array('foo' => array('bar' => array('moo' => 'boo'))));
        $this
            ->array($bag->get('foo[bar]', null, true))
                ->isIdenticalTo(array('moo' => 'boo'))
            ->string($bag->get('foo[bar][moo]', null, true))
                ->isEqualTo('boo')
            ->string('default')
                ->isEqualTo($bag->get('foo[bar][foo]', 'default', true))
            ->string('default')
                ->isEqualTo($bag->get('bar[moo][foo]', 'default', true));
    }

    public function testSet()
    {
        $bag = new tParameterBag(array());

        $bag->set('foo', 'bar');
        $this
            ->string($bag->get('foo'))
                ->isEqualTo('bar');

        $bag->set('foo', 'baz');
        $this
            ->string($bag->get('foo'))
                ->isEqualTo('baz');
    }

    public function testHas()
    {
        $bag = new tParameterBag(array('foo' => 'bar'));
        $this
            ->boolean($bag->has('foo'))
                ->isTrue()
            ->boolean($bag->has('unknown'))
                ->isFalse();
    }

    public function testGetAlpha()
    {
        $bag = new tParameterBag(array('word' => 'foo_BAR_012'));
        $this
            ->string($bag->getAlpha('word'))
                ->isEqualTo('fooBAR')
            ->string($bag->getAlpha('unknown'))
                ->isEmpty();
    }

    public function testGetAlnum()
    {
        $bag = new tParameterBag(array('word' => 'foo_BAR_012'));
        $this
            ->string($bag->getAlnum('word'))
                ->isEqualTo('fooBAR012')
            ->string($bag->getAlnum('unknown'))
                ->isEmpty();
    }

    public function testGetDigits()
    {
        $bag = new tParameterBag(array('word' => 'foo_BAR_012'));
        $this
            ->string($bag->getDigits('word'))
                ->isEqualTo('012')
            ->string($bag->getDigits('unknown'))
                ->isEmpty();
    }

    public function testGetInt()
    {
        $bag = new tParameterBag(array('digits' => '0123'));

        $this
            ->integer($bag->getInt('digits'))
                ->isEqualTo(123)
            ->integer($bag->getInt('unknown'))
                ->isZero();
    }

    public function testFilter()
    {
        $bag = new tParameterBag(array(
            'digits' => '0123ab',
            'email' => 'example@example.com',
            'url' => 'http://example.com/foo',
            'dec' => '256',
            'hex' => '0x100',
            'array' => array('bang'),
            ));

        $this
            ->string($bag->filter('nokey'))
                ->isEmpty()
            ->string($bag->filter('digits', '', false, FILTER_SANITIZE_NUMBER_INT))
                ->isEqualTo('0123')
            ->string($bag->filter('email', '', false, FILTER_VALIDATE_EMAIL))
                ->isEqualTo('example@example.com')
            ->string($bag->filter('url', '', false, FILTER_VALIDATE_URL, array('flags' => FILTER_FLAG_PATH_REQUIRED)))
                ->isEqualTo('http://example.com/foo')
            ->string($bag->filter('url', '', false, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED))
                ->isEqualTo('http://example.com/foo')
            ->boolean($bag->filter('dec', '', false, FILTER_VALIDATE_INT, array(
                    'flags'   => FILTER_FLAG_ALLOW_HEX,
                    'options' => array('min_range' => 1, 'max_range' => 0xff)
                ))
            )
                ->isFalse()
            ->boolean($bag->filter('hex', '', false, FILTER_VALIDATE_INT, array(
                    'flags'   => FILTER_FLAG_ALLOW_HEX,
                    'options' => array('min_range' => 1, 'max_range' => 0xff)
                ))
            )
                ->isFalse()
            ->array($bag->filter('array', '', false))
                ->isIdenticalTo(array('bang'));
    }

    public function testGetIterator()
    {
        $parameters = array('foo' => 'bar', 'hello' => 'world');
        $bag = new tParameterBag($parameters);

        $i = 0;
        foreach ($bag as $key => $val) {
            $i++;

            $this
                ->string($val)
                    ->isEqualTo($parameters[$key]);
        }

        $this
            ->integer($i)
                ->isEqualTo(count($parameters));
    }

    public function testCount()
    {
        $parameters = array('foo' => 'bar', 'hello' => 'world');
        $bag = new tParameterBag($parameters);

        $this
            ->integer(count($bag))
                ->isEqualTo(count($parameters));
    }
}
