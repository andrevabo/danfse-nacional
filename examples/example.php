<?php

require __DIR__ . '/../vendor/autoload.php';

use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\Config\MunicipalityBranding;
use DanfseNacional\DanfseGenerator;

$xmlPath = __DIR__ . '/nfse_exemplo.xml';
$xml     = file_get_contents($xmlPath);

// --- Exemplo 1: geração simples, sem configuração ---

$generator = new DanfseGenerator();
$pdf = $generator->generateFromXml($xml);

$output = __DIR__ . '/danfse_simples.pdf';
file_put_contents($output, $pdf);
echo "PDF simples gerado: {$output} (" . number_format(strlen($pdf) / 1024, 1) . " KB)\n";

// --- Exemplo 2: inspecionar os dados antes de gerar ---

$nfse = $generator->parseXml($xml);
$inf  = $nfse->infNFSe;

echo "\nDados extraídos do XML:\n";
echo "  NFS-e número : " . $inf->nNFSe . "\n";
echo "  Emitente     : " . $inf->emit->xNome . "\n";
echo "  CNPJ         : " . $inf->emit->CNPJ . "\n";
echo "  Tomador      : " . $inf->DPS->infDPS->toma->xNome . "\n";
echo "  Valor líquido: R$ " . $inf->valores->vLiq . "\n";
echo "  Competência  : " . $inf->DPS->infDPS->dCompet . "\n";
echo "  Ambiente     : " . ($inf->DPS->infDPS->tpAmb === '1' ? 'Produção' : 'Homologação') . "\n";

// --- Exemplo 3: com logo e identificação do município ---
// logoPath: null  → usa o logo padrão do pacote (assets/)
// logoPath: false → nenhum logo exibido
// brasao-niteroi.png por Guilherme Paula

$config = new DanfseConfig(
    municipality: new MunicipalityBranding(
        name: 'Prefeitura de Niterói',
        department: 'Secretaria Municipal de Fazenda',
        email: 'iss@fazenda.niteroi.rj.gov.br',
        logoPath: __DIR__ . '/brasao-niteroi.png',
    ),
);

$generator = new DanfseGenerator($config);
$pdf = $generator->generateFromXml($xml);

$output = __DIR__ . '/danfse_com_config.pdf';
file_put_contents($output, $pdf);

$generator = new DanfseGenerator($config);
$nfse = $generator->parseXml($xml);
$html = $generator->generateHtml($nfse);
file_put_contents(__DIR__ . '/danfse_com_config.html', $html);

echo "\nPDF: {$output} (" . number_format(strlen($pdf) / 1024, 1) . " KB)\n";

echo "\nConcluído. Os PDFs foram salvos em " . __DIR__ . "\n";
