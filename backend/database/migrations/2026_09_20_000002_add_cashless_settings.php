<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', static function (Blueprint $table) {
            $table->boolean('cashless_enabled')->default(false);
            $table->foreignId('cashless_topup_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->decimal('cashless_min_topup_amount', 14, 2)->default(5);
            $table->boolean('cashless_allow_remaining_balance_refund')->default(false);
            $table->timestamp('cashless_refund_deadline_at')->nullable();
        });

        Schema::table('products', static function (Blueprint $table) {
            $table->boolean('is_cashless_topup')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->dropColumn('is_cashless_topup');
        });

        Schema::table('event_settings', static function (Blueprint $table) {
            $table->dropForeign(['cashless_topup_product_id']);
            $table->dropColumn([
                'cashless_enabled',
                'cashless_topup_product_id',
                'cashless_min_topup_amount',
                'cashless_allow_remaining_balance_refund',
                'cashless_refund_deadline_at',
            ]);
        });
    }
};
