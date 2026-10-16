<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\AlunoAtual;
use App\Services\Portal\Prefeitura;
use Symfony\Component\HttpFoundation\Response;

/**
 * Imagens que o e-cidade envia em base64. Servidas por URL (e não embutidas
 * nas props) para não pesar todas as respostas e poderem ficar em cache.
 */
class ImagemController extends Controller
{
    public function foto(AlunoAtual $aluno): Response
    {
        return $this->imagem($aluno->foto(), privada: true);
    }

    public function logo(Prefeitura $prefeitura): Response
    {
        return $this->imagem($prefeitura->logo(), privada: false);
    }

    protected function imagem(?string $binario, bool $privada): Response
    {
        abort_if($binario === null, 404);

        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binario) ?: 'application/octet-stream';
        abort_unless(str_starts_with($tipo, 'image/'), 404);

        return response($binario, 200, [
            'Content-Type' => $tipo,
            'Cache-Control' => ($privada ? 'private' : 'public').', max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            // Impede script em SVG aberto diretamente no navegador
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
