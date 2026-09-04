<?php
// app/Http/Controllers/Api/PedidoController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PedidoController extends Controller
{
    // Cliente: Criar novo pedido (venda de artigos)
    // Espera: { itens: [{produto_id, quantidade}, ...], endereco_completo, bairro, cidade, observacoes }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.quantidade' => 'required|integer|min:1',
            'endereco_completo' => 'required|string',
            'bairro' => 'nullable|string',
            'cidade' => 'nullable|string',
            'observacoes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $user = $request->user();
            $subtotal = 0;
            $itensParaCriar = [];

            // Valida stock e calcula valores ANTES de gravar nada
            foreach ($request->itens as $item) {
                $produto = Produto::findOrFail($item['produto_id']);

                if (!$produto->ativo) {
                    throw new \Exception("O produto \"{$produto->nome}\" não está disponível.");
                }

                if ($produto->stock < $item['quantidade']) {
                    throw new \Exception("Stock insuficiente para \"{$produto->nome}\". Disponível: {$produto->stock}.");
                }

                $itemSubtotal = $produto->preco * $item['quantidade'];
                $subtotal += $itemSubtotal;

                $itensParaCriar[] = [
                    'produto' => $produto,
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $produto->preco,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // Taxa de entrega fixa por agora (ajusta depois se quiseres regra por zona, como nos agendamentos)
            $taxaEntrega = 0;
            $total = $subtotal + $taxaEntrega;

            $pedido = Pedido::create([
                'user_id' => $user->id,
                'nome_cliente' => $user->name,
                'telefone_cliente' => $user->telefone,
                'endereco_completo' => $request->endereco_completo,
                'bairro' => $request->bairro,
                'cidade' => $request->cidade,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxaEntrega,
                'total' => $total,
                'status' => 'pendente',
                'observacoes' => $request->observacoes,
            ]);

            foreach ($itensParaCriar as $item) {
                $pedido->itens()->create([
                    'produto_id' => $item['produto']->id,
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Reserva o stock
                $item['produto']->decrement('stock', $item['quantidade']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido realizado com sucesso!',
                'data' => $pedido->load('itens.produto'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido: ' . $e->getMessage(),
            ], 422);
        }
    }

    // Cliente: Listar os meus pedidos
    public function meus(Request $request)
    {
        $pedidos = Pedido::with('itens.produto')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pedidos,
        ]);
    }

    // Cliente: Ver um pedido específico (só o dono do pedido)
    public function show(Request $request, $id)
    {
        $pedido = Pedido::with('itens.produto')
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pedido,
        ]);
    }

    // Cliente: Cancelar pedido
    public function cancelar(Request $request, $id)
    {
        try {
            $pedido = Pedido::with('itens')
                ->where('user_id', $request->user()->id)
                ->where('id', $id)
                ->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            if (!in_array($pedido->status, ['pendente', 'confirmado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido não pode ser cancelado.',
                ], 422);
            }

            DB::beginTransaction();

            // Devolve o stock reservado
            foreach ($pedido->itens as $item) {
                Produto::where('id', $item->produto_id)->increment('stock', $item->quantidade);
            }

            $pedido->status = 'cancelado';
            $pedido->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado com sucesso!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pedido',
            ], 500);
        }
    }

    // Admin: Listar todos os pedidos
    public function index()
    {
        $pedidos = Pedido::with(['user', 'itens.produto'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pedidos,
        ]);
    }

    // Admin: Atualizar status do pedido
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pendente,confirmado,enviado,entregue,cancelado',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pedido = Pedido::with('itens')->findOrFail($id);
        $oldStatus = $pedido->status;

        // Se está a cancelar agora (e ainda não estava cancelado), devolve o stock
        if ($request->status === 'cancelado' && $oldStatus !== 'cancelado') {
            foreach ($pedido->itens as $item) {
                Produto::where('id', $item->produto_id)->increment('stock', $item->quantidade);
            }
        }

        $pedido->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido atualizado com sucesso',
            'data' => $pedido,
        ]);
    }

    // Admin: Deletar pedido
    public function destroy($id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pedido removido com sucesso',
        ]);
    }
}
