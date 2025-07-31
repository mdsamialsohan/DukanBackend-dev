<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sell_memos', function (Blueprint $table) {
            $table->boolean('isApproved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();

            // If approved_by references users table
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('sell_memos', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['isApproved', 'approved_by']);
        });
    }
};
