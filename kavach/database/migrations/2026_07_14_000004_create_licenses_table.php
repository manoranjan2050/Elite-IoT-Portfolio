<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('license_key')->unique();
            $table->enum('status', ['active', 'expired', 'suspended'])->default('active');
            // tier copied from plan at issue time; can be upgraded independently
            $table->enum('tier', ['normal', 'pro'])->default('normal');
            $table->timestamp('expires_at')->nullable(); // null = lifetime
            $table->unsignedInteger('max_activations')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
