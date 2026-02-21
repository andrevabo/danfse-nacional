<?php

namespace DanfseNacional;

/**
 * Formatadores para padrões brasileiros (CNPJ, CPF, telefone, CEP, moeda, datas)
 */
class Formatter
{
    public function cnpjCpf(string $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        $value = preg_replace('/\D/', '', $value);

        if (strlen($value) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $value);
        }

        if (strlen($value) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
        }

        return $value;
    }

    public function phone(string $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        $value = preg_replace('/\D/', '', $value);

        if (strlen($value) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $value);
        }

        if (strlen($value) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $value);
        }

        return $value;
    }

    public function cep(string $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        $value = preg_replace('/\D/', '', $value);

        if (strlen($value) === 8) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $value);
        }

        return $value;
    }

    public function date(string $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        try {
            $dt = new \DateTimeImmutable($value);
            return $dt->format('d/m/Y');
        } catch (\Exception) {
            return $value;
        }
    }

    public function dateTime(string $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        try {
            $dt = new \DateTimeImmutable($value);
            return $dt->format('d/m/Y H:i:s');
        } catch (\Exception) {
            return $value;
        }
    }

    public function currency(string|float $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    /**
     * Formata código de tributação nacional para o padrão XX.XX.XX
     */
    public function codTribNacional(string $value): string
    {
        if ($value === '' || $value === '-') {
            return '-';
        }

        $value = preg_replace('/\D/', '', $value);

        if (strlen($value) === 6) {
            return preg_replace('/(\d{2})(\d{2})(\d{2})/', '$1.$2.$3', $value);
        }

        return $value;
    }

    public function limit(string $value, int $limit, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }
}
