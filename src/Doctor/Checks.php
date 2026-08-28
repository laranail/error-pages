<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Doctor;

use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\Checks\ConfigPresentCheck;

/**
 * The canonical error-pages health checks — reused by the provider (unified
 * doctor), the doctor command, and any health endpoint.
 */
final class Checks
{
    /**
     * @return list<DoctorCheck|class-string<DoctorCheck>>
     */
    public static function all(): array
    {
        return [
            new ConfigPresentCheck(
                ['config' => 'error-pages'],
                required: true,
                name: 'error-pages:config',
                description: 'Error Pages config is published',
            ),
        ];
    }
}
