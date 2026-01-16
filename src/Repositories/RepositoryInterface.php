<?php

declare(strict_types=1);

namespace SellNow\Repositories;

/**
 * Base Repository Interface
 * Defines common database operations for all repositories
 */
interface RepositoryInterface
{
    public function find(int $id): ?object;
    
    public function findAll(): array;
    
    public function create(object $entity): int;
    
    public function update(object $entity): bool;
    
    public function delete(int $id): bool;
}
