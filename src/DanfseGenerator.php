<?php

namespace DanfseNacional;

use CuyZ\Valinor\MapperBuilder;
use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\Dto\NFSe;
use DanfseNacional\Template\DanfseTemplate;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Gerador de PDF DANFSE a partir do XML NFS-e Nacional.
 *
 * Uso simples:
 *   $pdf = (new DanfseGenerator())->generateFromXml($xmlString);
 *
 * Com configuração:
 *   $generator = new DanfseGenerator(new DanfseConfig(logoDataUri: '...'));
 *   $pdf = $generator->generateFromXml($xmlString);
 */
class DanfseGenerator
{
    public function __construct(
        private readonly DanfseConfig $config = new DanfseConfig(),
    ) {}

    /**
     * Gera o PDF DANFSE a partir do XML da NFS-e.
     *
     * @return string Conteúdo binário do PDF
     */
    public function generateFromXml(string $xml): string
    {
        $nfse = $this->parseXml($xml);
        return $this->generatePdf($nfse);
    }

    /**
     * Faz o parse do XML e retorna o DTO NFSe.
     */
    public function parseXml(string $xml): NFSe
    {
        $converter = new XmlToArray();
        $array = $converter->convert($xml);

        $mapper = (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->allowPermissiveTypes()
            ->mapper();

        return $mapper->map(NFSe::class, $array);
    }

    /**
     * Gera e renderiza o template HTML a partir do DTO da NFSe.
     * Útil para testes.
     * @param  NFSe  $nfse
     * @return string
     */
    public function generateHtml(NFSe $nfse): string
    {
        $template = new DanfseTemplate();
        return $template->render($nfse, $this->config);
    }

    /**
     * Gera o PDF a partir do DTO NFSe já processado.
     *
     * @return string Conteúdo binário do PDF
     */
    public function generatePdf(NFSe $nfse): string
    {
        $html = $this->generateHtml($nfse);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial');
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
