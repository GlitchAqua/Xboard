<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 流量包模板表
        Schema::create('v2_traffic_package', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('流量包名称');
            $table->text('description')->nullable()->comment('描述');
            $table->unsignedBigInteger('traffic_bytes')->comment('流量额度(字节)');
            $table->integer('price')->comment('价格(分)');
            $table->integer('validity_days')->comment('购买后有效天数');
            $table->integer('group_id')->nullable()->comment('授予服务器组ID');
            $table->integer('speed_limit')->nullable()->comment('速度限制(Mbps)');
            $table->tinyInteger('show')->default(1)->comment('是否显示');
            $table->tinyInteger('sell')->default(1)->comment('是否可购买');
            $table->integer('sort')->default(0)->comment('排序');
            $table->integer('created_at');
            $table->integer('updated_at');
        });

        // 用户已购流量包表
        Schema::create('v2_user_traffic_package', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->comment('用户ID');
            $table->integer('traffic_package_id')->comment('流量包模板ID');
            $table->unsignedBigInteger('traffic_bytes')->comment('总流量额度(字节)');
            $table->unsignedBigInteger('used_bytes')->default(0)->comment('已使用字节');
            $table->integer('expired_at')->unsigned()->comment('到期时间');
            $table->tinyInteger('status')->default(1)->comment('1=活跃 0=过期 2=用尽');
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index(['user_id', 'status', 'expired_at'], 'idx_utp_user_status_expired');
        });

        // 余额变动日志表
        Schema::create('v2_balance_log', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->comment('用户ID');
            $table->integer('amount')->comment('变动金额(分), 正=入账, 负=扣费');
            $table->integer('balance_before')->comment('变动前余额');
            $table->integer('balance_after')->comment('变动后余额');
            $table->string('type', 32)->comment('类型: recharge/traffic_deduction/package_purchase/refund/admin_adjust');
            $table->string('description')->nullable()->comment('描述');
            $table->string('related_order_no', 64)->nullable()->comment('关联订单号');
            $table->json('metadata')->nullable()->comment('附加数据');
            $table->integer('created_at');

            $table->index(['user_id', 'created_at'], 'idx_balance_log_user_created');
            $table->index('type', 'idx_balance_log_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v2_balance_log');
        Schema::dropIfExists('v2_user_traffic_package');
        Schema::dropIfExists('v2_traffic_package');
    }
};
