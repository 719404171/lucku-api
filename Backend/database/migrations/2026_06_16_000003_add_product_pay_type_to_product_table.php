<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductPayTypeToProductTable extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            if (!Schema::hasColumn('product', 'product_pay_type')) {
                $table->tinyInteger('product_pay_type')->default(3)->comment('支付方式：1=金幣, 2=鑽石, 3=全部')->after('product_type');
            }
        });
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn('product_pay_type');
        });
    }
}
