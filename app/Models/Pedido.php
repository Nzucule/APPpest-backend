<?php
// app/Models/Pedido.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome_cliente',
        'telefone_cliente',
        'endereco_completo',
        'bairro',
        'cidade',
        'subtotal',
        'taxa_entrega',
        'total',
        'metodo_pagamento',
        'comprovativo_pagamento',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'taxa_entrega' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itens()
    {
        return $this->hasMany(PedidoItem::class);
    }

    // Mesmo padrão de "badge" já usado em Agendamento
    public function getStatusBadgeAttribute()
    {
        $cores = [
            'pendente'   => 'warning',
            'confirmado' => 'info',
            'enviado'    => 'primary',
            'entregue'   => 'success',
            'cancelado'  => 'danger',
        ];

        return $cores[$this->status] ?? 'secondary';
    }
}
