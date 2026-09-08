<?php
$files = [
    'manage_address.php', 'checkout.php', 'cancel_order.php', 'cart_action.php', 'wishlist_action.php', 'save_order.php',
    'admin/book_add.php', 'admin/book_edit.php', 'admin/book_delete.php', 'admin/categories.php', 'admin/order_detail.php', 'admin/settings.php', 'admin/admin_profile.php', 'admin/users.php',
    'admin/category_action_ajax.php', 'admin/update_stock_ajax.php', 'admin/delete_order.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "File not found: $f\n";
        continue;
    }
    $content = file_get_contents($f);
    $helper_path = strpos($f, 'admin/') === 0 ? '../includes/csrf_helper.php' : 'includes/csrf_helper.php';
    
    // 1. Add require & generate
    if (strpos($content, 'csrf_helper.php') === false) {
        $content = preg_replace('/(require_once.*db\.php[\'\"];)/i', "$1\nrequire_once '$helper_path';\ncsrf_generate();", $content, 1);
    }
    
    // 2. Add verify on POST
    $content = preg_replace('/(if\s*\(\s*\$_SERVER\[[\'"]REQUEST_METHOD[\'"]\]\s*==\s*[\'"]POST[\'"]\s*\)\s*\{)/i', "$1\n    csrf_verify();", $content);
    $content = preg_replace('/(if\s*\(\s*isset\(\$_POST\[[\'"][a-zA-Z0-9_]+[\'"]\]\)\s*\)\s*\{)/i', "$1\n    csrf_verify();", $content);
    
    // 3. Add csrf_field() to POST forms
    if (strpos($f, '_ajax') === false && strpos($f, 'delete_order.php') === false) {
        $content = preg_replace('/(<form[^>]*method=[\'"]POST[\'"][^>]*>)/i', "$1\n    <?= csrf_field() ?>", $content);
    }

    file_put_contents($f, $content);
    echo "Patched $f\n";
}
echo "Done CSRF patching!\n";
