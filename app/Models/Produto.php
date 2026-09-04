<?php
// app/Models/Produto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'stock',
        'categoria',
        'imagem',
        'ativo',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    // Mantemos o atributo acessor virtual se o React usar produto.imagem_url
    protected $appends = ['imagem_url'];

    public function getImagemUrlAttribute()
    {
        if (!$this->imagem) {
            return null;
        }

        // Se já for uma URL completa (ex: Cloudinary), devolve diretamente
        if (filter_var($this->imagem, FILTER_VALIDATE_URL)) {
            return $this->imagem;
        }

        // Suporte para imagens antigas guardadas localmente
        return asset('storage/' . $this->imagem);
    }

    public function pedidoItens()
    {
        return $this->hasMany(PedidoItem::class);
    }
}