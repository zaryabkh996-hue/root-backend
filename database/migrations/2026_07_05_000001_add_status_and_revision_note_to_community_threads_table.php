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
        Schema::table('community_threads', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('is_pinned');
            $table->text('revision_note')->nullable()->after('status');
        });

        // Set existing threads to approved so they don't disappear from community view
        \DB::table('community_threads')->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_threads', function (Blueprint $table) {
            $table->dropColumn(['status', 'revision_note']);
        });
    }
};
