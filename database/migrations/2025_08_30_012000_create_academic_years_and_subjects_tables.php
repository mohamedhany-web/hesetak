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
        // جدول السنوات الدراسية
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('color', 7)->default('#3B82F6'); // HEX color
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['is_active', 'order']);
            });
        }
        
        // جدول المواد الدراسية
        if (!Schema::hasTable('academic_subjects')) {
            Schema::create('academic_subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('color', 7)->default('#3B82F6'); // HEX color
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['academic_year_id', 'is_active', 'order']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_subjects');
        Schema::dropIfExists('academic_years');
    }
};
