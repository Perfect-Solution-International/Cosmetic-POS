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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Color, Size, Shade, Volume, Finish
            $table->string('slug')->unique();
            $table->enum('type', ['dropdown', 'color', 'text', 'numeric', 'date', 'checkbox'])->default('dropdown');
            $table->boolean('is_variation')->default(true); // true = generates variation, false = descriptive info
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('value'); // e.g. Ruby Red, Velvet, 50ml, XL
            $table->string('color_code')->nullable(); // Hex color e.g. #E51A2E
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g. PRD-1001
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->enum('type', ['simple', 'variable'])->default('simple');
            $table->boolean('has_expiry')->default(false); // For cosmetics/skincare
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0.00); // Product-specific discount
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name')->nullable(); // e.g. Red / Large
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->decimal('purchase_cost', 12, 2)->default(0.00);
            $table->decimal('selling_price', 12, 2)->default(0.00);
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->integer('reorder_level')->default(5);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variation_values');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};

