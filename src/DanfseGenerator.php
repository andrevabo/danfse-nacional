<?php

namespace DanfseNacional;

use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Mapper\MappingError;

use DanfseNacional\Config\DanfseConfig;
use DanfseNacional\Dto\NFSe;
use DanfseNacional\Exception\DanfseMappingException;
use DanfseNacional\Template\DanfseTemplate;

use Dompdf\Dompdf;
use Dompdf\Options;

use Psr\Log\LoggerInterface;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

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
        private readonly ?LoggerInterface $logger = null,
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

        /**
         * Normaliza apenas os casos em que:
         *
         * - o XML trouxe string vazia: "";
         * - o DTO espera uma classe complexa;
         * - o tipo também aceita null.
         *
         * Exemplo:
         *
         * public ?Endereco $endereco
         *
         * Se o XML veio:
         *
         * "endereco" => ""
         *
         * vira:
         *
         * "endereco" => null
         *
         * Mas campos escalares como string, int, float e bool não são alterados aqui.
         */
        $array = $this->normalizeEmptyStringsForNullableComplexTypes($array, NFSe::class);

        $mapper = (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->allowPermissiveTypes()
            ->mapper();

        try {
            return $mapper->map(NFSe::class, $array);
        } catch (MappingError $error) {
            $errors = $this->formatMappingErrors($error);

            $this->logMappingError($errors, $array);

            throw new DanfseMappingException(
                message: 'XML da NFS-e não aderente ao DTO esperado para geração da DANFSE.',
                errors: $errors,
                previous: $error,
            );
        }
    }

    /**
     * Gera e renderiza o template HTML a partir do DTO da NFSe.
     * Útil para testes.
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeEmptyStringsForNullableComplexTypes(array $data, string $className): array
    {
        if (! class_exists($className)) {
            return $data;
        }

        $typesByName = $this->getDtoTypesByName($className);

        foreach ($data as $key => $value) {
            if (! isset($typesByName[$key])) {
                continue;
            }

            $type = $typesByName[$key];

            if ($value === '' && $this->isNullableComplexType($type)) {
                $data[$key] = null;

                continue;
            }

            if (is_array($value)) {
                $nestedClass = $this->getComplexClassName($type);

                if ($nestedClass !== null) {
                    $data[$key] = $this->normalizeEmptyStringsForNullableComplexTypes(
                        data: $value,
                        className: $nestedClass,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * O Valinor costuma mapear DTOs via constructor promotion.
     * Por isso, priorizamos parâmetros do construtor.
     *
     * Também fazemos fallback para propriedades públicas, caso algum DTO
     * use propriedades diretamente.
     *
     * @return array<string, ReflectionType>
     */
    private function getDtoTypesByName(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $types = [];

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type !== null) {
                    $types[$parameter->getName()] = $type;
                }
            }
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();

            if ($type !== null && ! isset($types[$property->getName()])) {
                $types[$property->getName()] = $type;
            }
        }

        return $types;
    }

    private function isNullableComplexType(ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->allowsNull()
                && ! $type->isBuiltin()
                && class_exists($type->getName());
        }

        if ($type instanceof ReflectionUnionType) {
            $allowsNull = false;
            $hasComplexClass = false;

            foreach ($type->getTypes() as $unionType) {
                if (! $unionType instanceof ReflectionNamedType) {
                    continue;
                }

                if ($unionType->getName() === 'null') {
                    $allowsNull = true;

                    continue;
                }

                if (! $unionType->isBuiltin() && class_exists($unionType->getName())) {
                    $hasComplexClass = true;
                }
            }

            return $allowsNull && $hasComplexClass;
        }

        return false;
    }

    private function getComplexClassName(ReflectionType $type): ?string
    {
        if ($type instanceof ReflectionNamedType) {
            if (! $type->isBuiltin() && class_exists($type->getName())) {
                return $type->getName();
            }

            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if (
                    $unionType instanceof ReflectionNamedType
                    && ! $unionType->isBuiltin()
                    && class_exists($unionType->getName())
                ) {
                    return $unionType->getName();
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{
     *     path: string,
     *     message: string,
     *     code: string|null,
     *     value: mixed,
     *     value_type: string
     * }>
     */
    private function formatMappingErrors(MappingError $error): array
    {
        $errors = [];

        foreach ($error->messages() as $message) {
            $sourceValue = $message->sourceValue();

            $errors[] = [
                'path' => $message->path(),
                'message' => (string) $message,
                'code' => $message->code(),
                'value' => $this->summarizeValue($sourceValue),
                'value_type' => get_debug_type($sourceValue),
            ];
        }

        return $errors;
    }

    /**
     * @param list<array<string, mixed>> $errors
     * @param array<string, mixed> $payload
     */
    private function logMappingError(array $errors, array $payload): void
    {
        $context = [
            'target' => NFSe::class,
            'errors' => $errors,
            'payload_summary' => $this->summarizePayload($payload),
        ];

        if ($this->logger !== null) {
            $this->logger->error(
                'Falha ao validar XML da NFS-e contra os DTOs da DANFSE.',
                $context,
            );

            return;
        }

        error_log(json_encode([
            'level' => 'error',
            'message' => 'Falha ao validar XML da NFS-e contra os DTOs da DANFSE.',
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function summarizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return [
                '_type' => 'array',
                '_count' => count($value),
                '_keys' => array_slice(array_keys($value), 0, 30),
            ];
        }

        if (is_string($value)) {
            return mb_strlen($value) > 500
                ? mb_substr($value, 0, 500) . '…'
                : $value;
        }

        if (is_object($value)) {
            return [
                '_type' => $value::class,
            ];
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function summarizePayload(array $payload): array
    {
        return [
            '_type' => 'array',
            '_count' => count($payload),
            '_keys' => array_slice(array_keys($payload), 0, 50),
        ];
    }
}
