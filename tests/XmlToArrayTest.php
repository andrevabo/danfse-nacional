<?php

namespace DanfseNacional\Tests;

use DanfseNacional\XmlToArray;
use PHPUnit\Framework\TestCase;

class XmlToArrayTest extends TestCase
{
    private XmlToArray $converter;

    protected function setUp(): void
    {
        $this->converter = new XmlToArray();
    }

    public function test_converts_simple_xml(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<root xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<child>value</child>'
            . '</root>';

        $result = $this->converter->convert($xml);
        $this->assertSame('value', $result['child']);
    }

    public function test_extracts_attributes(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<root xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">'
            . '<child attr="test">value</child>'
            . '</root>';

        $result = $this->converter->convert($xml);
        $this->assertSame('1.01', $result['versao']);
    }

    public function test_excludes_signature_elements(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<root xmlns="http://www.sped.fazenda.gov.br/nfse">'
            . '<data>value</data>'
            . '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo/></Signature>'
            . '</root>';

        $result = $this->converter->convert($xml);
        $this->assertSame('value', $result['data']);
        $this->assertArrayNotHasKey('Signature', $result);
    }

    public function test_parses_real_nfse_xml(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo.xml');
        $this->assertNotFalse($xml);

        $result = $this->converter->convert($xml);

        $this->assertArrayHasKey('infNFSe', $result);
        $inf = $result['infNFSe'];

        $this->assertSame('NFS3303302112233450000195000000000000100000000001', $inf['Id']);
        $this->assertSame('10', $inf['nNFSe']);
        $this->assertSame('Niterói', $inf['xLocEmi']);
        $this->assertSame('11222333000181', $inf['emit']['CNPJ']);
        $this->assertSame('24020005', $inf['emit']['enderNac']['CEP']);

        // DPS aninhado
        $this->assertArrayHasKey('DPS', $inf);
        $this->assertSame('1', $inf['DPS']['infDPS']['tpAmb']);
        $this->assertSame('5', $inf['DPS']['infDPS']['nDPS']);
        $this->assertSame('11222333000181', $inf['DPS']['infDPS']['prest']['CNPJ']);
        $this->assertSame('91712343000134', $inf['DPS']['infDPS']['toma']['CNPJ']);
    }
}
