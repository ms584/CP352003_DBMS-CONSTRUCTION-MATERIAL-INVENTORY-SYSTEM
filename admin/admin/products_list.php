<?php
session_start();
include("../../db.php"); 

// ระบบจัดการ "ลบสินค้า"
if(isset($_GET['delete_id'])){
    $del_id = (int)$_GET['delete_id'];
    try {
        // ลบประวัติที่เกี่ยวข้องก่อน
        mysqli_query($con, "DELETE FROM receiving_detail WHERE product_id = '$del_id'");
        mysqli_query($con, "DELETE FROM sales_detail    WHERE product_id = '$del_id'");
        // product_packaging มี ON DELETE CASCADE แล้ว
        if(mysqli_query($con, "DELETE FROM products WHERE product_id = '$del_id'")){
            echo "<script>alert('ลบสินค้าและประวัติที่เกี่ยวข้องเรียบร้อยแล้ว!'); window.location.href='products_list.php';</script>";
        } else {
            echo "<script>alert('ไม่สามารถลบได้'); window.location.href='products_list.php';</script>";
        }
    } catch(Exception $e) {
        echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); window.location.href='products_list.php';</script>";
    }
}

// ===== SORT LOGIC =====
$allowed_sort = ['selling_price', 'stock_qty', 'product_code', 'product_name', 'category_name'];
$allowed_dir  = ['asc', 'desc'];

$sort_col = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort) ? $_GET['sort'] : 'product_id';
$sort_dir = isset($_GET['dir'])  && in_array($_GET['dir'],  $allowed_dir)  ? $_GET['dir']  : 'desc';

// สลับทิศทางเมื่อคลิกหัวตารางเดิม
function sort_link($col, $label, $current_col, $current_dir) {
    $next_dir = ($current_col === $col && $current_dir === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if($current_col === $col) {
        $icon = $current_dir === 'asc' ? ' ▲' : ' ▼';
    }
    return "<a href='products_list.php?sort=$col&dir=$next_dir' style='color:inherit; text-decoration:none; white-space:nowrap;'>$label$icon</a>";
}

include "sidenav.php";
include "topheader.php";
?>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 class="card-title">รายการสินค้าวัสดุก่อสร้าง (เช็คสต๊อก)</h4>
                <p class="card-category">แสดงจำนวนคงเหลือและสถานะของสินค้า</p>
            </div>
            <a href="add_products.php" class="btn btn-info">เพิ่มสินค้าใหม่</a>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="text-primary">
                  <th><?php echo sort_link('product_code',   'รหัสสินค้า', $sort_col, $sort_dir); ?></th>
                  <th>รูปภาพ</th>
                  <th><?php echo sort_link('product_name',   'ชื่อสินค้า', $sort_col, $sort_dir); ?></th>
                  <th><?php echo sort_link('category_name',  'หมวดหมู่/ยี่ห้อ', $sort_col, $sort_dir); ?></th>
                  <th><?php echo sort_link('selling_price',  'ราคาขาย', $sort_col, $sort_dir); ?></th>
                  <th><?php echo sort_link('stock_qty',      'คงเหลือ',  $sort_col, $sort_dir); ?></th>
                  <th>หน่วย</th>
                  <th>สถานที่เก็บ</th>
                  <th>จัดการ</th>
                </thead>
                <tbody>
                  <?php 
                    $sql = "SELECT p.*, c.category_name, b.brand_name, u.unit_name 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.category_id 
                            LEFT JOIN brands b ON p.brand_id = b.brand_id 
                            LEFT JOIN units u ON p.unit_id = u.unit_id
                            ORDER BY " . ($sort_col === 'category_name' ? "c.$sort_col" : "p.$sort_col") . " $sort_dir";
                    
                    $result = mysqli_query($con, $sql);
                    
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_array($result)) {
                            
                            $stock_status = "";
                            $text_color = "";
                            if($row['stock_qty'] <= $row['min_stock']) {
                                $stock_status = "<br><small class='text-danger'><b>(ใกล้หมด!)</b></small>";
                                $text_color = "color: red; font-weight: bold;";
                            }

                            echo "<tr>";
                            echo "<td>".$row['product_code']."</td>";
                            echo "<td><img src='../../product_images/".$row['product_image']."' style='width:50px; height:50px; object-fit:cover; border-radius:5px;'></td>";
                            echo "<td>".$row['product_name']."</td>";
                            echo "<td>".$row['category_name']." <br> <small class='text-muted'>".$row['brand_name']."</small></td>";
                            echo "<td>".number_format($row['selling_price'], 2)." ฿</td>";
                            echo "<td style='".$text_color."'>".number_format($row['stock_qty']) . $stock_status ."</td>";
                            echo "<td>".$row['unit_name']."</td>";
                            echo "<td>".$row['location']."</td>";
                            echo "<td>
                                    <a href='edit_material.php?id=".$row['product_id']."' class='btn btn-warning btn-sm'><i class='material-icons'>edit</i></a>
                                    <a href='#' class='btn btn-danger btn-sm btn-del-product'
                                       data-id='".$row['product_id']."'
                                       data-code='".htmlspecialchars($row['product_code'], ENT_QUOTES)."'
                                       data-name='".htmlspecialchars($row['product_name'], ENT_QUOTES)."'>
                                      <i class='material-icons'>delete</i></a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' class='text-center'>ยังไม่มีรายการสินค้าในระบบ</td></tr>";
                    }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* highlight หัวตารางที่กำลัง sort อยู่ */
thead.text-primary th a {
    font-weight: 700;
}
thead.text-primary th a:hover {
    color: #ffd740 !important;
}
</style>

<script>
document.querySelectorAll('.btn-del-product').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var id   = this.dataset.id;
        var code = this.dataset.code;
        var name = this.dataset.name;
        if(confirm('ต้องการลบสินค้า [' + code + '] ' + name + ' ใช่ไหม?\n\n⚠️ ประวัติการรับเข้า/เบิกออกของสินค้านี้จะถูกลบด้วย')) {
            window.location.href = 'products_list.php?delete_id=' + id;
        }
    });
});
</script>
<?php include "footer.php"; ?>