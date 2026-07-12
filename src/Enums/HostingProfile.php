<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Enums;

/**
 * Deployment target for generated server config: shared/cPanel hosting
 * (Apache `.htaccess`) versus a VPS (Nginx vhost + FastCGI intercept).
 */
enum HostingProfile: string
{
    case Shared = 'shared';
    case Vps = 'vps';

    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Vps;
    }
}
