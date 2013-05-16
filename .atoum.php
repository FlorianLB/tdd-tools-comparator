<?php

// $script->noCodeCoverageForNamespaces('mageekguy');
$script->getRunner()->disableCodeCoverage();

$script->bootstrapFile('bootstrap.php');
$script->addTestAllDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'tests/atoum');