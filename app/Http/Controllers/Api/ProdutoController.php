<?php

// app/Http/Controllers/Api/ProdutoController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProdutoController extends Controller
{
    /**
     * Listar todos os produtos ativos
     */
    public function index()
    {
        $produtos = Produto::where('ativo', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $produtos,
        ]);
    }

    /**
     * Mostrar um produto específico
     */
    public function show($id)
    {
        $produto = Produto::find($id);

        if (!$produto) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $produto,
        ]);
    }

    /**
     * Criar produto
     */
    public function store(Request $request)
    {
        // Validação
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria' => 'nullable|string|max:255',
            'ativo' => 'nullable|boolean',
            'imagem' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Dados do produto, excluindo a imagem
            $data = $request->except(['imagem']);

            /*
             * Upload da imagem para o Cloudinary
             */
            if ($request->hasFile('imagem')) {

                // Verificar se o ficheiro é válido
                if (!$request->file('imagem')->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A imagem enviada não é válida.',
                    ], 422);
                }

                Log::info('Imagem recebida para upload', [
                    'nome' => $request->file('imagem')->getClientOriginalName(),
                    'tipo' => $request->file('imagem')->getMimeType(),
                    'tamanho' => $request->file('imagem')->getSize(),
                ]);

                /*
                 * Enviar imagem para o Cloudinary
                 */
                $uploadedFile = Cloudinary::upload(
                    $request->file('imagem')->getRealPath(),
                    [
                        'folder' => 'produtos',
                    ]
                );

                /*
                 * Obter URL HTTPS da imagem
                 */
                $uploadedFileUrl = $uploadedFile->getSecurePath();

                Log::info('Upload para Cloudinary concluído', [
                    'url' => $uploadedFileUrl,
                ]);

                // Guardar somente a URL na base de dados
                $data['imagem'] = $uploadedFileUrl;
            }

            /*
             * Criar produto na base de dados
             */
            $produto = Produto::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso',
                'data' => $produto,
            ], 201);

        } catch (\Throwable $e) {

            /*
             * Registar erro no Laravel
             */
            Log::error('Erro ao criar produto', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar produto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar produto
     */
    public function update(Request $request, $id)
    {
        $produto = Produto::find($id);

        if (!$produto) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado',
            ], 404);
        }

        // Validação
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'categoria' => 'nullable|string|max:255',
            'ativo' => 'nullable|boolean',
            'imagem' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            // Dados sem a imagem
            $data = $request->except(['imagem']);

            /*
             * Se foi enviada uma nova imagem,
             * fazer upload para o Cloudinary.
             */
            if ($request->hasFile('imagem')) {

                if (!$request->file('imagem')->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A imagem enviada não é válida.',
                    ], 422);
                }

                Log::info('Nova imagem recebida para atualização', [
                    'nome' => $request->file('imagem')->getClientOriginalName(),
                    'tipo' => $request->file('imagem')->getMimeType(),
                    'tamanho' => $request->file('imagem')->getSize(),
                ]);

                /*
                 * Upload da nova imagem
                 */
                $uploadedFile = Cloudinary::upload(
                    $request->file('imagem')->getRealPath(),
                    [
                        'folder' => 'produtos',
                    ]
                );

                /*
                 * Obter URL HTTPS
                 */
                $uploadedFileUrl = $uploadedFile->getSecurePath();

                Log::info('Nova imagem enviada para Cloudinary', [
                    'url' => $uploadedFileUrl,
                ]);

                // Guardar URL na base de dados
                $data['imagem'] = $uploadedFileUrl;
            }

            /*
             * Atualizar produto
             */
            $produto->update($data);

            /*
             * Obter dados atualizados
             */
            $produto->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'data' => $produto,
            ]);

        } catch (\Throwable $e) {

            Log::error('Erro ao atualizar produto', [
                'produto_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar produto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar produto
     */
    public function destroy($id)
    {
        try {

            $produto = Produto::find($id);

            if (!$produto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado',
                ], 404);
            }

            $produto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produto removido com sucesso',
            ]);

        } catch (\Throwable $e) {

            Log::error('Erro ao remover produto', [
                'produto_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover produto.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
