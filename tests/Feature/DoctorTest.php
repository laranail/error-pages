<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorStatus;
use Simtabi\Laranail\ServerErrorPages\Doctor\Checks;

/**
 * Resolve the doctor check list into runnable instances (class-strings → app()).
 *
 * @return list<DoctorCheck>
 */
function resolvedDoctorChecks(): array
{
    return array_map(
        static fn (DoctorCheck|string $check): DoctorCheck => is_string($check) ? app($check) : $check,
        Checks::all(),
    );
}

it('passes the config-present check for a normally-installed package', function (): void {
    foreach (resolvedDoctorChecks() as $check) {
        expect($check->run()->status)->toBe(DoctorStatus::Pass);
    }
});

it('fails the config-present check when the config namespace is empty', function (): void {
    config()->set('laranail.server-error-pages');

    foreach (resolvedDoctorChecks() as $check) {
        expect($check->run()->status)->toBe(DoctorStatus::Fail);
    }
});
