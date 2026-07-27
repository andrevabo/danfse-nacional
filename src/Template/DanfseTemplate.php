<?php

namespace DanfseNacional\Template;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\Dto\NFSe;
use DanfseNacional\Enums\OpSimpNac;
use DanfseNacional\Enums\RegApTribSN;
use DanfseNacional\Enums\RegEspTrib;
use DanfseNacional\Enums\TpRetISSQN;
use DanfseNacional\Enums\TribISSQN;
use DanfseNacional\Enums\TpRetPisCofins;
use DanfseNacional\Data\Municipios;
use DanfseNacional\Data\BeneficiosMunicipais;
use DanfseNacional\Formatter;

/**
 * Constrói o array de dados para o template e gera o QR Code.
 */
class DanfseTemplate
{
    private Formatter $fmt;

    public function __construct()
    {
        $this->fmt = new Formatter();
    }

    /**
     * Renderiza o template e retorna o HTML completo
     */
    public function render(NFSe $nfse, DanfseConfig $config): string
    {
        $data = $this->buildData($nfse);
        $logo = $config->logoDataUri;
        $municipality = $config->municipality;
        $qrCode = $this->generateQrCode($data['chave_acesso']);
        array_walk_recursive($data, fn(&$v) => $v = is_string($v) ? htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $v);

        $templatePath = __DIR__ . '/danfse.php';

        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Constrói o array de dados para o template a partir dos DTOs
     */
    public function buildData(NFSe $nfse): array
    {
        $inf = $nfse->infNFSe;
        $dps = $inf?->DPS;
        $infDps = $dps?->infDPS;
        $prest = $infDps?->prest;
        $regTrib = $prest?->regTrib;
        $emit = $inf?->emit;
        $enderEmit = $emit?->enderNac;
        $toma = $infDps?->toma;
        $endToma = $toma?->end;
        $interm = $infDps?->interm;
        $endInterm = $interm?->end;
        $serv = $infDps?->serv;
        $locPrest = $serv?->locPrest;
        $cServ = $serv?->cServ;
        $valores = $infDps?->valores;
        $vServPrest = $valores?->vServPrest;
        $trib = $valores?->trib;
        $tribMun = $trib?->tribMun;
        $tribFed = $trib?->tribFed;
        $totTrib = $trib?->totTrib;
        $valoresNfse = $inf?->valores;
        $vTotTrib = $totTrib?->vTotTrib;

        // Chave de acesso (remove prefixo "NFS")
        $id = $inf?->Id ?? '';
        $chaveAcesso = str_starts_with($id, 'NFS') ? substr($id, 3) : $id;

        // Endereço emitente
        $enderecoEmit = implode(', ', array_filter([
            $enderEmit?->xLgr ?? '',
            $enderEmit?->nro ?? '',
            $enderEmit?->xBairro ?? '',
        ], fn($v) => $v !== ''));

        $municipioEmit = '';
        if (($inf?->xLocEmi ?? '') !== '' && ($enderEmit?->UF ?? '') !== '') {
            $municipioEmit = ($inf->xLocEmi) . ' - ' . $enderEmit->UF;
        }

        // Endereço tomador
        $enderecoToma = implode(', ', array_filter([
            $endToma?->xLgr ?? '',
            $endToma?->nro ?? '',
            $endToma?->xBairro ?? '',
        ], fn($v) => $v !== ''));

        $cepToma = $endToma?->endNac?->CEP ?? '';

        // Endereço intermediário
        $enderecoInterm = implode(', ', array_filter([
            $endInterm?->xLgr ?? '',
            $endInterm?->nro ?? '',
            $endInterm?->xBairro ?? '',
        ], fn($v) => $v !== ''));

        $cepInterm = $endInterm?->endNac?->CEP ?? '';

        return [
            'chave_acesso' => $chaveAcesso,
            'numero_nfse' => $inf?->nNFSe ?? '-',
            'competencia' => $this->fmt->date($infDps?->dCompet ?? ''),
            'emissao_nfse' => $this->fmt->dateTime($inf?->dhProc ?? ''),
            'numero_dps' => $infDps?->nDPS ?? '-',
            'serie_dps' => $infDps?->serie ?? '-',
            'emissao_dps' => $this->fmt->dateTime($infDps?->dhEmi ?? ''),
            'ambiente' => (int) ($infDps?->tpAmb ?? 1),

            'emitente' => [
                'nome' => $emit?->xNome ?? '-',
                'cnpj_cpf' => $this->fmt->cnpjCpf($emit?->documento() ?? ''),
                'im' => '-',
                'telefone' => $this->fmt->phone($emit?->fone ?? ''),
                'email' => strtolower($emit?->email ?? '–'),
                'endereco' => $enderecoEmit ?: '-',
                'municipio' => $municipioEmit ?: '-',
                'cep' => $this->fmt->cep($enderEmit?->CEP ?? ''),
                'simples_nacional' => OpSimpNac::labelFor($regTrib?->opSimpNac ?? ''),
                'regime_sn' => RegApTribSN::labelFor($regTrib?->regApTribSN ?? ''),
            ],

            'tomador' => [
                'nome' => $toma?->xNome ?? '-',
                'cnpj_cpf' => $this->fmt->cnpjCpf($toma?->documento() ?? ''),
                'im' => $toma?->IM ?: '-',
                'telefone' => $this->fmt->phone($toma?->fone ?? ''),
                'email' => strtolower($toma?->email ?? '–'),
                'endereco' => $enderecoToma ?: '-',
                'municipio' => $endToma?->endNac?->cMun ? Municipios::lookup($endToma->endNac->cMun) : '-',
                'cep' => $this->fmt->cep($cepToma),
            ],

            'intermediario' => $interm !== null ? [
                'nome' => $interm->xNome ?: '-',
                'cnpj_cpf' => $this->fmt->cnpjCpf($interm->documento()),
                'im' => $interm->IMPrestMun ?: '-',
                'telefone' => $this->fmt->phone($interm->fone),
                'email' => strtolower($interm->email ?? '–'),
                'endereco' => $enderecoInterm ?: '-',
                'municipio' => $endInterm?->endNac?->cMun ? Municipios::lookup($endInterm->endNac->cMun) : '-',
                'cep' => $this->fmt->cep($cepInterm),
            ] : null,

            'servico' => [
                'codigo_trib_nacional' => $this->fmt->codTribNacional($cServ?->cTribNac ?? ''),
                'desc_trib_nacional' => $this->fmt->limit(trim($inf?->xTribNac ?? ''), 60),
                'codigo_trib_municipal' => $cServ?->cTribMun ?? '-',
                'desc_trib_municipal' => $this->fmt->limit(trim($inf?->xTribMun ?? ''), 60),
                'local_prestacao' => $locPrest?->cLocPrestacao ? Municipios::lookup($locPrest?->cLocPrestacao) : '-',
                'pais_prestacao' => $locPrest?->cPaisPrestacao ?? '-',
                'descricao' => $cServ?->xDescServ ?? '-',
            ],

            'tributacao_municipal' => [
                'tributacao_issqn' => TribISSQN::labelFor($tribMun?->tribISSQN ?? ''),
                'municipio_incidencia' => $inf?->cLocIncid ? Municipios::lookup($inf?->cLocIncid) : '-',
                'regime_especial' => RegEspTrib::labelFor($regTrib?->regEspTrib ?? ''),
                'valor_servico' => $this->fmt->currency($vServPrest?->vServ ?? ''),
                'bc_issqn' =>  $this->fmt->currency($valoresNfse?->vBC ?? $tribMun?->vBC ?? null),
                'aliquota' => $this->fmt->percentage($valoresNfse?->pAliqAplic ?? $tribMun?->pAliq ?? null),
                'retencao_issqn' => TpRetISSQN::labelFor($tribMun?->tpRetISSQN ?? ''),
                'issqn_apurado' => $this->fmt->currency($valoresNfse?->vISSQN ?? $tribMun?->vISSQN ?? null),
                // 'beneficio_municipal' => BeneficiosMunicipais::lookup(
                //     $inf?->cLocIncid ?? $locPrest?->cLocPrestacao,
                //     $tribMun->BM?->nBM ?? null
                // ),
                'beneficio_municipal' => $tribMun->BM?->nBM ?? null,
            ],

            'tributacao_federal' => [
                'irrf' => $tribFed?->vRetIRRF ? $this->fmt->currency($tribFed->vRetIRRF) : '-',
                'cp' => $tribFed?->vRetCP ? $this->fmt->currency($tribFed->vRetCP) : '-',
                'csll' => $tribFed?->vRetCSLL ? $this->fmt->currency($tribFed->vRetCSLL) : '-',
                'desc_csll' => TpRetPisCofins::labelFor($tribFed?->piscofins?->tpRetPisCofins ?? ''),
                'pis' => $tribFed?->piscofins?->vPis ? $this->fmt->currency($tribFed->piscofins->vPis) : '-',
                'cofins' => $tribFed?->piscofins?->vCofins ? $this->fmt->currency($tribFed->piscofins->vCofins) : '-',
            ],

            'totais' => [
                'valor_servico' => $this->fmt->currency($vServPrest?->vServ ?? ''),
                'desconto_condicionado' => $tribMun?->vDescCond ? $this->fmt->currency($tribMun->vDescCond) : '-',
                'desconto_incondicionado' => $tribMun?->vDescIncond ? $this->fmt->currency($tribMun->vDescIncond) : '-',
                'issqn_retido' => ($valoresNfse?->vISSQN && ($tribMun?->tpRetISSQN ?? '1') !== '1') ? $this->fmt->currency($valoresNfse?->vISSQN) : '-',
                'retencoes_federais' => $this->sumCurrency(
                    $tribFed?->vRetIRRF ?? '',
                    $tribFed?->vRetCP ?? '',
                    $tribFed?->vRetCSLL ?? '',
                ),
                'pis_cofins' => $this->sumCurrency(
                    $tribFed?->piscofins?->vPis ?? '',
                    $tribFed?->piscofins?->vCofins ?? '',
                ),
                'valor_liquido' => $this->fmt->currency($valoresNfse?->vLiq ?? ''),
            ],

            'totais_tributos' => [
                'federais' => $vTotTrib?->vTotTribFed ? $this->fmt->currency($vTotTrib->vTotTribFed) : '-',
                'estaduais' => $vTotTrib?->vTotTribEst ? $this->fmt->currency($vTotTrib->vTotTribEst) : '-',
                'municipais' => $vTotTrib?->vTotTribMun ? $this->fmt->currency($vTotTrib->vTotTribMun) : '-',
            ],

            'informacoes_complementares' => $serv?->infoCompl?->xInfComp ?? '',
            'c_nbs' => $cServ?->cNBS ?? '–',
        ];
    }

    /**
     * Soma valores monetários e retorna formatado, ou '-' se todos forem vazios.
     */
    private function sumCurrency(string ...$values): string
    {
        $sum = 0.0;
        $hasValue = false;
        foreach ($values as $v) {
            if ($v !== '') {
                $sum += (float) $v;
                $hasValue = true;
            }
        }
        return $hasValue ? $this->fmt->currency((string) $sum) : '-';
    }

    /**
     * Gera QR Code como data URI PNG
     */
    private function generateQrCode(string $chaveAcesso): string
    {
        $url = "https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave={$chaveAcesso}";

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd(),
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($url);

        // Retorna como SVG embutido em data URI
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
