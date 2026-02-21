<?php

namespace DanfseNacional\Tests;

use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\Config\MunicipalityBranding;
use DanfseNacional\DanfseGenerator;
use DanfseNacional\Dto\NFSe;
use PHPUnit\Framework\TestCase;

class DanfseGeneratorTest extends TestCase
{
    private string $realXml;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../examples/nfse_exemplo.xml';
        $this->realXml = file_get_contents($path);
        $this->assertNotFalse($this->realXml, "real_nfse.xml não encontrado em $path");
    }

    public function test_parse_xml_returns_nfse_dto(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);

        $this->assertInstanceOf(NFSe::class, $nfse);
        $this->assertNotNull($nfse->infNFSe);
    }

    public function test_parsed_dto_fields_match_xml(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);

        $inf = $nfse->infNFSe;
        $this->assertNotNull($inf);
        $this->assertSame('10', $inf->nNFSe);
        $this->assertSame('Niterói', $inf->xLocEmi);

        $emit = $inf->emit;
        $this->assertNotNull($emit);
        $this->assertSame('11222333000181', $emit->CNPJ);
        $this->assertSame('EMPRESA EXEMPLO DESENVOLVIMENTO LTDA', $emit->xNome);

        $dps = $inf->DPS;
        $this->assertNotNull($dps);

        $infDps = $dps->infDPS;
        $this->assertNotNull($infDps);
        $this->assertSame('1', $infDps->tpAmb);
        $this->assertSame('5', $infDps->nDPS);
        $this->assertSame('2026-01-15', $infDps->dCompet);

        $toma = $infDps->toma;
        $this->assertNotNull($toma);
        $this->assertSame('91712343000134', $toma->CNPJ);
        $this->assertSame('CLIENTE FICTICIO COMERCIO S.A.', $toma->xNome);
    }

    public function test_generate_from_xml_returns_pdf_binary(): void
    {
        $generator = new DanfseGenerator();
        $pdf = $generator->generateFromXml($this->realXml);

        // Verifica assinatura do PDF (%PDF-)
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_generate_with_config(): void
    {
        $config = new DanfseConfig(
            municipality: new MunicipalityBranding(
                name: 'Prefeitura de Niterói',
                department: 'Secretaria Municipal de Fazenda',
                email: 'iss@fazenda.niteroi.rj.gov.br',
            ),
        );
        $generator = new DanfseGenerator($config);
        $pdf = $generator->generateFromXml($this->realXml);

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_two_step_generation(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);
        $pdf = $generator->generatePdf($nfse);

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_template_data_matches_expected(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);

        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        // Chave de acesso (sem prefixo NFS)
        $this->assertSame('3303302112233450000195000000000000100000000001', $data['chave_acesso']);

        // Emitente
        $this->assertSame('11.222.333/0001-81', $data['emitente']['cnpj_cpf']);
        $this->assertSame('EMPRESA EXEMPLO DESENVOLVIMENTO LTDA', $data['emitente']['nome']);
        $this->assertSame('Niterói - RJ', $data['emitente']['municipio']);
        $this->assertSame('24020-005', $data['emitente']['cep']);

        // Tomador
        $this->assertSame('91.712.343/0001-34', $data['tomador']['cnpj_cpf']);
        $this->assertSame('CLIENTE FICTICIO COMERCIO S.A.', $data['tomador']['nome']);

        // Serviço
        $this->assertSame('01.07.00', $data['servico']['codigo_trib_nacional']);

        // Totais
        $this->assertSame('R$ 1.500,00', $data['totais']['valor_servico']);
        $this->assertSame('R$ 1.292,75', $data['totais']['valor_liquido']);

        // Ambiente
        $this->assertSame(1, $data['ambiente']);

        // Tributação municipal
        $this->assertSame('Operação Tributável', $data['tributacao_municipal']['tributacao_issqn']);
        $this->assertSame('Retido pelo Tomador', $data['tributacao_municipal']['retencao_issqn']);
        $this->assertSame('Sociedade de Profissionais', $data['tributacao_municipal']['regime_especial']);
        $this->assertSame('Niterói', $data['tributacao_municipal']['municipio_incidencia']);

        // Emitente: Simples Nacional
        $this->assertSame(
            'Não Optante',
            $data['emitente']['simples_nacional'],
        );
    }

    public function test_homologacao_environment_flag(): void
    {
        // Substitui tpAmb=1 (produção) por tpAmb=2 (homologação)
        $xml = str_replace('<tpAmb>1</tpAmb>', '<tpAmb>2</tpAmb>', $this->realXml);

        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($xml);
        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        $this->assertSame(2, $data['ambiente']);
    }

    public function test_generate_pdf_size_is_reasonable(): void
    {
        $generator = new DanfseGenerator();
        $pdf = $generator->generateFromXml($this->realXml);

        // Um PDF de A4 válido deve ter pelo menos 1KB e no máximo ~5MB
        $size = strlen($pdf);
        $this->assertGreaterThan(1000, $size, 'PDF parece muito pequeno');
        $this->assertLessThan(5_000_000, $size, 'PDF parece muito grande');
    }
}
