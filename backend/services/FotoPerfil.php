<?php
declare(strict_types=1);

final class FotoPerfil
{
    public static function processarUpload(array $arquivo): string
    {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Selecione uma foto de até 2 MB.');
        }
        $caminho = $arquivo['tmp_name'] ?? '';
        if (!is_string($caminho) || !is_uploaded_file($caminho) || filesize($caminho) > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('A foto deve ter até 2 MB.');
        }
        $info = @getimagesize($caminho);
        if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] > 4096 || $info[1] > 4096) {
            throw new InvalidArgumentException('Use uma foto JPG, PNG ou WebP de até 4096 × 4096 pixels.');
        }
        // Armazena como imagem embutida, sem criar arquivos executáveis no servidor.
        $mime = image_type_to_mime_type($info[2]);
        if ((new finfo(FILEINFO_MIME_TYPE))->file($caminho) !== $mime) {
            throw new InvalidArgumentException('O arquivo deve ser uma imagem JPG, PNG ou WebP válida.');
        }
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($caminho));
    }
}
