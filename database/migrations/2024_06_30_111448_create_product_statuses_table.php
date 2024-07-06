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
        Schema::create('product_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->enum('state', ['active', 'inactive'])->default('active');
            $table->foreignId('product_id')->constrained('products', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_statuses');
    }
};
