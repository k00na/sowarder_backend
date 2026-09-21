<?php

namespace App\ValueObjects;

use App\Enums\Currency;
use InvalidArgumentException;

readonly class Money
{
    public function __construct(
        public int $amountMinor,
        public Currency $currency,
    ) {
        if ($this->amountMinor < 0) {
            throw new InvalidArgumentException('Money amount_minor must be >= 0.');
        }
    }

    public static function of(int $amountMinor, Currency|string $currency): self
    {
        return new self(
            $amountMinor,
            $currency instanceof Currency ? $currency : Currency::from($currency),
        );
    }

    public function times(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity must be >= 0.');
        }

        return new self($this->amountMinor * $quantity, $this->currency);
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add money with different currencies.');
        }

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    /**
     * @return array{currency: string, amount_minor: int}
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency->value,
            'amount_minor' => $this->amountMinor,
        ];
    }
}
