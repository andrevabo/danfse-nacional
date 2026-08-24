<?php
/** @var array $data */
/** @var string $logo */
/** @var string $qrCode */
/** @var string|null $watermark */
/** @var bool $stub */

use DanfseNacional\Enums\RegEspTrib;

$tm = $data['tributacao_municipal'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DANFSe - <?= $data['numero_nfse'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            color: #000;
            margin: 4pt;
            padding: 2pt;
            border: 1pt #000 solid;
            -webkit-box-decoration-break: clone;
            box-decoration-break: clone;
            position: relative;
        }

        .footer-fixed {
            position: absolute;
            bottom: 5pt;
            border: 1pt solid #000;
            left: 50%;
            transform: translateX(-50%);
            width: 98%;
        }

        .footer-fixed td {
            border-right: 1pt solid #000;
            padding: 3pt 5pt;
            vertical-align: top;
        }

        .footer-fixed td:last-child {
            border-right: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        td {
            padding: .3pt 4pt;
            border: none;
            vertical-align: top;
        }

        table > tbody > tr > td {
            padding-bottom: 3pt;
        }

        table > tbody > tr > td:nth-child(2) {
            padding-left: 5pt;
        }

        .bordered-section {
            margin-bottom: 1pt;
            border-bottom: 1px solid #000;
        }

        .bordered-section:last-of-type,
        .bordered-section.last {
            border-bottom: none;
        }

        .first-section {
            position: relative;
        }

        .first-section .main-label {
            padding-bottom: 4px !important;
        }

        .main-label {
            background-color: #f2f2f2;
            padding-top: 1pt;
        }

        .label {
            font-size: 6pt;
            font-weight: bold;
            color: #000;
            display: block;
            margin-bottom: 0;
        }

        .value {
            font-size: 7pt;
            font-weight: normal;
            color: #000;
            font-family: sans-serif;
            margin-bottom: 0;
        }

        .section-title {
            font-size: 7pt;
            background-color: #f2f2f2;
            padding-top: 1pt;
            font-weight: bold;
        }

        .section-title.no-bg {
            background-color: unset;
        }

        .header-table {
            margin-bottom: 0;
            border-bottom: 1px solid #000;
        }

        .header-table td {
            border: none;
            padding-bottom: 1pt;
            background-color: #f2f2f2;
        }

        .logo-cell {
            width: 130pt;
            text-align: left;
            vertical-align: middle;
            padding-bottom: 4pt !important;
        }

        .title-cell {
            text-align: center;
            vertical-align: middle;
        }

        .municipality-cell {
            width: 150pt;
            text-align: left;
            font-size: 5.5pt;
            vertical-align: middle;
        }

        .qr-container {
            text-align: center;
            position: absolute;
            width: 185px;
        }

        /* Watermark para nota cancelada/substituída */
        .watermark {
            font-family: Arial, Helvetica, sans-serif;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 50pt;
            color: #e8e8e8;
            white-space: nowrap;
            z-index: -1;
        }

        @media dompdf {
            .footer-fixed {
                position: fixed;
                bottom: 35pt;
                width: 96%;
                left: 12pt;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <?php if ($watermark !== null): ?>
    <div class="watermark"><?= $watermark ?></div>
    <?php endif; ?>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <?php if ($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" style="max-width: 130pt; max-height: 40pt;">
                <?php endif; ?>
            </td>
            <td class="title-cell">
                <div style="font-size: 9pt; font-weight: bold;">DANFSe v2.0</div>
                <div style="font-size: 9pt; font-weight: bold;">Documento Auxiliar da NFS-e</div>
                <?php if ($data['ambiente'] == 2): ?>
                    <div style="color: red; font-weight: bold; font-size: 9pt;">NFS-e SEM VALIDADE JURÍDICA</div>
                <?php endif; ?>
            </td>
            <td class="municipality-cell">
                <?php if ($data['mostrar_municipio']): ?>
                <table>
                    <tr>
                        <td style="font-size: 8pt; padding: 0">
                            Município: <?= $data['municipio_emissor']['nome'] ?> / <?= $data['municipio_emissor']['uf'] ?>
                        </td>
                    </tr>
                </table>
                <?php endif; ?>
                <div style="font-size: 6pt; padding-top: 1pt;">
                    Ambiente Gerador: <?= $data['amb_gerador'] ?><br>
                    Tipo de Ambiente: <?= $data['ambiente'] ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Grade de Identificação -->
    <div class="bordered-section first-section">
        <table>
            <tr>
                <td colspan="3">
                    <span class="label">CHAVE DE ACESSO DA NFS-e</span>
                    <span class="value"><?= $data['chave_acesso'] ?></span>
                </td>
                <td style="width: 25%;" rowspan="3">
                    <div class="qr-container">
                        <img src="<?= htmlspecialchars($qrCode) ?>" alt="QR Code"
                             style="width: 65px; height: 65px; display: block; margin: 0 auto;"/>
                        <div style="font-size: 6pt; padding-top: 2pt; text-align: left;">
                            A autenticidade desta NFS-e pode ser verificada pela leitura deste código QR ou pela
                            consulta da chave de acesso no portal nacional da NFS-e
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="width: 25%;">
                    <span class="label">NÚMERO DA NFS-e</span>
                    <span class="value"><?= $data['numero_nfse'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">COMPETÊNCIA DA NFS-e</span>
                    <span class="value"><?= $data['competencia'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">DATA E HORA DA EMISSÃO DA NFS-e</span>
                    <span class="value"><?= $data['emissao_nfse'] ?></span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">NÚMERO DA DPS</span>
                    <span class="value"><?= $data['numero_dps'] ?></span>
                </td>
                <td>
                    <span class="label">SÉRIE DA DPS</span>
                    <span class="value"><?= $data['serie_dps'] ?></span>
                </td>
                <td>
                    <span class="label">DATA E HORA DA EMISSÃO DA DPS</span>
                    <span class="value"><?= $data['emissao_dps'] ?></span>
                </td>
            </tr>
            <tr>
                <td class="main-label">
                    <span class="label">EMITENTE DA NFS-e</span>
                    <span class="value"><?= $data['tipo_emitente'] ?></span>
                </td>
                <td>
                    <?php if ($data['situacao'] !== ''): ?>
                    <span class="label">SITUAÇÃO DA NFS-e</span>
                    <span class="value"><?= $data['situacao'] ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="label">FINALIDADE</span>
                    <span class="value"><?= $data['finalidade'] ?? '-' ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Prestador -->
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                    <span>PRESTADOR / FORNECEDOR</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">CNPJ / CPF / NIF</span>
                    <span class="value"><?= $data['prestador']['cnpj_cpf'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Indicador Municipal (Inscrição)</span>
                    <span class="value"><?= $data['prestador']['im'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Telefone</span>
                    <span class="value"><?= $data['prestador']['telefone'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label">Nome / Nome Empresarial</span>
                    <span class="value"><?= $data['prestador']['nome'] ?></span>
                </td>
                <td>
                    <span class="label">Município / Sigla UF</span>
                    <span class="value"><?= $data['prestador']['municipio'] ?></span>
                </td>
                <td>
                    <span class="label">Código IBGE / CEP</span>
                    <span class="value"><?= $data['prestador']['codigo_ibge_cep'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label">Endereço</span>
                    <span class="value"><?= $data['prestador']['endereco'] ?></span>
                </td>
                <td>
                    <span class="label">E-mail</span>
                    <span class="value"><?= $data['prestador']['email'] ?></span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label" style="font-size: 6pt">Simples Nacional na Data de Competência</span>
                    <span class="value"><?= $data['prestador']['simples_nacional'] ?></span>
                </td>
                <td COLSPAN="3">
                    <span class="label">Regime de Apuração Tributária pelo SN</span>
                    <span class="value"><?= $data['prestador']['regime_sn'] ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tomador -->
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                    <span>TOMADOR / ADQUIRENTE</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">CNPJ / CPF / NIF</span>
                    <span class="value"><?= $data['tomador']['cnpj_cpf'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Indicador Municipal (Inscrição)</span>
                    <span class="value"><?= $data['tomador']['im'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Telefone</span>
                    <span class="value"><?= $data['tomador']['telefone'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;">
                    <span class="label">Nome / Nome Empresarial</span>
                    <span class="value"><?= $data['tomador']['nome'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Município / Sigla UF</span>
                    <span class="value"><?= $data['tomador']['municipio'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Código IBGE / CEP</span>
                    <span class="value"><?= $data['tomador']['codigo_ibge_cep'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;">
                    <span class="label">Endereço</span>
                    <span class="value"><?= $data['tomador']['endereco'] ?></span>
                </td>
                <td colspan="2" style="width: 50%;">
                    <span class="label">E-mail</span>
                    <span class="value"><?= $data['tomador']['email'] ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Destinatário -->
    <?php if ($data['destinatario'] !== null): ?>
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                    <span>DESTINATÁRIO DA OPERAÇÃO</span>
                </td>
                <td colspan="2" style="width: 50%;">
                    <span class="label">CNPJ / CPF / NIF</span>
                    <span class="value"><?= $data['destinatario']['cnpj_cpf'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Telefone</span>
                    <span class="value"><?= $data['destinatario']['telefone'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;">
                    <span class="label">Nome / Nome Empresarial</span>
                    <span class="value"><?= $data['destinatario']['nome'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Município / Sigla UF</span>
                    <span class="value"><?= $data['destinatario']['municipio'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Código IBGE / CEP</span>
                    <span class="value"><?= $data['destinatario']['codigo_ibge_cep'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;">
                    <span class="label">Endereço</span>
                    <span class="value"><?= $data['destinatario']['endereco'] ?></span>
                </td>
                <td colspan="2" style="width: 50%;">
                    <span class="label">E-mail</span>
                    <span class="value"><?= $data['destinatario']['email'] ?></span>
                </td>
            </tr>
        </table>
    </div>
    <?php else: ?>
    <div class="bordered-section" style="text-align: center; font-weight: normal; font-size: 7pt;">
        <?= $data['destinatario_msg'] ?>
    </div>
    <?php endif; ?>

    <!-- Intermediário -->
    <?php if ($data['intermediario'] !== null): ?>
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                    <span>INTERMEDIÁRIO DA OPERAÇÃO</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">CNPJ / CPF / NIF</span>
                    <span class="value"><?= $data['intermediario']['cnpj_cpf'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Indicador Municipal (Inscrição)</span>
                    <span class="value"><?= $data['intermediario']['im'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Telefone</span>
                    <span class="value"><?= $data['intermediario']['telefone'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;">
                    <span class="label">Nome / Nome Empresarial</span>
                    <span class="value"><?= $data['intermediario']['nome'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Município / Sigla UF</span>
                    <span class="value"><?= $data['intermediario']['municipio'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Código IBGE / CEP</span>
                    <span class="value"><?= $data['intermediario']['codigo_ibge_cep'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="width: 50%;">
                    <span class="label">Endereço</span>
                    <span class="value"><?= $data['intermediario']['endereco'] ?></span>
                </td>
                <td colspan="2" style="width: 50%;">
                    <span class="label">E-mail</span>
                    <span class="value"><?= $data['intermediario']['email'] ?></span>
                </td>
            </tr>
        </table>
    </div>
    <?php else: ?>
    <div class="bordered-section" style="text-align: center; font-weight: normal; font-size: 7pt;">
        INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e
    </div>
    <?php endif; ?>

    <!-- Serviço Prestado -->
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                  <span>SERVIÇO PRESTADO</span>
                </td>

                <td style="width: 25%;">
                    <span class="label">Código de Trib. Nacional / Municipal</span>
                    <span class="value"><?= $data['servico']['codigo_trib_nacional'] ?> / <?= $data['servico']['codigo_trib_municipal'] ?></span>
                </td>

                <td style="width: 25%;">
                    <span class="label">Código da NBS</span>
                    <span class="value"><?= $data['servico']['codigo_nbs'] ?></span>
                </td>

                <td style="width: 25%;">
                    <span class="label">Local da Prestação / Sigla UF / País</span>
                    <span class="value"><?= $data['servico']['local_prestacao'] ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <?= $data['servico']['desc_trib'] ?>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">Descrição do Serviço</span>
                    <span class="value"><?= $data['servico']['descricao'] ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tributação Municipal -->
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                  <span>TRIBUTAÇÃO MUNICIPAL (ISSQN)</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Tipo de Tributação do ISSQN</span>
                    <span class="value"><?= $tm['tributacao_issqn'] ?? '-' ?></span>
                </td>
                <td colspan="2">
                    <span class="label">Município / Sigla UF / País de Incidência do ISSQN</span>
                    <span class="value"><?= $tm['municipio_incidencia'] ?? '-' ?></span>
                </td>
            </tr>

            <?php
            if (
                $tm['regime_especial'] !== RegEspTrib::labelFor(RegEspTrib::NENHUM->value) || !empty($tm['tipo_imunidade']) ||
                !empty($tm['suspensao_exigibilidade']) || !empty($tm['num_processo_suspensao'])
            ):
            ?>
            <tr>
                <td style="width: 25%;">
                    <span class="label">Regime Especial de Tributação do ISSQN</span>
                    <span class="value"><?= $tm['regime_especial'] ?: '-' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Tipo de Imunidade do ISSQN</span>
                    <span class="value"><?= $tm['tipo_imunidade'] ?: '-' ?></span>
                </td>
                <td>
                    <span class="label">Suspensão da Exigibilidade do ISSQN</span>
                    <span class="value"><?= $tm['suspensao_exigibilidade'] ?: 'Não' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Número Processo Suspensão</span>
                    <span class="value"><?= $tm['num_processo_suspensao'] ?: '-' ?></span>
                </td>
            </tr>
            <?php endif; ?>

            <?php
            if (
                !empty($tm['beneficio_municipal']) || !empty($tm['calculo_bm']) ||
                !empty($tm['total_deducoes']) || !empty($tm['desconto_incondicionado'])
            ):
            ?>
            <tr>
                <td>
                    <span class="label">Benefício Municipal</span>
                    <span class="value"><?= $tm['beneficio_municipal'] ?: '-' ?></span>
                </td>
                <td>
                    <span class="label">Cálculo do BM</span>
                    <span class="value"><?= $tm['calculo_bm'] ?: '-' ?></span>
                </td>
                <td>
                    <span class="label">Total de Deduções/Reduções</span>
                    <span class="value"><?= $tm['total_deducoes'] ?: '-' ?></span>
                </td>
                <td>
                    <span class="label">Desconto Incondicionado</span>
                    <span class="value"><?= $tm['desconto_incondicionado'] ?: '-' ?></span>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>
                    <span class="label">BC ISSQN</span>
                    <span class="value"><?= $tm['bc_issqn'] ?? '-' ?></span>
                </td>
                <td>
                    <span class="label">Alíquota Aplicada</span>
                    <span class="value"><?= $tm['aliquota'] ?? '-' ?></span>
                </td>
                <td>
                    <span class="label">Retenção do ISSQN</span>
                    <span class="value"><?= $tm['retencao_issqn'] ?? '-' ?></span>
                </td>
                <td>
                    <span class="label">ISSQN Apurado</span>
                    <span class="value"><?= $tm['issqn_apurado'] ?? '-' ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tributação Federal -->
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                  <span class="section-title">TRIBUTAÇÃO FEDERAL (EXCETO CBS)</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">IRRF</span>
                    <span class="value"><?= $data['tributacao_federal']['irrf'] ?? '-' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Contribuição Previdenciária - Retida</span>
                    <span class="value"><?= $data['tributacao_federal']['cp'] ?? '-' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Contribuições Sociais - Retidas</span>
                    <span class="value"><?= $data['tributacao_federal']['contrib_sociais'] ?? '-' ?></span>
                </td>
            </tr>
            <?php if ($data['tributacao_federal']['mostrar_pis_cofins']): ?>
            <tr>
                <td style="width: 25%;">
                    <span class="label">PIS - Débito Apuração Própria</span>
                    <span class="value"><?= $data['tributacao_federal']['pis'] ?? '-' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">COFINS - Débito Apuração Própria</span>
                    <span class="value"><?= $data['tributacao_federal']['cofins'] ?? '-' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Descrição Contrib. Sociais - Retidas</span>
                    <span class="value"><?= $data['tributacao_federal']['desc_contrib_sociais'] ?? '-' ?></span>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Tributação IBS/CBS -->
    <?php if ($data['ibs_cbs'] !== null): ?>
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%; font-weight: bold;" class="section-title">
                    <span class="section-title">TRIBUTAÇÃO IBS / CBS</span>
                </td>
                <td style="width: 25%;">
                    <span class="label">CST / cClassTrib</span>
                    <span class="value"><?= $data['ibs_cbs']['cst_classtrib'] ?></span>
                </td>
                <td colspan="2">
                    <span class="label">Indicador de Operação / Código IBGE Incidência / Município Incidência / Sigla UF</span>
                    <span class="value"><?= $data['ibs_cbs']['indicador_operacao'] ?></span>
                </td>
            </tr>
            <tr>
                <td style="width: 25%;">
                    <span class="label">Exclusões e Reduções da Base de Cálculo</span>
                    <span class="value"><?= $data['ibs_cbs']['exclusoes_reducoes'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Base de Cálculo após Exclusões e Reduções</span>
                    <span class="value"><?= $data['ibs_cbs']['bc'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Red. Alíquota IBS / CBS</span>
                    <span class="value"><?= $data['ibs_cbs']['red_aliquota'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Alíquota - IBS UF / Mun</span>
                    <span class="value"><?= $data['ibs_cbs']['aliquota_ibs'] ?></span>
                </td>
            </tr>
            <tr>
                <td style="width: 25%;">
                    <span class="label">Alíq. Efetiva Municipal - IBS</span>
                    <span class="value"><?= $data['ibs_cbs']['aliq_efetiva_mun'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Valor Apurado Municipal - IBS</span>
                    <span class="value"><?= $data['ibs_cbs']['valor_apurado_mun'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Alíq. Efetiva Estadual - IBS</span>
                    <span class="value"><?= $data['ibs_cbs']['aliq_efetiva_uf'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Valor Apurado Estadual - IBS</span>
                    <span class="value"><?= $data['ibs_cbs']['valor_apurado_uf'] ?></span>
                </td>
            </tr>
            <tr>
                <td style="width: 25%;">
                    <span class="label">Valor Total Apurado - IBS</span>
                    <span class="value"><?= $data['ibs_cbs']['valor_total_ibs'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Alíquota - CBS</span>
                    <span class="value"><?= $data['ibs_cbs']['aliquota_cbs'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Alíquota Efetiva - CBS</span>
                    <span class="value"><?= $data['ibs_cbs']['aliq_efetiva_cbs'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Valor Total Apurado - CBS</span>
                    <span class="value"><?= $data['ibs_cbs']['valor_total_cbs'] ?></span>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Valor Total -->
    <div class="bordered-section">
        <table>
            <tr>
                <td style="width: 25%;" class="section-title">
                  <span class="section-title">VALOR TOTAL DA NFS-e</span>
                </td>
                <td>
                    <span class="label">VALOR DA OPERAÇÃO/SERVIÇO</span>
                    <span class="value"><?= $data['totais']['valor_servico'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Desconto Incondicionado</span>
                    <span class="value"><?= $data['totais']['desconto_incondicionado'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Desconto Condicionado</span>
                    <span class="value"><?= $data['totais']['desconto_condicionado'] ?></span>
                </td>
            </tr>
            <tr>
                <td style="width: 25%;">
                    <span class="label">Total das Retenções Federais</span>
                    <span class="value"><?= $data['totais']['retencoes_federais'] ?? '-' ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">VALOR LÍQUIDO DA NFS-e</span>
                    <span class="value"><?= $data['totais']['valor_liquido'] ?></span>
                </td>
                <td style="width: 25%;">
                    <span class="label">Total do IBS/CBS</span>
                    <span class="value"><?= $data['totais']['total_ibs_cbs'] ?? '-' ?></span>
                </td>
                <td style="width: 25%;" class="section-title">
                    <span class="label">VALOR LÍQUIDO DA NFS-e + IBS/CBS</span>
                    <span class="value"><?= $data['totais']['valor_liquido_ibscbs'] ?? '-' ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Informações Complementares -->
    <div class="bordered-section last">
        <table>
            <tr>
                <td class="section-title no-bg">
                  <span>INFORMAÇÕES COMPLEMENTARES</span>
                </td>
            </tr>
            <tr>
                <td style="min-height: 30pt; padding: 5pt;">
                    <span class="value"><?= $data['informacoes_complementares'] ?></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Canhoto -->
    <?php if ($stub): ?>
    <table class="footer-fixed">
        <tr>
            <td style="width: 25%;">
                <span class="label">DATA CIENTIFICAÇÃO:</span>
            </td>
            <td style="width: 25%;">
                <span class="label">IDENTIFICAÇÃO E ASSINATURA</span>
            </td>
            <td style="width: 50%;">
                <span class="label">N° NFS-e / CHAVE NFS-e</span>
                <span class="value"><?= $data['numero_nfse'] ?> / <?= $data['chave_acesso'] ?></span>
            </td>
        </tr>
    </table>
    <?php endif; ?>
</body>
</html>
