<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('current_module_id', 20)->default('1.1');
            $table->json('completed_modules')->nullable();   // ["1.1","1.2",...]
            $table->json('unlocked_stages')->nullable();     // [1,2,...]
            $table->json('completed_stages')->nullable();    // [1,...]
            $table->json('feedback_entries')->nullable();    // {"1.1":"sprout",...}
            $table->text('journal_entries')->nullable();     // AES-encrypted JSON string
            $table->integer('afro_score')->default(0);
            $table->string('user_persona', 100)->nullable()->default('Heritage Seeker');
            $table->string('lifecycle_phase', 100)->nullable()->default('Foundation Building');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            // One progress record per user
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
