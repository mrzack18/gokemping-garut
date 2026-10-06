<?php

namespace Tests\Unit;

use App\Support\TicketToken;
use Tests\TestCase;

/**
 * Token QR tiket (ROADMAP 5.5 lanjutan).
 */
class TicketTokenTest extends TestCase
{
    public function test_token_cocok_dengan_kode_yang_sama(): void
    {
        $token = TicketToken::make('GK-20261015-001');

        $this->assertTrue(TicketToken::matches('GK-20261015-001', $token));
    }

    public function test_token_tidak_cocok_untuk_kode_lain(): void
    {
        $token = TicketToken::make('GK-20261015-001');

        $this->assertFalse(TicketToken::matches('GK-20261015-002', $token));
    }

    public function test_token_tidak_cocok_dengan_tebakan(): void
    {
        $this->assertFalse(TicketToken::matches('GK-20261015-001', 'token-palsu'));
    }

    public function test_url_memuat_kode_booking_dan_tokennya(): void
    {
        $url = TicketToken::url('GK-20261015-001');

        $this->assertStringContainsString('/cek-tiket/GK-20261015-001', $url);
        $this->assertStringContainsString(
            'token='.TicketToken::make('GK-20261015-001'),
            $url,
        );
    }
}
