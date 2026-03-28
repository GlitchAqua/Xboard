<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('v2_user', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique()->after('id');
            $table->string('email', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('v2_user', function (Blueprint $table) {
            $table->dropColumn('username');
            $table->string('email', 64)->nullable(false)->change();
        });
    }
};
