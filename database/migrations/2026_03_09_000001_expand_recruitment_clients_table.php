<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_clients', function (Blueprint $table) {
            $table->string('cnpj_cpf')->nullable()->after('type');
            $table->string('secondary_contact_name')->nullable()->after('contact_name');
            $table->string('secondary_phone')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('address')->nullable()->after('website');
            $table->string('city')->nullable()->after('address');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip_code', 10)->nullable()->after('state');
            $table->string('logo_path')->nullable()->after('zip_code');
            $table->text('notes')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_clients', function (Blueprint $table) {
            $table->dropColumn([
                'cnpj_cpf', 'secondary_contact_name', 'secondary_phone',
                'website', 'address', 'city', 'state', 'zip_code',
                'logo_path', 'notes',
            ]);
        });
    }
};
