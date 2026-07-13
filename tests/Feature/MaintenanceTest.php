<?php

declare(strict_types=1);

it('renders a branded 503 while the app is in maintenance mode', function (): void {
    $this->artisan('down');

    try {
        $response = $this->get('/anything');

        $response->assertStatus(503);
        expect($response->getContent())
            ->toContain('class="ep-status"')
            ->toContain('>503<')
            ->toContain('Be right back');
    } finally {
        $this->artisan('up');
    }
});
