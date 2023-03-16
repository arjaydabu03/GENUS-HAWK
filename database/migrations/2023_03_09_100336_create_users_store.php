<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_store', function (Blueprint $table) {
            $table->increments("id");
            $table->string("account_code")->unique();
            $table->string("account_name");
            $table->string("mobile_no");

            $table->integer("location_id");
            $table->string("location_code");
            $table->string("location");

            $table->integer("department_id");
            $table->string("department_code");
            $table->string("department");

            $table->integer("company_id");
            $table->string("company_code");
            $table->string("company");

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_store');
    }
};
