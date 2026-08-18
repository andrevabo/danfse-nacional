<?php

namespace DanfseNacional\Tests;

use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\DanfseGenerator;
use DanfseNacional\Dto\NFSe;
use DanfseNacional\Enums\AmbGerador;
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

    public function test_parse_xml_retorna_nfse_dto(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);

        $this->assertInstanceOf(NFSe::class, $nfse);
        $this->assertNotNull($nfse->infNFSe);
    }

    public function test_campos_dto_parseado_correspondem_ao_xml(): void
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

    public function test_gera_binario_pdf_a_partir_do_xml(): void
    {
        $generator = new DanfseGenerator();
        $pdf = $generator->generateFromXml($this->realXml);

        // Verifica assinatura do PDF (%PDF-)
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_gera_com_config(): void
    {
        $config = new DanfseConfig(canceled: true);
        $generator = new DanfseGenerator($config);
        $pdf = $generator->generateFromXml($this->realXml);

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_geracao_em_duas_etapas(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);
        $pdf = $generator->generatePdf($nfse);

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_dados_do_template_correspondem_ao_esperado(): void
    {
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);

        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        // Chave de acesso (sem prefixo NFS)
        $this->assertSame('3303302112233450000195000000000000100000000001', $data['chave_acesso']);

        // Identificação
        $this->assertSame('Prestador', $data['tipo_emitente']);
        $this->assertSame('NFS-e Gerada', $data['situacao']);
        $this->assertSame('-', $data['finalidade']); // finNFSe ausente no XML v1.01

        $this->assertSame(AmbGerador::SISTEMA_NACIONAL->value, $data['amb_gerador']);

        // Prestador
        $this->assertSame('11.222.333/0001-81', $data['prestador']['cnpj_cpf']);
        $this->assertSame('EMPRESA EXEMPLO DESENVOLVIMENTO LTDA', $data['prestador']['nome']);
        $this->assertSame('Niterói / RJ', $data['prestador']['municipio']);
        $this->assertSame('987654', $data['prestador']['im']);
        $this->assertSame('33.03302 / 24.020-005', $data['prestador']['codigo_ibge_cep']);

        // Tomador
        $this->assertSame('91.712.343/0001-34', $data['tomador']['cnpj_cpf']);
        $this->assertSame('CLIENTE FICTICIO COMERCIO S.A.', $data['tomador']['nome']);
        $this->assertSame('35.50308 / 01.310-100', $data['tomador']['codigo_ibge_cep']);

        // Serviço
        $this->assertSame('01.07.00', $data['servico']['codigo_trib_nacional']);
        $this->assertSame('1.1081.10.00', $data['servico']['codigo_nbs']);

        // Tributação federal
        $this->assertSame('R$ 15,00', $data['tributacao_federal']['contrib_sociais']);
        $this->assertSame('PIS/COFINS Não Retido', $data['tributacao_federal']['desc_contrib_sociais']);

        // Totais
        $this->assertSame('R$ 1.500,00', $data['totais']['valor_servico']);
        $this->assertSame('R$ 1.292,75', $data['totais']['valor_liquido']);

        // Ambiente
        $this->assertSame(1, $data['ambiente']);

        // Tributação municipal
        $this->assertSame('Operação Tributável', $data['tributacao_municipal']['tributacao_issqn']);
        $this->assertSame('Retido pelo Tomador', $data['tributacao_municipal']['retencao_issqn']);
        $this->assertSame('Sociedade de Profissionais', $data['tributacao_municipal']['regime_especial']);
        $this->assertSame('Niterói / RJ / BR', $data['tributacao_municipal']['municipio_incidencia']);

        // Emitente: Simples Nacional
        $this->assertSame(
            'Não Optante',
            $data['prestador']['simples_nacional'],
        );
    }

    public function test_finalidade_exibida_quando_finNFSe_presente(): void
    {
        // Injeta o grupo IBS/CBS com finNFSe (modelo v2.0) no infDPS.
        $xml = str_replace(
            '<serv>',
            '<IBSCBS><finNFSe>0</finNFSe></IBSCBS><serv>',
            $this->realXml,
        );

        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($xml);
        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        $this->assertSame('NFS-e regular', $data['finalidade']);
    }

    public function test_destinatario_nao_identificado_sem_ibscbs(): void
    {
        // XML v1.01 sem grupo IBS/CBS: destinatário deve cair na mensagem padrão.
        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($this->realXml);
        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        $this->assertNull($data['destinatario']);
        $this->assertSame('DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $data['destinatario_msg']);
        $this->assertNull($data['ibs_cbs']);
    }

    public function test_ibscbs_campos_do_exemplo(): void
    {
        $path = __DIR__ . '/../examples/nfse_exemplo_ibscbs.xml';
        $xml = file_get_contents($path);
        $this->assertNotFalse($xml, "exemplo IBS/CBS não encontrado em $path");

        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($xml);
        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        // Finalidade (finNFSe) agora presente
        $this->assertSame('NFS-e regular', $data['finalidade']);

        // Destinatário identificado
        $this->assertNotNull($data['destinatario']);
        $this->assertSame('DESTINATARIO FINAL SERVICOS LTDA', $data['destinatario']['nome']);
        $this->assertSame('33.04557 / 20.040-002', $data['destinatario']['codigo_ibge_cep']);
        $this->assertSame('', $data['destinatario_msg']);

        // Bloco IBS/CBS
        $this->assertNotNull($data['ibs_cbs']);
        $this->assertSame('000 / 000001', $data['ibs_cbs']['cst_classtrib']);
        $this->assertSame('100000 / 3303302 / Niterói / RJ', $data['ibs_cbs']['indicador_operacao']);
        $this->assertSame('R$ 1.500,00', $data['ibs_cbs']['bc']);
        $this->assertSame('0.10% / 0.05%', $data['ibs_cbs']['aliquota_ibs']);
        $this->assertSame('R$ 0,75', $data['ibs_cbs']['valor_apurado_mun']);
        $this->assertSame('R$ 1,50', $data['ibs_cbs']['valor_apurado_uf']);
        $this->assertSame('R$ 2,25', $data['ibs_cbs']['valor_total_ibs']);
        $this->assertSame('R$ 13,50', $data['ibs_cbs']['valor_total_cbs']);
        // Exclusões = vDescIncond(100) + vISSQN(27) + vPis(9,75) + vCofins(45)
        $this->assertSame('R$ 181,75', $data['ibs_cbs']['exclusoes_reducoes']);

        // Totais com IBS/CBS
        $this->assertSame('R$ 15,75', $data['totais']['total_ibs_cbs']);
        $this->assertSame('R$ 1.308,50', $data['totais']['valor_liquido_ibscbs']);
    }

    public function test_ibscbs_exemplo_gera_pdf(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml');
        $pdf = (new DanfseGenerator())->generateFromXml($xml);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_prestador_prioriza_no_prest_sobre_emit(): void
    {
        $prestFull = '<prest>'
            . '<CNPJ>11222333000181</CNPJ>'
            . '<IM>111222</IM>'
            . '<xNome>PRESTADOR DECLARADO NA DPS LTDA</xNome>'
            . '<end><endNac><cMun>3304557</cMun><CEP>20040002</CEP></endNac>'
            . '<xLgr>Avenida Rio Branco</xLgr><nro>1</nro><xBairro>Centro</xBairro></end>'
            . '<fone>2199990000</fone>'
            . '<email>dps@prestador.com.br</email>'
            . '<regTrib><opSimpNac>1</opSimpNac><regEspTrib>6</regEspTrib></regTrib>'
            . '</prest>';
        $xml = preg_replace('#<prest>.*?</prest>#s', $prestFull, $this->realXml);

        $nfse = (new DanfseGenerator())->parseXml($xml);
        $data = (new \DanfseNacional\Template\DanfseTemplate())->buildData($nfse);

        $this->assertSame('PRESTADOR DECLARADO NA DPS LTDA', $data['prestador']['nome']);
        $this->assertSame('111222', $data['prestador']['im']);
        $this->assertSame('Rio de Janeiro / RJ', $data['prestador']['municipio']);
        $this->assertSame('33.04557 / 20.040-002', $data['prestador']['codigo_ibge_cep']);
        $this->assertSame('(21) 9999-0000', $data['prestador']['telefone']);
    }

    public function test_prestador_endereco_exterior(): void
    {
        $prestExt = '<prest>'
            . '<CNPJ>11222333000181</CNPJ>'
            . '<xNome>FOREIGN PROVIDER INC</xNome>'
            . '<end><endExt><cPais>2496</cPais><cEndPost>10001</cEndPost>'
            . '<xCidade>New York</xCidade><xEstProvReg>NY</xEstProvReg></endExt>'
            . '<xLgr>5th Avenue</xLgr><nro>100</nro><xBairro>Manhattan</xBairro></end>'
            . '<regTrib><opSimpNac>1</opSimpNac></regTrib>'
            . '</prest>';
        $xml = preg_replace('#<prest>.*?</prest>#s', $prestExt, $this->realXml);

        $nfse = (new DanfseGenerator())->parseXml($xml);
        $data = (new \DanfseNacional\Template\DanfseTemplate())->buildData($nfse);

        $this->assertSame('New York / NY', $data['prestador']['municipio']);
        $this->assertSame('10001', $data['prestador']['codigo_ibge_cep']);
        $this->assertSame('5th Avenue, 100, Manhattan', $data['prestador']['endereco']);
    }

    public function test_intermediario_im_lido_de_tag_IM(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml');
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml));

        $this->assertSame('123456', $data['intermediario']['im']);
    }

    public function test_tomador_com_nif(): void
    {
        $xmlNif = preg_replace(
            '#<toma>.*?</toma>#s',
            '<toma><NIF>X1234567890</NIF><xNome>TOMADOR EXTERIOR</xNome>'
            . '<email>t@ext.com</email></toma>',
            $this->realXml,
        );

        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xmlNif));

        // NIF alfanumérico não é mutilado pelo formatter
        $this->assertSame('X1234567890', $data['tomador']['cnpj_cpf']);
        $this->assertSame('TOMADOR EXTERIOR', $data['tomador']['nome']);
    }

    public function test_nota6_pis_cofins_suprimidos_apos_2026(): void
    {
        $generator = new DanfseGenerator();
        $template = new \DanfseNacional\Template\DanfseTemplate();

        // Competência em 2026 → PIS/COFINS/Descrição visíveis
        $data2026 = $template->buildData($generator->parseXml($this->realXml));
        $this->assertTrue($data2026['tributacao_federal']['mostrar_pis_cofins']);
        $html2026 = $generator->generateHtml($generator->parseXml($this->realXml));
        $this->assertStringContainsString('PIS - Débito Apuração Própria', $html2026);

        // Competência em 2027 → linha suprimida (Nota 6)
        $xml2027 = str_replace('<dCompet>2026-01-15</dCompet>', '<dCompet>2027-03-10</dCompet>', $this->realXml);
        $data2027 = $template->buildData($generator->parseXml($xml2027));
        $this->assertFalse($data2027['tributacao_federal']['mostrar_pis_cofins']);
        $html2027 = $generator->generateHtml($generator->parseXml($xml2027));
        $this->assertStringNotContainsString('PIS - Débito Apuração Própria', $html2027);
        $this->assertStringNotContainsString('Descrição Contrib. Sociais - Retidas', $html2027);
    }

    public function test_flag_ambiente_homologacao(): void
    {
        // Substitui tpAmb=1 (produção) por tpAmb=2 (homologação)
        $xml = str_replace('<tpAmb>1</tpAmb>', '<tpAmb>2</tpAmb>', $this->realXml);

        $generator = new DanfseGenerator();
        $nfse = $generator->parseXml($xml);
        $template = new \DanfseNacional\Template\DanfseTemplate();
        $data = $template->buildData($nfse);

        $this->assertSame(2, $data['ambiente']);
    }

    public function test_tamanho_do_pdf_gerado_e_razoavel(): void
    {
        $generator = new DanfseGenerator();
        $pdf = $generator->generateFromXml($this->realXml);

        // Um PDF de A4 válido deve ter pelo menos 1KB e no máximo ~5MB
        $size = strlen($pdf);
        $this->assertGreaterThan(1000, $size, 'PDF parece muito pequeno');
        $this->assertLessThan(5_000_000, $size, 'PDF parece muito grande');
    }

    // ── Campos de identificação e cabeçalho ──────────────────────────────────

    public function test_campos_identificacao(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('10', $data['numero_nfse']);
        $this->assertSame('15/01/2026', $data['competencia']);
        $this->assertSame('5', $data['numero_dps']);
        $this->assertSame('20261', $data['serie_dps']);
        $this->assertStringContainsString('15/01/2026', $data['emissao_dps']);
        $this->assertStringContainsString('15/01/2026', $data['emissao_nfse']);
    }

    // ── Campos de prestador e tomador não cobertos ────────────────────────────

    public function test_prestador_email_e_regime_sn(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('financeiro@empresaexemplo.com.br', $data['prestador']['email']);
        // Regime SN = 2 (Regime de Caixa)
        $this->assertNotEmpty($data['prestador']['regime_sn']);
    }

    public function test_tomador_campos_secundarios(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('654321', $data['tomador']['im']);
        $this->assertSame('(11) 98765-4321', $data['tomador']['telefone']);
        $this->assertSame('contato@clienteficticio.com.br', $data['tomador']['email']);
        $this->assertStringContainsString('Avenida Paulista', $data['tomador']['endereco']);
        $this->assertSame('São Paulo / SP', $data['tomador']['municipio']);
    }

    // ── Serviço ───────────────────────────────────────────────────────────────

    public function test_servico_campos_secundarios(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('007', $data['servico']['codigo_trib_municipal']);
        $this->assertStringContainsString('Niterói', $data['servico']['local_prestacao']);
        $this->assertStringContainsString('Desenvolvimento de sistema', $data['servico']['descricao']);
        $this->assertStringContainsString('Desenvolvimento e licenciamento', $data['servico']['desc_trib']);
    }

    // ── Tributação federal ────────────────────────────────────────────────────

    public function test_tributacao_federal_retencoes(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('R$ 22,50', $data['tributacao_federal']['irrf']);
        $this->assertSame('R$ 15,00', $data['tributacao_federal']['cp']);
        $this->assertSame('R$ 9,75', $data['tributacao_federal']['pis']);
        $this->assertSame('R$ 45,00', $data['tributacao_federal']['cofins']);

        // tpRetPisCofins = 2 (Não Retido): contrib_sociais reflete apenas vRetCSLL
        $this->assertSame('R$ 15,00', $data['tributacao_federal']['contrib_sociais']);
    }

    public function test_tributacao_federal_pis_cofins_retido(): void
    {
        // tpRetPisCofins = 1 (PIS/COFINS Retido):
        // - contrib_sociais = vRetCSLL + vPis + vCofins
        // - pis e cofins (débito apuração própria) retornam 0,00
        $xml = str_replace('<tpRetPisCofins>2</tpRetPisCofins>', '<tpRetPisCofins>1</tpRetPisCofins>', $this->realXml);

        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml));

        // CSLL(15,00) + PIS(9,75) + COFINS(45,00) = 69,75
        $this->assertSame('R$ 69,75', $data['tributacao_federal']['contrib_sociais']);
        $this->assertSame('R$ 0,00', $data['tributacao_federal']['pis']);
        $this->assertSame('R$ 0,00', $data['tributacao_federal']['cofins']);
        $this->assertSame('PIS/COFINS Retido', $data['tributacao_federal']['desc_contrib_sociais']);
    }

    public function test_tributacao_federal_pis_cofins_nao_retido_explicito(): void
    {
        // tpRetPisCofins = 2 (Não Retido) já é o padrão do XML de exemplo, mas testamos
        // explicitamente a árvore de decisão simétrica ao caso retido.
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('R$ 15,00', $data['tributacao_federal']['contrib_sociais']);
        $this->assertSame('R$ 9,75', $data['tributacao_federal']['pis']);
        $this->assertSame('R$ 45,00', $data['tributacao_federal']['cofins']);
        $this->assertSame('PIS/COFINS Não Retido', $data['tributacao_federal']['desc_contrib_sociais']);
    }

    // ── Totais ────────────────────────────────────────────────────────────────

    public function test_totais_descontos_e_retencoes(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('R$ 50,00', $data['totais']['desconto_condicionado']);
        $this->assertSame('R$ 100,00', $data['totais']['desconto_incondicionado']);
        // IRRF(22,50) + CP(15,00) + CSLL(15,00) = 52,50
        $this->assertSame('R$ 52,50', $data['totais']['retencoes_federais']);
    }

    // ── IBS/CBS campos não cobertos ───────────────────────────────────────────

    public function test_ibs_cbs_aliquotas_efetivas(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml');
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml));

        $this->assertSame('0.05%', $data['ibs_cbs']['aliq_efetiva_mun']);
        $this->assertSame('0.10%', $data['ibs_cbs']['aliq_efetiva_uf']);
        $this->assertSame('0.90%', $data['ibs_cbs']['aliquota_cbs']);
        $this->assertSame('0.90%', $data['ibs_cbs']['aliq_efetiva_cbs']);
        // Sem reduções → todos '-' (pctList retorna '-' sem sufixo % em valores vazios)
        $this->assertSame('- / - / -', $data['ibs_cbs']['red_aliquota']);
    }

    // ── INFORMAÇÕES COMPLEMENTARES ────────────────────────────────────────────

    public function test_informacoes_complementares_com_xinf_e_ptottrib(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $ic = $data['informacoes_complementares'];

        $this->assertStringContainsString('Inf. Cont.: Referente ao contrato', $ic);
        $this->assertStringContainsString(
            'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: 4.50%; Estaduais: 0.10%; Municipais: 2.00%',
            $ic,
        );
        // Campos ausentes não devem aparecer
        $this->assertStringNotContainsString('NFS-e Subst.:', $ic);
        $this->assertStringNotContainsString('Cod. Obra:', $ic);
    }

    public function test_informacoes_complementares_sem_infocompl_tributos_incompletos(): void
    {
        $xml = preg_replace('#\s*<infoCompl>.*?</infoCompl>#s', '', $this->realXml);
        $xml = preg_replace('#<pTotTrib>.*?</pTotTrib>#s', '<pTotTribSN>13.21</pTotTribSN>', $xml);
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml));

        $this->assertSame(
            'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: -; Estaduais: -; Municipais: -',
            $data['informacoes_complementares'],
        );
    }

    public function test_informacoes_complementares_com_vtottrib_monetario(): void
    {
        $xml = preg_replace(
            '#<pTotTrib>.*?</pTotTrib>#s',
            '<vTotTrib><vTotTribFed>67.50</vTotTribFed><vTotTribEst>1.50</vTotTribEst><vTotTribMu>30.00</vTotTribMu></vTotTrib>',
            $this->realXml,
        );

        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml));

        $this->assertStringContainsString(
            'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: R$ 67,50; Estaduais: R$ 1,50; Municipais: R$ 30,00',
            $data['informacoes_complementares'],
        );
    }

    public function test_informacoes_complementares_todos_campos_nt008(): void
    {
        // Injeta todos os campos opcionais de informações complementares
        $xml = str_replace(
            '<infoCompl>' . "\n" . '                        <xInfComp>Referente ao contrato',
            '<infoCompl>' . "\n" . '                        <xInfComp>Texto livre</xInfComp>'
                . '<docRef>DOC-2026-001</docRef>'
                . '<idDocTec>TEC-123</idDocTec>'
                . '<xPed>PED-456</xPed>'
                . '<gItemPed><xItemPed>ITEM-789</xItemPed></gItemPed>'
                . '<xInfCompIgnored>',
            $this->realXml,
        );
        // Injeta subst no infDPS
        $xml = str_replace(
            '<tpAmb>1</tpAmb>',
            '<tpAmb>1</tpAmb><subst><chSubstda>NFSe-CHAVE-SUBSTITUIDA-000001</chSubstda></subst>',
            $xml,
        );
        // Injeta obra no serv
        $xml = str_replace(
            '<locPrest>',
            '<obra><cObra>OBRA-001</cObra></obra><locPrest>',
            $xml,
        );
        // Injeta atvEvento no serv
        $xml = str_replace(
            '<cServ>',
            '<atvEvento><idAtvEvt>EVT-2026-001</idAtvEvt></atvEvento><cServ>',
            $xml,
        );
        // Injeta imovel no IBSCBS da DPS
        $xml = str_replace(
            '<IBSCBS>' . "\n" . '                    <finNFSe>',
            '<IBSCBS>' . "\n" . '                    <imovel><inscImobFisc>IMOB-12345</inscImobFisc></imovel>' . "\n" . '                    <finNFSe>',
            file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml'),
        );
        // Injeta xOutInf no infNFSe
        $xml2 = str_replace(
            '<nNFSe>',
            '<xOutInf>Texto adicional municipio</xOutInf><nNFSe>',
            file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml'),
        );

        // Testa cada campo individualmente com XMLs limpos de injeção
        $gen = new DanfseGenerator();
        $tmpl = new \DanfseNacional\Template\DanfseTemplate();

        // subst/chSubstda
        $xmlSubst = str_replace(
            '<tpAmb>1</tpAmb>',
            '<tpAmb>1</tpAmb><subst><chSubstda>CHAVE-SUBST-001</chSubstda></subst>',
            $this->realXml,
        );
        $ic = $tmpl->buildData($gen->parseXml($xmlSubst))['informacoes_complementares'];
        $this->assertStringContainsString('NFS-e Subst.: CHAVE-SUBST-001', $ic);

        // serv/obra/cObra
        $xmlObra = str_replace('<locPrest>', '<obra><cObra>OBRA-2026</cObra></obra><locPrest>', $this->realXml);
        $ic = $tmpl->buildData($gen->parseXml($xmlObra))['informacoes_complementares'];
        $this->assertStringContainsString('Cod. Obra: OBRA-2026', $ic);

        // serv/atvEvento/idAtvEvt
        $xmlEvt = str_replace('<cServ>', '<atvEvento><idAtvEvt>EVT-001</idAtvEvt></atvEvento><cServ>', $this->realXml);
        $ic = $tmpl->buildData($gen->parseXml($xmlEvt))['informacoes_complementares'];
        $this->assertStringContainsString('Cod. Evt.: EVT-001', $ic);

        // infoCompl/docRef, idDocTec, xPed, gItemPed/xItemPed
        $xmlExtra = str_replace(
            '<xInfComp>',
            '<docRef>DOC-REF-001</docRef><idDocTec>DOC-TEC-001</idDocTec>'
                . '<xPed>PED-001</xPed><gItemPed><xItemPed>ITEM-001</xItemPed></gItemPed><xInfComp>',
            $this->realXml,
        );
        $ic = $tmpl->buildData($gen->parseXml($xmlExtra))['informacoes_complementares'];
        $this->assertStringContainsString('Doc. Ref.: DOC-REF-001', $ic);
        $this->assertStringContainsString('Doc. Tec.: DOC-TEC-001', $ic);
        $this->assertStringContainsString('Núm. Ped.: PED-001', $ic);
        $this->assertStringContainsString('Item Ped.: ITEM-001', $ic);

        // IBSCBS/imovel/inscImobFisc
        $xmlImovel = str_replace(
            '<finNFSe>0</finNFSe>',
            '<finNFSe>0</finNFSe><imovel><inscImobFisc>IMOB-001</inscImobFisc></imovel>',
            file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml'),
        );
        $ic = $tmpl->buildData($gen->parseXml($xmlImovel))['informacoes_complementares'];
        $this->assertStringContainsString('Insc. Imob.: IMOB-001', $ic);

        // infNFSe/xOutInf
        $xmlOutInf = str_replace(
            '<nNFSe>10</nNFSe>',
            '<nNFSe>10</nNFSe><xOutInf>Info adicional municipio</xOutInf>',
            file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml'),
        );
        $ic = $tmpl->buildData($gen->parseXml($xmlOutInf))['informacoes_complementares'];
        $this->assertStringContainsString('Inf. A. T. Mun.: Info adicional municipio', $ic);
    }

    public function test_informacoes_complementares_ordem_e_separadores(): void
    {
        // Injeta subst + xInfComp para verificar a ordem e o separador " | "
        $xml = str_replace(
            '<tpAmb>1</tpAmb>',
            '<tpAmb>1</tpAmb><subst><chSubstda>CHAVE-999</chSubstda></subst>',
            $this->realXml,
        );

        $ic = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml))['informacoes_complementares'];

        // Inf. Cont. deve aparecer antes de NFS-e Subst.
        $posInf = strpos($ic, 'Inf. Cont.:');
        $posSubst = strpos($ic, 'NFS-e Subst.:');
        $posTrib = strpos($ic, 'Totais Aproximados');
        $this->assertLessThan($posSubst, $posInf, 'Inf. Cont. deve preceder NFS-e Subst.');
        $this->assertLessThan($posTrib, $posSubst, 'NFS-e Subst. deve preceder linha de tributos');

        // Separador pipe
        $this->assertStringContainsString(' | ', $ic);
    }

    public function test_informacoes_complementares_truncamento_2000(): void
    {
        // xInfComp com 2100 chars → corpo deve ser truncado em 2000 com "..."
        $longo = str_repeat('A', 2100);
        $xml = str_replace(
            '<xInfComp>Referente',
            '<xInfComp>' . $longo . 'X',
            $this->realXml,
        );

        $ic = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml))['informacoes_complementares'];

        // O corpo (antes do tributos) deve estar truncado
        $this->assertStringContainsString('...', $ic);
        // A linha de tributos sempre aparece, mesmo após truncamento
        $this->assertStringContainsString('Totais Aproximados dos Tributos', $ic);
    }

    // ── HTML: blocos condicionais e renderização ──────────────────────────────

    public function test_html_watermark_cancelada(): void
    {
        $config = new DanfseConfig(canceled: true);
        $html = (new DanfseGenerator($config))->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringContainsString('class="watermark"', $html);
        $this->assertStringContainsString('CANCELADA', $html);
    }

    public function test_html_watermark_substituida(): void
    {
        $config = new DanfseConfig(substituted: true);
        $html = (new DanfseGenerator($config))->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringContainsString('class="watermark"', $html);
        $this->assertStringContainsString('SUBSTITUÍDA', $html);
    }

    public function test_html_sem_watermark_em_nota_normal(): void
    {
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringNotContainsString('class="watermark"', $html);
    }

    public function test_html_homologacao_exibe_aviso_sem_validade(): void
    {
        $xml = str_replace('<tpAmb>1</tpAmb>', '<tpAmb>2</tpAmb>', $this->realXml);
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringContainsString('NFS-e SEM VALIDADE JURÍDICA', $html);
    }

    public function test_html_producao_nao_exibe_aviso_sem_validade(): void
    {
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringNotContainsString('NFS-e SEM VALIDADE JURÍDICA', $html);
        $this->assertStringContainsString('Tipo de Ambiente: 1', $html);
    }

    public function test_html_destinatario_identificado_renderiza_bloco(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml');
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringContainsString('DESTINATÁRIO DA OPERAÇÃO</span>', $html);
        $this->assertStringContainsString('DESTINATARIO FINAL SERVICOS LTDA', $html);
        $this->assertStringNotContainsString('DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO', $html);
    }

    public function test_html_destinatario_nao_identificado_exibe_mensagem(): void
    {
        // realXml não tem IBS/CBS → destinatário não identificado
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringContainsString('DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $html);
        $this->assertStringNotContainsString('DESTINATÁRIO DA OPERAÇÃO</span>', $html);
    }

    public function test_html_destinatario_proprio_tomador_exibe_mensagem(): void
    {
        $xml = str_replace(
            '<indDest>1</indDest>',
            '<indDest>0</indDest>',
            file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml'),
        );
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringContainsString('O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO', $html);
        $this->assertStringNotContainsString('DESTINATÁRIO DA OPERAÇÃO</span>', $html);
    }

    public function test_html_intermediario_renderiza_quando_presente(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml');
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringContainsString('INTERMEDIÁRIO DA OPERAÇÃO', $html);
        $this->assertStringContainsString('INTERMEDIARIO FICTICIO LTDA', $html);
    }

    public function test_html_intermediario_ausente_quando_nao_informado(): void
    {
        $xml = preg_replace('#\s*<interm>.*?</interm>#s', '', $this->realXml);
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringContainsString('INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $html);
    }

    public function test_html_bloco_ibscbs_renderiza_quando_presente(): void
    {
        $xml = file_get_contents(__DIR__ . '/../examples/nfse_exemplo_ibscbs.xml');
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringContainsString('TRIBUTAÇÃO IBS / CBS', $html);
        $this->assertStringContainsString('R$ 1.500,00', $html); // BC
    }

    public function test_html_bloco_ibscbs_ausente_quando_nao_informado(): void
    {
        // realXml não tem IBS/CBS
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringNotContainsString('TRIBUTAÇÃO IBS/CBS', $html);
    }

    public function test_html_informacoes_complementares_renderizadas(): void
    {
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringContainsString('INFORMAÇÕES COMPLEMENTARES', $html);
        $this->assertStringContainsString('Inf. Cont.:', $html);
        $this->assertStringContainsString('Totais Aproximados dos Tributos', $html);
    }

    // ── Identificação do município ────────────────────────────────────────────

    public function test_mostrar_municipio_true_quando_cod_trib_nac_nao_comeca_com_99(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertTrue($data['mostrar_municipio']);
        $this->assertSame('Niterói', $data['municipio_emissor']['nome']);
        $this->assertSame('RJ', $data['municipio_emissor']['uf']);
    }

    public function test_mostrar_municipio_false_quando_cod_trib_nac_comeca_com_99(): void
    {
        $xml = str_replace('<cTribNac>010700</cTribNac>', '<cTribNac>990201</cTribNac>', $this->realXml);
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($xml));

        $this->assertFalse($data['mostrar_municipio']);
    }

    public function test_html_municipio_exibido_a_partir_do_xml(): void
    {
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($this->realXml)
        );

        $this->assertStringContainsString('Município: Niterói / RJ', $html);
    }

    public function test_html_municipio_oculto_quando_cod_trib_nac_comeca_com_99(): void
    {
        $xml = str_replace('<cTribNac>010700</cTribNac>', '<cTribNac>990101</cTribNac>', $this->realXml);
        $html = (new DanfseGenerator())->generateHtml(
            (new DanfseGenerator())->parseXml($xml)
        );

        $this->assertStringNotContainsString('Município: Niterói / RJ', $html);
    }

    // ── Tributação municipal apurada pelo município (NT 008/2026) ─────────────

    /**
     * A NT 008/2026 define a origem destes campos no XML:
     *
     *   BC ISSQN           NFSe/infNFSe/valores/   vBC
     *   ALÍQUOTA APLICADA  NFSe/infNFSe/valores/   pAliqAplic
     *   ISSQN APURADO      NFSe/infNFSe/valores/   vISSQN
     *
     * (Apenas RETENÇÃO DO ISSQN vem de NFSe/infNFSe/DPS/infDPS/valores/trib/tribMun/.)
     *
     * Os dois XML de exemplo do repositório trazem vBC/vISSQN dentro do `tribMun` do
     * DPS e um `infNFSe/valores` com apenas `vLiq`, então a leitura pelo `tribMun`
     * funciona para eles. Em NFS-e em que o MUNICÍPIO apura o imposto — caso comum em
     * São Paulo, por exemplo — o `tribMun` declara somente `pAliq`, e os valores
     * apurados ficam em `infNFSe/valores`. Nesse cenário o DANFSe saía com "-" na base
     * de cálculo e no ISSQN apurado.
     *
     * @see https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica/rtc/nt-008-se-cgnfse-danfse-20260505.pdf
     */
    public function test_issqn_apurado_pelo_municipio_vem_de_infnfse_valores(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertSame('R$ 1.350,00', $data['tributacao_municipal']['bc_issqn']);
        $this->assertSame('2.00%', $data['tributacao_municipal']['aliquota']);
        $this->assertSame('R$ 27,00', $data['tributacao_municipal']['issqn_apurado']);
    }

    /**
     * A retenção continua vindo do `tribMun` do DPS, como manda a norma.
     */
    public function test_retencao_issqn_continua_vindo_do_tribmun_do_dps(): void
    {
        $data = (new \DanfseNacional\Template\DanfseTemplate())
            ->buildData((new DanfseGenerator())->parseXml($this->realXml));

        $this->assertNotSame('-', $data['tributacao_municipal']['retencao_issqn']);
    }

    /**
     * `serv/locPrest` é opcional no XML. A leitura era feita sem null-safe
     * (`$locPrest->cLocPrestacao`), diferente da expressão vizinha
     * (`$locPrest?->cPaisPrestacao`), então um XML sem o bloco emitia
     * "Attempt to read property on null".
     *
     * Em aplicações Laravel isso não é apenas um aviso: o handler de erros converte
     * PHP Warning em ErrorException, e a geração do DANFSe falhava por completo.
     */
    public function test_xml_sem_locprest_nao_emite_warning(): void
    {
        $xml = preg_replace('#\s*<locPrest>.*?</locPrest>#s', '', $this->realXml);

        set_error_handler(static function (int $severity, string $message): bool {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $pdf = (new DanfseGenerator())->generateFromXml($xml);
        } finally {
            restore_error_handler();
        }

        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
