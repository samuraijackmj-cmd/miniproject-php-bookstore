<?php
$files = [
    'admin/sales_report.php',
    'admin/books.php',
    'admin/orders.php',
    'admin/customers.php',
    'admin/users.php',
    'admin/dashboard.php',
    'admin/search_books_ajax.php',
    'admin/book_edit.php',
    'admin/book_add.php',
    'admin/categories.php',
    'admin/admin_profile.php',
    'admin/settings.php',
    'admin/order_detail.php',
    'admin/print_invoice.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "Missing $f\n";
        continue;
    }
    $content = file_get_contents($f);
    
    // Replace htmlspecialchars( ... ) that does not have ENT_QUOTES
    $count = 0;
    $content = preg_replace_callback(
        '/htmlspecialchars\s*\(([^,]+?)\)/i',
        function ($matches) {
            // Check if what matched doesn't look like it already has multiple args
            // But if it's htmlspecialchars($x ?? 'y'), it might not have commas.
            return "htmlspecialchars(" . $matches[1] . ", ENT_QUOTES, 'UTF-8')";
        },
        $content,
        -1,
        $count
    );
    
    // Also, we need to find echo $row['field'] without htmlspecialchars.
    // E.g. echo $book['title']; echo $user['username'];
    // It's harder with regex, let's replace a few known ones
    $content = preg_replace('/<\?php\s+echo\s+\\$([a-zA-Z0-9_]+)\[([\'"][a-zA-Z0-9_]+[\'"])\]\s*;\s*\?>/', '<?php echo htmlspecialchars(\$$1[$2], ENT_QUOTES, \'UTF-8\'); ?>', $content);
    $content = preg_replace('/<\?=\s*\\$([a-zA-Z0-9_]+)\[([\'"][a-zA-Z0-9_]+[\'"])\]\s*\?>/', '<?= htmlspecialchars(\$$1[$2], ENT_QUOTES, \'UTF-8\') ?>', $content);

    file_put_contents($f, $content);
    echo "Patched $count XSS in $f\n";
}
