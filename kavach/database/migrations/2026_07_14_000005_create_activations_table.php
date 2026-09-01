<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint'); // sha256 of domain or machine id
            $table->string('label')->nullable(); // human readable: domain name / PC name
            $table->string('ip', 45)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activations');
    }
};
