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
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->index()->after('subscription_tier');
            $table->string('stripe_subscription_id')->nullable()->index()->after('stripe_id');
            $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            $table->string('subscription_status')->nullable()->after('stripe_price_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'stripe_subscription_id',
                'stripe_price_id',
                'subscription_status',
                'subscription_ends_at',
            ]);
        });
    }
};
