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
        Schema::create('cricket_matches', function (Blueprint $table) {
            $table->id();
            $table->string('team_one', 100);
            $table->string('team_two', 100);
            $table->unsignedTinyInteger('overs_limit');
            $table->string('status', 20)->default('in_progress');
            $table->timestamps();
        });

        Schema::create('cricket_innings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cricket_match_id')->constrained('cricket_matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('innings_number');
            $table->string('batting_team', 100);
            $table->string('status', 20)->default('active');
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['cricket_match_id', 'innings_number']);
        });

        Schema::create('score_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cricket_innings_id')->constrained('cricket_innings')->cascadeOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->unsignedTinyInteger('batter_runs')->default(0);
            $table->string('extras_type', 20)->default('none');
            $table->unsignedTinyInteger('extra_runs')->default(0);
            $table->unsignedTinyInteger('total_runs')->default(0);
            $table->boolean('is_wicket')->default(false);
            $table->boolean('legal_ball')->default(true);
            $table->timestamps();
            $table->unique(['cricket_innings_id', 'sequence_number']);
        });

        Schema::create('score_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cricket_innings_id')->constrained('cricket_innings')->cascadeOnDelete();
            $table->foreignId('score_delivery_id')->nullable()->constrained('score_deliveries')->nullOnDelete();
            $table->string('event_type', 40);
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['cricket_innings_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_audits');
        Schema::dropIfExists('score_deliveries');
        Schema::dropIfExists('cricket_innings');
        Schema::dropIfExists('cricket_matches');
    }
};
