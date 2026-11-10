<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_path')->nullable()->change();

            $table->foreignId('created_by')->nullable()->after('order_id')->constrained('users')->nullOnDelete();
            $table->string('recipient_type')->default('person')->after('status');
            $table->string('recipient_name')->nullable()->after('recipient_type');
            $table->string('company_legal_name')->nullable()->after('recipient_name');
            $table->string('company_tax_certificate_path')->nullable()->after('company_legal_name');
            $table->string('company_cr_certificate_path')->nullable()->after('company_tax_certificate_path');
            $table->string('company_city')->nullable()->after('company_cr_certificate_path');
            $table->string('company_district')->nullable()->after('company_city');
            $table->string('company_street')->nullable()->after('company_district');
            $table->string('company_building_number')->nullable()->after('company_street');
            $table->string('company_postal_code')->nullable()->after('company_building_number');
            $table->string('company_additional_number')->nullable()->after('company_postal_code');
            $table->string('company_phone')->nullable()->after('company_additional_number');
            $table->string('company_website')->nullable()->after('company_phone');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'recipient_type',
                'recipient_name',
                'company_legal_name',
                'company_tax_certificate_path',
                'company_cr_certificate_path',
                'company_city',
                'company_district',
                'company_street',
                'company_building_number',
                'company_postal_code',
                'company_additional_number',
                'company_phone',
                'company_website',
            ]);
            $table->string('invoice_path')->nullable(false)->change();
        });
    }
};
