<?php

namespace PubApp;

class Customer
{
    protected $name;

    protected $age;

    protected $country;

    public function __construct($name, $age, $country)
    {
        $this->name    = $name;
        $this->age     = $age;
        $this->country = $country;
    }

    public function hasLegalDrinkingAge()
    {
        switch ($this->country) {
            case 'US':
                return $this->age >= 21;
            default:
                return $this->age >= 18;
        }
    }
}
