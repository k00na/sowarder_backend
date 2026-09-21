<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of(-1, Currency::Usd);
    }

    #[Test]
    public function it_multiplies_and_adds_same_currency(): void
    {
        $unit = Money::of(1500, Currency::Mvr);

        $this->assertSame(4500, $unit->times(3)->amountMinor);
        $this->assertSame(3000, $unit->add(Money::of(1500, Currency::Mvr))->amountMinor);
    }

    #[Test]
    public function it_rejects_adding_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of(100, Currency::Usd)->add(Money::of(100, Currency::Mvr));
    }
}
