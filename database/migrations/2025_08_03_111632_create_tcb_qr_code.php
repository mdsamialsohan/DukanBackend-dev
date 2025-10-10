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
        Schema::create('tcb', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('nid'); // Required
            $table->string('fcn'); // Required
            $table->text('word')->nullable(); // Optional
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tcb');
    }
};
