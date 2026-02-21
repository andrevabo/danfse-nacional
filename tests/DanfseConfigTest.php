<?php

namespace DanfseNacional\Tests;

use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\Config\MunicipalityBranding;
use PHPUnit\Framework\TestCase;

class DanfseConfigTest extends TestCase
{
    public function test_no_logo_specified_uses_default_from_assets(): void
    {
        $config = new DanfseConfig();

        $this->assertNotNull($config->logoDataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $config->logoDataUri);
    }

    public function test_logo_false_disables_logo(): void
    {
        $config = new DanfseConfig(logoPath: false);

        $this->assertNull($config->logoDataUri);
    }

    public function test_logo_path_is_converted_to_data_uri(): void
    {
        $path = __DIR__ . '/fixtures/logo.png';
        $config = new DanfseConfig(logoPath: $path);

        $this->assertNotNull($config->logoDataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $config->logoDataUri);

        $expected = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        $this->assertSame($expected, $config->logoDataUri);
    }

    public function test_logo_data_uri_is_used_directly(): void
    {
        $config = new DanfseConfig(logoDataUri: 'data:image/png;base64,abc123');

        $this->assertSame('data:image/png;base64,abc123', $config->logoDataUri);
    }

    public function test_logo_data_uri_takes_precedence_over_path(): void
    {
        $config = new DanfseConfig(
            logoDataUri: 'data:image/png;base64,explicit',
            logoPath: __DIR__ . '/fixtures/logo.png',
        );

        $this->assertSame('data:image/png;base64,explicit', $config->logoDataUri);
    }

    public function test_logo_false_takes_precedence_over_data_uri(): void
    {
        $config = new DanfseConfig(
            logoDataUri: 'data:image/png;base64,explicit',
            logoPath: false,
        );

        $this->assertNull($config->logoDataUri);
    }

    public function test_invalid_logo_path_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/não encontrado/');

        new DanfseConfig(logoPath: '/caminho/inexistente/logo.png');
    }

    public function test_municipality_logo_path_is_converted(): void
    {
        $path = __DIR__ . '/fixtures/logo.png';
        $branding = new MunicipalityBranding(
            name: 'Prefeitura de Niterói',
            logoPath: $path,
        );

        $this->assertNotNull($branding->logoDataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $branding->logoDataUri);
    }

    public function test_municipality_without_logo_is_null(): void
    {
        $branding = new MunicipalityBranding(name: 'Prefeitura de Niterói');

        $this->assertNull($branding->logoDataUri);
    }

    public function test_municipality_invalid_logo_path_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MunicipalityBranding(
            name: 'Prefeitura de Niterói',
            logoPath: '/caminho/inexistente/logo.png',
        );
    }
}
