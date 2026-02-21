<?php

namespace DanfseNacional\Config;

readonly class MunicipalityBranding
{
    public ?string $logoDataUri;

    public function __construct(
        public string $name,
        public string $department = '',
        public string $email = '',
        ?string $logoDataUri = null,
        ?string $logoPath = null,
    ) {
        $this->logoDataUri = $logoDataUri ?? ($logoPath !== null ? self::pathToDataUri($logoPath) : null);
    }

    private static function pathToDataUri(string $path): string
    {
        if (!is_readable($path)) {
            throw new \InvalidArgumentException("Arquivo de logo não encontrado ou ilegível: {$path}");
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Não foi possível ler o arquivo de logo: {$path}");
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}
