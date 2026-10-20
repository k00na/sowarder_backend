<?php

namespace Tests\Unit;

use App\Support\PublicDiskUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicDiskUrlTest extends TestCase
{
    #[Test]
    public function localhost_app_url_is_relative(): void
    {
        $this->assertSame('/storage', PublicDiskUrl::resolve(null, 'http://localhost'));
        $this->assertSame('/storage', PublicDiskUrl::resolve('', 'http://localhost:80'));
        $this->assertSame('/storage', PublicDiskUrl::resolve(null, 'http://127.0.0.1'));
    }

    #[Test]
    public function non_local_app_url_stays_absolute(): void
    {
        $this->assertSame(
            'https://api.example.com/storage',
            PublicDiskUrl::resolve(null, 'https://api.example.com'),
        );
    }

    #[Test]
    public function explicit_override_wins(): void
    {
        $this->assertSame(
            'https://cdn.example.com/media',
            PublicDiskUrl::resolve('https://cdn.example.com/media/', 'http://localhost'),
        );
    }
}
