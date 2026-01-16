<?php

declare(strict_types=1);

namespace SellNow\Repositories;

use PDO;
use SellNow\Entities\Order;

/**
 * Order Repository - Handles all database operations for orders
 */
class OrderRepository implements RepositoryInterface
{
    public function __construct(
        private PDO $db
    ) {}

    public function find(int $id): ?Order
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? Order::fromArray($row) : null;
    }

    public function findByTransactionId(string $transactionId): ?Order
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? Order::fromArray($row) : null;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => Order::fromArray($row), $rows);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM orders ORDER BY order_date DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => Order::fromArray($row), $rows);
    }

    public function create(object $entity): int
    {
        if (!$entity instanceof Order) {
            throw new \InvalidArgumentException('Expected Order entity');
        }

        $sql = "INSERT INTO orders (user_id, total_amount, payment_provider, payment_status, transaction_id, order_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $entity->getUserId(),
            $entity->getTotalAmount(),
            $entity->getPaymentProvider(),
            $entity->getPaymentStatus(),
            $entity->getTransactionId(),
            $entity->getOrderDate()?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(object $entity): bool
    {
        if (!$entity instanceof Order) {
            throw new \InvalidArgumentException('Expected Order entity');
        }

        $sql = "UPDATE orders SET 
                    total_amount = ?, payment_provider = ?, payment_status = ?, transaction_id = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $entity->getTotalAmount(),
            $entity->getPaymentProvider(),
            $entity->getPaymentStatus(),
            $entity->getTransactionId(),
            $entity->getId(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM orders WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status, ?string $transactionId = null): bool
    {
        $sql = "UPDATE orders SET payment_status = ?";
        $params = [$status];
        
        if ($transactionId !== null) {
            $sql .= ", transaction_id = ?";
            $params[] = $transactionId;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders WHERE payment_status = ? ORDER BY order_date DESC"
        );
        $stmt->execute([$status]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => Order::fromArray($row), $rows);
    }

    public function getTotalRevenue(): float
    {
        $stmt = $this->db->query(
            "SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'"
        );
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE payment_status = ?");
        $stmt->execute([$status]);
        
        return (int) $stmt->fetchColumn();
    }
}
