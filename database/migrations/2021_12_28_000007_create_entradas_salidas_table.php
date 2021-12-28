<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntradasSalidasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entradas_salidas', function (Blueprint $table) {
            $table->id();

            $table->string('descripcion', 200)->nullable(true);
            $table->double('cantidad', 10, 4);
            $table->string('fechaT', 25)->nullable(true);
            $table->string('cajero', 25)->nullable(true);
            $table->integer('tipo_movimiento')->nullable(true);
            $table->integer('edo')->nullable(true);

            $table->integer('tipo_pago')->nullable(true);
            $table->unsignedBigInteger('efectivo_cuenta_bancos_id')->nullable(true); // cuenta banco
            $table->foreign('efectivo_cuenta_bancos_id')
                    ->references('id')
                    ->on('efectivo_cuenta_bancos');

            $table->string('clave', 25)->nullable(true);
            $table->string('tiempo', 25)->nullable(true);
            $table->integer('td')->nullable(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entradas_salidas');
    }
}
