<?php

namespace spec\PubApp;

use PHPSpec2\ObjectBehavior;

class Customer extends ObjectBehavior
{
    function it_should_be_initializable()
    {
        $this->beConstructedWith(uniqid(), 25, 'US');
        $this->shouldHaveType('PubApp\Customer');
    }

    function it_should_not_allow_american_teenager_to_drink()
    {
        $this->beConstructedWith(uniqid(), 17, 'US');
        $this->hasLegalDrinkingAge()->shouldBe(false);
    }

    function it_should_allow_american_adult_to_drink()
    {
        $this->beConstructedWith(uniqid(), 23, 'US');
        $this->hasLegalDrinkingAge()->shouldBe(true);
    }
}
