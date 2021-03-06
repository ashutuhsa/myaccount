<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddFieldsInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          Schema::table('invoices', function (Blueprint $table) {
            $table->string('stripe_si_id')->nullable();
            $table->string('plan_id')->nullable();
            $table->string('inv_description')->nullable();
            $table->decimal('inv_total', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['stripe_si_id', 'plan_id', 'inv_description', 'inv_total']);
        });
    }
}
