<?php
// database/migrations/2026_09_03_100100_create_pedidos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Dados de entrega (mesmo padrão usado em agendamentos)
            $table->string('nome_cliente');
            $table->string('telefone_cliente');
            $table->string('endereco_completo');
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();

            // Valores
            $table->decimal('subtotal', 10, 2);
            $table->decimal('taxa_entrega', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Pagamento: sem método definido por agora.
            // Campos ficam prontos (nullable) para quando integrarem M-Pesa/e-Mola/etc.
            $table->string('metodo_pagamento')->nullable();
            $table->string('comprovativo_pagamento')->nullable();

            $table->enum('status', ['pendente', 'confirmado', 'enviado', 'entregue', 'cancelado'])
                  ->default('pendente');

            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pedidos');
    }
};
