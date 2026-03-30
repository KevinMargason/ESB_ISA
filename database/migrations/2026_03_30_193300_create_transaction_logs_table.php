<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('ref_type', 50);
            $table->unsignedBigInteger('ref_id');
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_name', 100);
            $table->char('payload_hash', 64);
            $table->char('prev_hash', 64)->nullable();
            $table->char('current_hash', 64);
            $table->unsignedBigInteger('chain_index')->unique();
            $table->timestamp('created_at');

            $table->index(['ref_type', 'ref_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
