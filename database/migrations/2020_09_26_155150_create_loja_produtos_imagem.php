<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLojaProdutosImagem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loja_produtos_imagens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('produto_variacao_id');
            $table->foreign('produto_variacao_id')->references('id')->on('loja_produtos_variacao');
            $table->string('path');

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
        Schema::dropIfExists('produtoImagem');
    }
}
