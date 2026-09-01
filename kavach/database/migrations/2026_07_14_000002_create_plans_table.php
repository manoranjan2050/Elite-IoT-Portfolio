<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['trial', 'monthly', 'yearly', 'lifetime']);
            $table->enum('tier', ['normal', 'pro'])->default('normal');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->nullable(); // null = lifetime
            $table->unsignedInteger('max_activations')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
