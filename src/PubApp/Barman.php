<?php

namespace PubApp;

class Barman
{
    public function serveDrink($drink)
    {
        if ('water' == $drink) {
            throw new WaterException('Oh no ! You will rust...');
        }
    }
}