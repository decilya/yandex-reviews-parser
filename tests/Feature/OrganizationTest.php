<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Jobs\ParseOrganizationReviewsJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Тесты организаций.
 */
class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест сохранения URL и диспатча задания.
     */
    public function test_user_can_save_yandex_url_and_job_is_dispatched(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/organization', [
            'yandex_url' => 'https://yandex.ru/maps/org/12345678',
        ]);

        $response->assertStatus(202);
        Queue::assertPushed(ParseOrganizationReviewsJob::class);
    }

    /**
     * Тест отклонения невалидного URL.
     */
    public function test_invalid_url_is_rejected(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/organization', ['yandex_url' => 'https://google.com/maps']);
        $response->assertStatus(422)->assertJsonValidationErrors('yandex_url');
    }
}
