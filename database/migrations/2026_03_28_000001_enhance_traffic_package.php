<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 流量包模板: 新增类型
        Schema::table('v2_traffic_package', function (Blueprint $table) {
            $table->string('type', 16)->default('time-limited')->after('sell')->comment('time-limited | permanent');
        });

        // 用户流量包: 新增自动续费、优先级、续费失败时间
        Schema::table('v2_user_traffic_package', function (Blueprint $table) {
            $table->tinyInteger('auto_renew')->default(0)->after('status')->comment('是否自动续费');
            $table->integer('consumption_priority')->default(0)->after('auto_renew')->comment('消耗优先级(越小越优先)');
            $table->integer('renew_failed_at')->unsigned()->nullable()->after('consumption_priority')->comment('续费失败时间(15天倒计时)');
            $table->integer('renewal_count')->default(0)->after('renew_failed_at')->comment('已续费次数');
        });

        // 允许 expired_at 为 null (永久包)
        Schema::table('v2_user_traffic_package', function (Blueprint $table) {
            $table->integer('expired_at')->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('v2_traffic_package', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('v2_user_traffic_package', function (Blueprint $table) {
            $table->dropColumn(['auto_renew', 'consumption_priority', 'renew_failed_at', 'renewal_count']);
        });
    }
};
