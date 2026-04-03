<?php

namespace Tests\Unit;

use App\Models\Prize;
use App\Services\PrizeRangeValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrizeRangeValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_range_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(PrizeRangeValidationService::class)->validateForUpsert([
            'title' => 'Broken Prize',
            'quantity' => 5,
            'default_rank_from' => 1,
            'default_rank_to' => null,
            'is_active' => true,
        ]);
    }

    public function test_reversed_range_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(PrizeRangeValidationService::class)->validateForUpsert([
            'title' => 'Broken Prize',
            'quantity' => 5,
            'default_rank_from' => 5,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);
    }

    public function test_overlapping_active_ranges_are_rejected(): void
    {
        Prize::query()->create([
            'title' => 'Top Prize',
            'description' => 'Rank 1-3 reward.',
            'quantity' => 3,
            'default_rank_from' => 1,
            'default_rank_to' => 3,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(PrizeRangeValidationService::class)->validateForUpsert([
            'title' => 'Overlapping Prize',
            'quantity' => 2,
            'default_rank_from' => 3,
            'default_rank_to' => 5,
            'is_active' => true,
        ]);
    }

    public function test_valid_non_overlapping_active_ranges_are_accepted(): void
    {
        Prize::query()->create([
            'title' => 'Top Prize',
            'description' => 'Rank 1 reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        app(PrizeRangeValidationService::class)->validateForUpsert([
            'title' => 'Second Prize',
            'quantity' => 1,
            'default_rank_from' => 2,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);

        $this->assertTrue(true);
    }

    public function test_manual_prize_with_no_range_remains_valid(): void
    {
        app(PrizeRangeValidationService::class)->validateForUpsert([
            'title' => 'Manual Prize',
            'quantity' => 5,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $this->assertTrue(true);
    }

    public function test_model_create_is_rejected_when_partial_range_bypasses_filament_pages(): void
    {
        $this->expectException(ValidationException::class);

        Prize::query()->create([
            'title' => 'Broken Prize',
            'description' => 'Invalid direct write.',
            'quantity' => 5,
            'default_rank_from' => 1,
            'default_rank_to' => null,
            'is_active' => true,
        ]);
    }

    public function test_model_save_is_rejected_when_update_creates_overlapping_active_range(): void
    {
        Prize::query()->create([
            'title' => 'Top Prize',
            'description' => 'Rank 1 reward.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        $manualPrize = Prize::query()->create([
            'title' => 'Manual Prize',
            'description' => 'Initially manual only.',
            'quantity' => 1,
            'default_rank_from' => null,
            'default_rank_to' => null,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        $manualPrize->forceFill([
            'default_rank_from' => 1,
            'default_rank_to' => 2,
        ])->save();
    }
}
