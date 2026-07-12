<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Doctor;

use Simtabi\Laranail\Package\Tools\Services\Doctor\Checks\ConfigPresentCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;

/**
 * The canonical server-error-pages health checks — one list reused by the
 * service provider (unified doctor), the doctor command, and any health
 * endpoint.
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
                ['config' => 'laranail.server-error-pages'],
                required: true,
                name: 'server-error-pages:config',
                description: 'Server Error Pages config is published',
            ),
        ];
    }
}
