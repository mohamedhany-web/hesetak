<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_agreements', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_agreements', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('agreement_number');
            }
            if (! Schema::hasColumn('employee_agreements', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('employee_agreements', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('pdf_path');
            }
            if (! Schema::hasColumn('employee_agreements', 'signer_name')) {
                $table->string('signer_name')->nullable()->after('signed_at');
            }
            if (! Schema::hasColumn('employee_agreements', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('signer_name');
            }
            if (! Schema::hasColumn('employee_agreements', 'signed_ip')) {
                $table->string('signed_ip', 45)->nullable()->after('signature_path');
            }
            if (! Schema::hasColumn('employee_agreements', 'signed_user_agent')) {
                $table->string('signed_user_agent', 512)->nullable()->after('signed_ip');
            }
            if (! Schema::hasColumn('employee_agreements', 'acceptance_otp_hash')) {
                $table->string('acceptance_otp_hash')->nullable()->after('signed_user_agent');
            }
            if (! Schema::hasColumn('employee_agreements', 'acceptance_otp_expires_at')) {
                $table->timestamp('acceptance_otp_expires_at')->nullable()->after('acceptance_otp_hash');
            }
            if (! Schema::hasColumn('employee_agreements', 'acceptance_sent_at')) {
                $table->timestamp('acceptance_sent_at')->nullable()->after('acceptance_otp_expires_at');
            }
        });

        if (! Schema::hasTable('employee_agreement_versions')) {
            Schema::create('employee_agreement_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_agreement_id')->constrained('employee_agreements')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('salary', 10, 2);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->string('status', 32);
                $table->text('contract_terms')->nullable();
                $table->text('agreement_terms')->nullable();
                $table->text('notes')->nullable();
                $table->string('pdf_path')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->string('signer_name')->nullable();
                $table->string('signature_path')->nullable();
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['employee_agreement_id', 'version']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_agreement_versions');

        Schema::table('employee_agreements', function (Blueprint $table) {
            $cols = [
                'version',
                'pdf_path',
                'signed_at',
                'signer_name',
                'signature_path',
                'signed_ip',
                'signed_user_agent',
                'acceptance_otp_hash',
                'acceptance_otp_expires_at',
                'acceptance_sent_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('employee_agreements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
