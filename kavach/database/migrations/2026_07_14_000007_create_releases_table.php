<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('version', 20); // semver: 1.4.2
            $table->enum('channel', ['stable', 'beta'])->default('stable');
            // minimum tier required to receive this release (null = everyone)
            $table->enum('min_tier', ['normal', 'pro'])->nullable();
            $table->text('changelog')->nullable();
            $table->string('file_path'); // storage/app/private/releases/...
            $table->string('file_hash', 64); // sha256
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'version', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
