<?php

namespace DanfseNacional;

/**
 * Converte XML de NFS-e Nacional para array associativo limpo.
 *
 * Trata namespaces automaticamente e exclui elementos de assinatura digital.
 * Atributos XML (como Id) são incluídos como chaves regulares do array.
 */
class XmlToArray
{
    private const NFSE_NS = 'http://www.sped.fazenda.gov.br/nfse';
    private const SIG_NS = 'http://www.w3.org/2000/09/xmldsig#';

    public function convert(string $xml): array
    {
        $root = new \SimpleXMLElement($xml);
        $result = $this->nodeToArray($root);

        // Garante que sempre retorna um array no nível raiz
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @return array|string
     */
    private function nodeToArray(\SimpleXMLElement $node): mixed
    {
        $result = [];
        $attrCount = 0;

        // Extrai atributos sem namespace (ex: Id, versao)
        foreach ($node->attributes() as $key => $value) {
            $result[(string) $key] = (string) $value;
            $attrCount++;
        }

        // Processa filhos no namespace NFS-e
        $childCount = 0;
        foreach ($node->children(self::NFSE_NS) as $name => $child) {
            $result[(string) $name] = $this->nodeToArray($child);
            $childCount++;
        }

        // Fallback: tenta filhos sem namespace (compatibilidade com XMLs alternativos)
        if ($childCount === 0) {
            foreach ($node->children() as $name => $child) {
                $name = (string) $name;
                if (!isset($result[$name])) {
                    $result[$name] = $this->nodeToArray($child);
                    $childCount++;
                }
            }
        }

        // Elemento folha sem atributos: retorna o texto diretamente
        if ($attrCount === 0 && $childCount === 0) {
            return trim((string) $node);
        }

        // Elemento com atributos mas sem filhos: inclui texto como _value (caso raro)
        if ($childCount === 0 && $attrCount > 0) {
            $text = trim((string) $node);
            if ($text !== '') {
                $result['_value'] = $text;
            }
        }

        return $result;
    }
}
