<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServicoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AgendamentoController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\PedidoController;

// ========== ROTAS PÚBLICAS ==========
Route::post('/register', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/servicos', [ServicoController::class, 'index']);
Route::get('/servicos/{id}', [ServicoController::class, 'show']);
Route::get('/produtos', [ProdutoController::class, 'index']);
Route::get('/produtos/{id}', [ProdutoController::class, 'show']);

// ========== ROTAS PROTEGIDAS (QUALQUER USUÁRIO AUTENTICADO) ==========
Route::middleware(['auth:sanctum','cliente'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/perfil', [UserController::class, 'getPerfil']);
    Route::put('/perfil', [UserController::class, 'updatePerfil']);
    
    // Agendamentos do cliente
    Route::post('/agendamentos', [AgendamentoController::class, 'store']);
    Route::get('/meus-agendamentos', [AgendamentoController::class, 'meus']);
    Route::get('/historico', [AgendamentoController::class, 'historico']); // NOVA ROTA
    Route::post('/cancelar-agendamento/{id}', [AgendamentoController::class, 'cancelar']); // NOVA ROTA
    Route::get('/agendamentos/{id}', [AgendamentoController::class, 'show']);

    // Pedidos (venda de artigos) do cliente
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/meus-pedidos', [PedidoController::class, 'meus']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']);
    Route::post('/cancelar-pedido/{id}', [PedidoController::class, 'cancelar']);
});

// ========== ROTAS APENAS PARA ADMIN ==========
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Serviços - CRUD completo
    Route::post('/servicos', [ServicoController::class, 'store']);
    Route::put('/servicos/{id}', [ServicoController::class, 'update']);
    Route::delete('/servicos/{id}', [ServicoController::class, 'destroy']);
    
    // Usuários - apenas clientes
    Route::get('/users/clientes', [UserController::class, 'clientes']);
    Route::delete('/users/clientes/{id}', [UserController::class, 'deleteCliente']);
    
    // Agendamentos - todos (admin)
    Route::get('/agendamentos', [AgendamentoController::class, 'index']);
    Route::put('/agendamentos/{id}', [AgendamentoController::class, 'update']);
    Route::delete('/agendamentos/{id}', [AgendamentoController::class, 'destroy']);

    // Produtos - CRUD completo
    Route::post('/produtos', [ProdutoController::class, 'store']);
    Route::put('/produtos/{id}', [ProdutoController::class, 'update']);
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy']);

    // Pedidos - todos (admin)
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::put('/pedidos/{id}', [PedidoController::class, 'update']);
    Route::delete('/pedidos/{id}', [PedidoController::class, 'destroy']);
});