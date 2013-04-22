<?php

namespace spec\PubApp;

use PHPSpec2\ObjectBehavior;

class Barman extends ObjectBehavior
{
    function it_should_be_initializable()
    {
        $this->shouldHaveType('PubApp\Barman');
    }

    function it_should_not_allow_to_serve_water()
    {
        $this->shouldThrow('PubApp\WaterException')
             ->duringServeDrink('water');
    }
}
