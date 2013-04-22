<?php

namespace tests\units\PubApp;

use PubApp\Barman as tBarman;
use \atoum;

class Barman extends atoum
{
    public function testServeDrink()
    {
        $barman = new tBarman();

        $this
            ->exception(function() use ($barman) {
                $barman->serveDrink('water');
            })
                ->isInstanceOf('PubApp\WaterException');
    }
}