<?php

namespace App\Traits;

use PDO;

trait Pagination
{
    /**
     * Get paginated results from a table.
     *
     * @param string $table The table name.
     * @param int $limit Number of records per page.
     * @param int $offset The offset from which to start fetching records.
     * @return array
     */
    public function paginate(string $table, int $limit, int $offset): array
    {
        $sql = "SELECT * FROM {$table} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}