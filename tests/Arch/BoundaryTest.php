<?php

declare(strict_types=1);

// The whole point of the core: it must stay framework-agnostic.
arch('core has no framework dependency')
    ->expect('Simtabi\Laranail\ErrorPages\Core')
    ->not->toUse(['Illuminate', 'Symfony', 'Laravel']);

arch('strict types everywhere')
    ->expect('Simtabi\Laranail\ErrorPages\Core')
    ->toUseStrictTypes();
