<?php

declare(strict_types=1);

/*
 * Human-readable problem-type documentation shown on the pages served at
 * `problem.docs.route/{code}` (the RFC 7807/9457 `type` target). Each entry is
 * { meaning, causes[], resolution[] }. A specific code falls back to its class
 * (`4xx`/`5xx`) entry. Publish with
 * `--tag=laranail::error-pages-translations` and edit
 * lang/vendor/error-pages/en/problems.php to customise.
 */

return [
    '4xx' => [
        'meaning' => 'The request could not be completed because of something in the request itself — not a fault on the server.',
        'causes' => [
            'The address (URL) is mistyped, outdated, or no longer exists.',
            'Required parameters are missing or invalid.',
            'You are not signed in, or you lack permission for this resource.',
        ],
        'resolution' => [
            'Double-check the address and the parameters you sent.',
            'Sign in, or request access if the resource is protected.',
            'Correct the request and send it again.',
        ],
    ],

    '5xx' => [
        'meaning' => 'Something went wrong on our side while handling the request. This is not a problem with your request.',
        'causes' => [
            'A temporary outage, deployment, or overload.',
            'An unexpected error in the application.',
        ],
        'resolution' => [
            'Wait a moment and try the request again.',
            'If it keeps happening, contact support and include the reference id shown on the page.',
        ],
    ],

    '403' => [
        'meaning' => 'You are authenticated, but you do not have permission to access this resource.',
        'causes' => [
            'Your account lacks the required role or permission.',
            'The resource belongs to another account or team.',
        ],
        'resolution' => [
            'Request access from an administrator.',
            'Switch to an account that has permission.',
        ],
    ],

    '404' => [
        'meaning' => 'The page or resource you asked for does not exist at this address.',
        'causes' => [
            'The URL is mistyped or out of date.',
            'The resource was moved or deleted.',
        ],
        'resolution' => [
            'Check the address for typos.',
            'Start from the home page and navigate to what you need.',
        ],
    ],

    '429' => [
        'meaning' => 'You have sent too many requests in a short time and have been rate-limited.',
        'causes' => [
            'A burst of requests from your client or IP.',
            'A tight loop or missing back-off in an integration.',
        ],
        'resolution' => [
            'Wait for the period in the Retry-After header, then try again.',
            'Reduce how often you send requests, or add exponential back-off.',
        ],
    ],

    '503' => [
        'meaning' => 'The service is temporarily unavailable, usually for maintenance or because it is overloaded.',
        'causes' => [
            'Scheduled maintenance.',
            'A temporary spike in traffic.',
        ],
        'resolution' => [
            'Try again shortly — the Retry-After header suggests when.',
        ],
    ],
];
