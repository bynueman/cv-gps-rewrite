<?php

use App\Support\StoryDatabase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'stories';

    public function up(): void
    {
        StoryDatabase::ensureConfigured();
        $schema = Schema::connection('stories');
        $schema->create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30);
            $table->text('body');
            $table->string('visibility', 10);
            $table->string('status', 12)->default('pending');
            $table->boolean('publication_consent')->default(false);
            $table->boolean('social_consent')->default(false);
            $table->string('policy_version', 80);
            $table->timestamp('consented_at');
            $table->string('deletion_token_hash', 64)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('request_hash', 64);
            $table->string('fingerprint', 64);
            $table->string('duplicate_hash', 64);
            $table->unsignedInteger('supports_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['visibility', 'status', 'publication_consent', 'deleted_at', 'id'], 'story_public_feed');
            $table->index(['category', 'id']);
            $table->index('published_at');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['fingerprint', 'duplicate_hash', 'created_at'], 'story_duplicates');
        });
        $schema->create('story_moderation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 40);
            $table->string('from_status', 12)->nullable();
            $table->string('to_status', 12);
            $table->string('reason', 300)->nullable();
            $table->timestamps();
            $table->index(['story_id', 'id']);
        });
        $schema->create('story_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('reason', 20);
            $table->string('details', 300)->nullable();
            $table->string('status', 12)->default('pending');
            $table->timestamps();
            $table->unique(['story_id', 'fingerprint']);
            $table->index(['status', 'id']);
        });
        $schema->create('story_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->timestamp('created_at');
            $table->unique(['story_id', 'fingerprint']);
        });
        $schema->create('story_limits', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        StoryDatabase::ensureConfigured();
        foreach (['story_limits', 'story_reactions', 'story_reports', 'story_moderation_events', 'stories'] as $table) {
            Schema::connection('stories')->dropIfExists($table);
        }
    }
};
