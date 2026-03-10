<?php
// ajax: get packaging options for a product
// usage: get_packaging.php?product_id=5
session_start();
include("../../db.php");

header('Content-Type: application/json');

$pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if($pid <= 0) { echo json_encode([]); exit(); }

// ดึง base_unit ของสินค้า + packaging options
$prod_q = mysqli_query($con, "SELECT base_unit FROM products WHERE product_id = '$pid'");
$prod = mysqli_fetch_assoc($prod_q);
$base_unit = $prod['base_unit'] ?? '';

$pkg_q = mysqli_query($con, "SELECT id, package_unit, units_per_package FROM product_packaging WHERE product_id = '$pid' ORDER BY units_per_package ASC");
$packages = [];
while($r = mysqli_fetch_assoc($pkg_q)) {
    $packages[] = [
        'id'               => $r['id'],
        'package_unit'     => $r['package_unit'],
        'units_per_package'=> (float)$r['units_per_package'],
        'label'            => "1 {$r['package_unit']} = {$r['units_per_package']} $base_unit"
    ];
}

echo json_encode(['base_unit' => $base_unit, 'packages' => $packages]);
