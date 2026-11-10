<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClauseTable extends Migration
{
    public function up()
    {
        Schema::create('clause', function (Blueprint $table) {
            $table->increments('clause_id')->comment('用戶隱私協議id');
            $table->text('clause_content')->nullable()->comment('内容');
            $table->text('en_clause_content')->nullable()->comment('内容（英）');
            $table->integer('clause_status')->default(1)->comment('1 可用 0禁用  -1删除');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clause');
    }
}
