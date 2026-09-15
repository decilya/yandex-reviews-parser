<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Organization\Models\Organization;
use App\Domain\Review\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты пагинации отзывов.
 */
class ReviewPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест пагинации по 50.
     */
    public function test_reviews_are_paginated_by_50(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['user_id' => $user->id]);
        Review::factory()->count(75)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->getJson('/api/reviews?page=1');
        $response->assertStatus(200)
            ->assertJsonCount(50, 'data')
            ->assertJsonPath('meta.total', 75);
    }

    /**
     * Тест метрик организации в ответе.
     */
    public function test_reviews_include_organization_metrics(): void
    {
        $user = User::factory()->create();
        Organization::factory()->create([
            'user_id' => $user->id,
            'name' => 'Тестовое кафе',
            'rating' => 4.5,
            'rating_count' => 100,
            'review_count' => 80,
        ]);

        $response = $this->actingAs($user)->getJson('/api/reviews');
        $response->assertStatus(200)
            ->assertJsonPath('organization.name', 'Тестовое кафе')
            ->assertJsonPath('organization.rating', 4.5);
    }
}
