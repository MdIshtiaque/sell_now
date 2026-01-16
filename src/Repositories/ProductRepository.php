<?php

declare(strict_types=1);

namespace SellNow\Repositories;

use PDO;
use SellNow\Entities\Product;

/**
 * Product Repository - Handles all database operations for products
 */
class ProductRepository implements RepositoryInterface
{
    public function __construct(
        private PDO $db
    ) {}

    public function find(int $id): ?Product
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? Product::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Product
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? Product::fromArray($row) : null;
    }

    public function findByUserId(int $userId, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM products WHERE user_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY product_id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => Product::fromArray($row), $rows);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => Product::fromArray($row), $rows);
    }

    public function create(object $entity): int
    {
        if (!$entity instanceof Product) {
            throw new \InvalidArgumentException('Expected Product entity');
        }

        $sql = "INSERT INTO products (user_id, title, slug, description, price, image_path, file_path, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $entity->getUserId(),
            $entity->getTitle(),
            $entity->getSlug(),
            $entity->getDescription(),
            $entity->getPrice(),
            $entity->getImagePath(),
            $entity->getFilePath(),
            $entity->isActive() ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(object $entity): bool
    {
        if (!$entity instanceof Product) {
            throw new \InvalidArgumentException('Expected Product entity');
        }

        $sql = "UPDATE products SET 
                    title = ?, slug = ?, description = ?, price = ?, 
                    image_path = ?, file_path = ?, is_active = ? 
                WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $entity->getTitle(),
            $entity->getSlug(),
            $entity->getDescription(),
            $entity->getPrice(),
            $entity->getImagePath(),
            $entity->getFilePath(),
            $entity->isActive() ? 1 : 0,
            $entity->getId(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    public function deactivate(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    public function search(string $query, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products 
             WHERE is_active = 1 AND (title LIKE ? OR description LIKE ?) 
             ORDER BY product_id DESC 
             LIMIT ?"
        );
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => Product::fromArray($row), $rows);
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        return (int) $stmt->fetchColumn();
    }
}
