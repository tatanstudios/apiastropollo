<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdenesTable extends Migration
{
    /**
     * ORDENES
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();


            $table->bigInteger('id_cliente')->unsigned();
            $table->bigInteger('id_servicio')->unsigned();

            $table->bigInteger('id_zona')->unsigned()->nullable();

            $table->string('nota_orden', 600)->nullable();

            // precio sin afectar cupones
            $table->decimal('total_orden', 10,2);

            $table->dateTime('fecha_orden');


            // FECHA ESTIMADA DE ENTREGA PARA EL CLIENTE (SE TOMA EL TIEMPO DE ZONA UNICAMENTE)
            $table->dateTime('fecha_estimada')->nullable();



            // 0: NO INICIADA   1: ORDEN INICIADA
            $table->boolean('estado_iniciada');
            $table->dateTime('fecha_iniciada')->nullable();


            // 0: NO CANCELADA   1: ORDEN CANCELADA

            $table->boolean('estado_cancelada')->default(0);
            $table->dateTime('fecha_cancelada')->nullable();
            $table->string('nota_cancelada', 300)->nullable();



            // DATOS DE CUPONES
            // cupon de producto gratis aplicado
            $table->bigInteger('id_cupones')->unsigned()->nullable();

            // describiendo lo que se aplico
            $table->string('mensaje_cupon', 400)->nullable();



            // LA ORDEN ES VISIBLE POR EL CLIENTE EN EL LISTADO DE ORDENES ACTIVAS
            $table->boolean('visible');


            // ESTA ES UNA COPIA DEL CUPON PARA QUE GUARDE CUALQUIER CUPON
            $table->bigInteger('id_cupones_copia')->unsigned()->nullable();


            // 0: CANCELADA POR CLIENTE    1: CANCELADA POR RESTAURANTE
            $table->boolean('cancelado_por');


            $table->integer('conteo_notificacion')->default(0);



            $table->foreign('id_cliente')->references('id')->on('clientes');
            $table->foreign('id_servicio')->references('id')->on('servicios');
            $table->foreign('id_cupones')->references('id')->on('cupones');
            $table->foreign('id_cupones_copia')->references('id')->on('cupones');

            $table->foreign('id_zona')->references('id')->on('zonas');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ordenes');
    }
}
