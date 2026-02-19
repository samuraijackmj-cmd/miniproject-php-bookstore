<?php
header('Content-Type: application/json');
session_start();
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Book ID not provided']);
    exit;
}

$book_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        echo json_encode(['error' => 'Book not found']);
        exit;
    }

    // Get ratings
    $rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE book_id = ? AND approved = 1");
    $rating_stmt->execute([$book_id]);
    $rating_data = $rating_stmt->fetch();

    $response = [
        'id' => $book['book_id'],
        'title' => $book['title'],
        'author' => $book['author'],
        'price' => floatval($book['price']),
        'original_price' => !empty($book['original_price']) ? floatval($book['original_price']) : null,
        'discount' => intval($book['discount_percent']),
        'image' => !empty($book['cover_image']) ? $book['cover_image'] : 'https://via.placeholder.com/300x450?text=' . urlencode($book['title']),
        'description' => $book['description'] ?? 'ไม่มีรายละเอียด',
        'stock' => intval($book['stock_quantity']),
        'rating' => round($rating_data['avg_rating'] ?? 0, 1),
        'reviews' => intval($rating_data['review_count'] ?? 0)
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
