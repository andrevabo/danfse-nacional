<?php

namespace DanfseNacional\Config;

readonly class DanfseConfig
{
    public ?string $logoDataUri;

    /**
     * @param string|null  $logoDataUri Data URI pronto (data:image/png;base64,...). Tem precedência sobre logoPath.
     * @param string|false|null $logoPath Caminho para o arquivo de logo. null usa o logo padrão do pacote; false desativa o logo.
     */
    public function __construct(
        ?string $logoDataUri = null,
        string|false|null $logoPath = null,
        public ?MunicipalityBranding $municipality = null,
    ) {
        if ($logoPath === false) {
            $this->logoDataUri = null;
            return;
        }

        $this->logoDataUri = $logoDataUri
            ?? ($logoPath !== null ? self::pathToDataUri($logoPath) : self::defaultLogoDataUri());
    }

    private static function defaultLogoDataUri(): ?string
    {
        $path = __DIR__ . '/../../assets/logo-nfse.png';
        return is_readable($path) ? self::pathToDataUri($path) : null;
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
