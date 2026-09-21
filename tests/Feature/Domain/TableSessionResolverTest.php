<?php

namespace Tests\Feature\Domain;

use App\Enums\RestaurantStatus;
use App\Enums\SessionStatus;
use App\Exceptions\RestaurantDisabledException;
use App\Exceptions\SessionChangedException;
use App\Exceptions\TableTokenInvalidException;
use App\Models\DiningTable;
use App\Models\TableSession;
use App\Services\DiningTableTokenResolver;
use App\Services\TableSessionResolver;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TableSessionResolverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function token_resolver_rejects_unknown_and_disabled(): void
    {
        $resolver = app(DiningTableTokenResolver::class);

        $this->expectException(TableTokenInvalidException::class);
        $resolver->resolve('missing-token-that-does-not-exist-0123456789');
    }

    #[Test]
    public function token_resolver_rejects_disabled_restaurant(): void
    {
        $table = DiningTable::factory()->create();
        $table->restaurant->update(['status' => RestaurantStatus::Disabled]);

        $this->expectException(RestaurantDisabledException::class);
        app(DiningTableTokenResolver::class)->resolve($table->token);
    }

    #[Test]
    public function null_expected_creates_then_joins_open_session(): void
    {
        $table = DiningTable::factory()->create();
        $resolver = app(TableSessionResolver::class);

        $first = $resolver->resolveForOrder($table, null);
        $second = $resolver->resolveForOrder($table, null);

        $this->assertTrue($first->isOpen());
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, TableSession::query()->where('dining_table_id', $table->id)->where('status', SessionStatus::Open)->count());
    }

    #[Test]
    public function matching_expected_session_attaches(): void
    {
        $table = DiningTable::factory()->create();
        $session = TableSession::factory()->forTable($table)->create();

        $resolved = app(TableSessionResolver::class)->resolveForOrder($table, $session->id);

        $this->assertSame($session->id, $resolved->id);
    }

    #[Test]
    public function stale_expected_session_throws_session_changed(): void
    {
        $table = DiningTable::factory()->create();
        TableSession::factory()->forTable($table)->create();

        $this->expectException(SessionChangedException::class);
        app(TableSessionResolver::class)->resolveForOrder($table, (string) fake()->uuid());
    }

    #[Test]
    public function expected_session_when_table_vacant_throws_session_changed(): void
    {
        $table = DiningTable::factory()->create();

        $this->expectException(SessionChangedException::class);
        app(TableSessionResolver::class)->resolveForOrder($table, (string) fake()->uuid());
    }

    #[Test]
    public function concurrent_first_orders_end_with_single_open_session(): void
    {
        $table = DiningTable::factory()->create();
        $resolver = app(TableSessionResolver::class);

        $first = $resolver->resolveForOrder($table, null);
        $second = $resolver->resolveForOrder($table, null);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(
            1,
            TableSession::query()
                ->where('dining_table_id', $table->id)
                ->where('status', SessionStatus::Open)
                ->count(),
        );

        // Last assertion: DB partial unique index rejects a second open session.
        $this->expectException(QueryException::class);
        TableSession::factory()->forTable($table)->create([
            'status' => SessionStatus::Open,
        ]);
    }

    #[Test]
    public function browsing_does_not_occupy_via_current_open(): void
    {
        $table = DiningTable::factory()->create();

        $this->assertNull(app(TableSessionResolver::class)->currentOpen($table));
        $this->assertSame(0, TableSession::query()->where('dining_table_id', $table->id)->count());
    }
}
