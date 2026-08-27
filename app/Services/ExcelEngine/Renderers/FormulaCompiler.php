<?php

namespace App\Services\ExcelEngine\Renderers;

class FormulaCompiler
{
    /**
     * Compile a template formula string by substituting tokens with actual row numbers and context values.
     *
     * Tokens supported:
     * - {row}           => Current active row
     * - {start_row}     => Section start row
     * - {end_row}       => Section end row
     * - {end_row+N}     => Section end row plus offset N
     * - {start_row-N}   => Section start row minus offset N
     * - {parent_row}    => Parent row index in nested loops
     * - {sheet:key}     => Actual sheet name mapped to key
     *
     * @param string $formulaPattern e.g. "=N{row}-O{row}" or "=SUM(P{start_row}:P{end_row})"
     * @param array $context [ 'row' => 14, 'start_row' => 13, 'end_row' => 20, 'parent_row' => 13, 'sheet_map' => [...] ]
     * @return string Compiled formula string e.g. "=N14-O14" or "=SUM(P13:P20)"
     */
    public function compile(string $formulaPattern, array $context): string
    {
        $formula = trim($formulaPattern);
        if (empty($formula)) {
            return '';
        }

        // Ensure formula begins with '='
        if (!str_starts_with($formula, '=')) {
            $formula = '=' . $formula;
        }

        // 1. Replace sheet name tokens {sheet:key}
        if (!empty($context['sheet_map']) && is_array($context['sheet_map'])) {
            $formula = preg_replace_callback('/\{sheet:([a-zA-Z0-9_\-]+)\}/', function ($matches) use ($context) {
                $sheetKey = $matches[1];
                return $context['sheet_map'][$sheetKey] ?? $sheetKey;
            }, $formula);
        }

        // 2. Replace arithmetic tokens {end_row+N}, {end_row-N}, {start_row+N}, {start_row-N}, {row+N}, {row-N}, {block_start_row+N}, {block_end_row+N}
        $formula = preg_replace_callback('/\{(row|start_row|end_row|parent_row|block_start_row|block_end_row)\s*([\+\-])\s*(\d+)\}/', function ($matches) use ($context) {
            $token = $matches[1];
            $operator = $matches[2];
            $offset = (int)$matches[3];
            $baseVal = (int)($context[$token] ?? 0);

            return $operator === '+' ? ($baseVal + $offset) : ($baseVal - $offset);
        }, $formula);

        // 3. Replace direct tokens {row}, {start_row}, {end_row}, {parent_row}, {block_start_row}, {block_end_row}
        $replacements = [
            '{row}'             => (string)($context['row'] ?? ''),
            '{start_row}'       => (string)($context['start_row'] ?? ''),
            '{end_row}'         => (string)($context['end_row'] ?? ''),
            '{parent_row}'      => (string)($context['parent_row'] ?? ''),
            '{block_start_row}' => (string)($context['block_start_row'] ?? ($context['row'] ?? '')),
            '{block_end_row}'   => (string)($context['block_end_row'] ?? ($context['row'] ?? '')),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $formula);
    }

    /**
     * Resolve target cell coordinate that might contain offset expressions like "P{end_row+1}"
     *
     * @param string $cellPattern e.g. "P{end_row+1}" or "E{row}"
     * @param array $context
     * @return string e.g. "P21"
     */
    public function resolveTargetCell(string $cellPattern, array $context): string
    {
        // Use the same token compiler logic without requiring leading '='
        $temp = $this->compile($cellPattern, $context);
        return ltrim($temp, '=');
    }
}
