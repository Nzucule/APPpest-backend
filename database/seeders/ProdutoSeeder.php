<?php
// database/seeders/ProdutoSeeder.php

namespace Database\Seeders;

use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run()
    {
        Produto::create([
            'nome' => 'Veneno para Ratos (500g)',
            'descricao' => 'Raticida de ação rápida, seguro para uso doméstico.',
            'preco' => 250,
            'stock' => 50,
            'categoria' => 'desratizacao',
            'ativo' => true,
        ]);

        Produto::create([
            'nome' => 'Armadilha para Insetos',
            'descricao' => 'Armadilha adesiva reutilizável.',
            'preco' => 180,
            'stock' => 30,
            'categoria' => 'fumigacao',
            'ativo' => true,
        ]);

        Produto::create([
            'nome' => 'Spray Repelente de Cupins (1L)',
            'descricao' => 'Repelente de longa duração para madeira e estruturas.',
            'preco' => 420,
            'stock' => 20,
            'categoria' => 'termico',
            'ativo' => true,
        ]);
    }
}
