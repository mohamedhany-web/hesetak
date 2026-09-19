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
        // التحقق من وجود الجدول أولاً
        if (!Schema::hasTable('certificates')) {
            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            // إضافة السيريال الفريد
            if (!Schema::hasColumn('certificates', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('certificate_number');
            }
            
            // إضافة توقيع الأكاديمية
            if (!Schema::hasColumn('certificates', 'academy_signature')) {
                $table->string('academy_signature')->nullable()->after('metadata');
            }
            
            if (!Schema::hasColumn('certificates', 'academy_signature_name')) {
                $table->string('academy_signature_name')->default('المدير العام')->after('academy_signature');
            }
            
            if (!Schema::hasColumn('certificates', 'academy_signature_title')) {
                $table->string('academy_signature_title')->default('حصتك')->after('academy_signature_name');
            }
            
            // إضافة توقيع المدرب
            if (!Schema::hasColumn('certificates', 'instructor_id')) {
                $table->unsignedBigInteger('instructor_id')->nullable()->after('academy_signature_title');
                $table->foreign('instructor_id')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('certificates', 'instructor_signature')) {
                $table->string('instructor_signature')->nullable()->after('instructor_id');
            }
            
            if (!Schema::hasColumn('certificates', 'instructor_signature_name')) {
                $table->string('instructor_signature_name')->nullable()->after('instructor_signature');
            }
            
            if (!Schema::hasColumn('certificates', 'instructor_signature_title')) {
                $table->string('instructor_signature_title')->default('المدرب المعتمد')->after('instructor_signature_name');
            }
            
            // إضافة QR Code للتحقق
            if (!Schema::hasColumn('certificates', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('pdf_path');
            }
            
            // إضافة رابط التحقق
            if (!Schema::hasColumn('certificates', 'verification_url')) {
                $table->string('verification_url')->nullable()->after('verification_code');
            }
            
            // إضافة تاريخ التوثيق
            if (!Schema::hasColumn('certificates', 'certified_at')) {
                $table->timestamp('certified_at')->nullable()->after('issued_at');
            }
            
            // إضافة hash للتأكد من عدم التلاعب
            if (!Schema::hasColumn('certificates', 'certificate_hash')) {
                $table->string('certificate_hash')->nullable()->after('certified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'serial_number',
                'academy_signature',
                'academy_signature_name',
                'academy_signature_title',
                'instructor_id',
                'instructor_signature',
                'instructor_signature_name',
                'instructor_signature_title',
                'qr_code_path',
                'verification_url',
                'certified_at',
                'certificate_hash'
            ]);
        });
    }
};
