<?php
// Миграция: снимки данных после каждого парсинга.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->decimal('rating', 3, 2);
            $table->unsignedInteger('rating_count');
            $table->unsignedInteger('review_count');
            $table->unsignedInteger('reviews_fetched');
            $table->timestamp('snapshotted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_snapshots');
    }
};
