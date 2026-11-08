<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('tenant', 40)->index();
            $table->string('sku', 40);
            $table->string('name');
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('stock');
            $table->timestamps();
            $table->unique(['tenant', 'sku']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant', 40)->index();
            $table->string('customer_email');
            $table->unsignedInteger('total_cents');
            $table->string('status', 20);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
