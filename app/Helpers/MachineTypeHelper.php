<?php

namespace App\Helpers;

class MachineTypeHelper
{
    /**
     * Official Stamping Machine Type Abbreviation Mapping
     */
    public const MAP = [
        'HYD' => 'Hydraulic',
        'TDM' => 'Tandem',
        'PRO' => 'Progressive',
        'LTR' => 'Linear Transfer',
        'RBT' => 'Robot Transfer',
    ];

    /**
     * Additional aliases and variations for flexible matching
     */
    public const ALIASES = [
        'HYDRAULIC'       => 'HYD',
        'HYD'             => 'HYD',
        'TANDEM'          => 'TDM',
        'TDM'             => 'TDM',
        'PROGRESSIVE'     => 'PRO',
        'PRO'             => 'PRO',
        'LINEAR TRANSFER' => 'LTR',
        'LTR'             => 'LTR',
        'ROBOT TRANSFER'  => 'RBT',
        'RBT'             => 'RBT',
        'TRANSFER'        => 'LTR',
        'TRF'             => 'LTR',
        'M'               => 'TDM',
        'JW'              => 'TDM',
        'MANUAL'          => 'TDM',
    ];

    /**
     * Normalize any machine input to standard Code ('HYD', 'TDM', 'PRO', 'LTR', 'RBT')
     *
     * @param string|null $input
     * @return string
     */
    public static function toCode(?string $input): string
    {
        if (empty($input)) {
            return 'TDM';
        }

        $clean = strtoupper(trim($input));
        return self::ALIASES[$clean] ?? (str_starts_with($clean, 'HYD') ? 'HYD' :
               (str_starts_with($clean, 'PRO') ? 'PRO' :
               (str_starts_with($clean, 'LTR') ? 'LTR' :
               (str_starts_with($clean, 'RBT') ? 'RBT' : 'TDM'))));
    }

    /**
     * Normalize any machine input to standard Name ('Hydraulic', 'Tandem', 'Progressive', 'Linear Transfer', 'Robot Transfer')
     *
     * @param string|null $input
     * @return string
     */
    public static function toName(?string $input): string
    {
        $code = self::toCode($input);
        return self::MAP[$code] ?? 'Tandem';
    }

    /**
     * Normalize any machine input to UPPERCASE Name ('HYDRAULIC', 'TANDEM', 'PROGRESSIVE', etc.)
     *
     * @param string|null $input
     * @return string
     */
    public static function toUpperName(?string $input): string
    {
        return strtoupper(self::toName($input));
    }

    /**
     * Get list of options for Dropdowns and Forms
     *
     * @return array Key => Label (e.g. ['HYD' => 'HYD - HYDRAULIC', ...])
     */
    public static function getDropdownOptions(): array
    {
        $options = [];
        foreach (self::MAP as $code => $name) {
            $options[$code] = "{$code} : " . strtoupper($name);
        }
        return $options;
    }

    /**
     * Check if two machine types match (handling code vs full name)
     *
     * @param string|null $machine1
     * @param string|null $machine2
     * @return bool
     */
    public static function isMatch(?string $machine1, ?string $machine2): bool
    {
        if (empty($machine1) || empty($machine2)) {
            return true;
        }
        return self::toCode($machine1) === self::toCode($machine2);
    }
}
