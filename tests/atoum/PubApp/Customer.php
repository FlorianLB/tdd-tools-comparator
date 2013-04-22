<?php

namespace tests\units\PubApp;

use PubApp\Customer as tCustomer;

use \atoum;

class Customer extends atoum
{
    /**
     * @dataProvider legalAgeDataProvider
     */
    public function testHasLegalDrinkingAge($age, $country, $expectedResult)
    {
        $this
            ->if($customer = new tCustomer('Bukowski', $age, $country))
            ->then
                ->boolean($customer->hasLegalDrinkingAge())
                    ->isEqualTo($expectedResult);
    }

    protected function legalAgeDataProvider()
    {
        return array(
            array(17, 'US', false),
            array(19, 'US', false),
            array(21, 'US', true),
            array(23, 'US', true),
            array(17, 'FR', false),
            array(19, 'FR', true)
        );
    }
}