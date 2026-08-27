<?php

namespace App\Services\ExcelEngine\Contracts;

interface DataResolverInterface
{
    /**
     * Resolve domain entity into normalized array for Dynamic Excel Engine
     *
     * @param int|string|object $entity
     * @param array $options Optional resolver parameters
     * @return array{
     *    fields?: array<string, mixed>,
     *    sections?: array<string, array<int, array<string, mixed>>>
     * }
     */
    public function resolve($entity, array $options = []): array;
}
