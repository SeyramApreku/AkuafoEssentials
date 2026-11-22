<?php
require_once __DIR__ . '/../settings/db_class.php';

class RatingClass extends Database
{
    public function find($orderId)
    {
        return $this->fetchOne('SELECT rating, comment FROM aku_order_ratings WHERE order_id = ?', [$orderId]);
    }

    /** One rating per order; rating again updates it. */
    public function save($orderId, $rating, $comment)
    {
        $this->execute(
            'INSERT INTO aku_order_ratings (order_id, rating, comment) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)',
            [$orderId, $rating, $comment]
        );
    }

    public function all($limit = 200)
    {
        return $this->fetchAll(
            'SELECT r.rating, r.comment, r.updated_at, o.ref, o.email, o.total
               FROM aku_order_ratings r JOIN aku_orders o ON o.id = r.order_id
              ORDER BY r.updated_at DESC LIMIT ' . (int)$limit
        );
    }

    public function stats()
    {
        return $this->fetchOne('SELECT COUNT(*) AS c, AVG(rating) AS a FROM aku_order_ratings');
    }
}