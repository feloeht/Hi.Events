<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', static function (Blueprint $table) {
            $table->dropForeign(['cashless_topup_fixed_fee_id']);
            $table->dropForeign(['cashless_topup_percentage_fee_id']);
            $table->dropColumn(['cashless_topup_fixed_fee_id', 'cashless_topup_percentage_fee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('event_settings', static function (Blueprint $table) {
            $table->foreignId('cashless_topup_fixed_fee_id')->nullable()->constrained('taxes_and_fees')->onDelete('set null');
            $table->foreignId('cashless_topup_percentage_fee_id')->nullable()->constrained('taxes_and_fees')->onDelete('set null');
        });
    }
};
