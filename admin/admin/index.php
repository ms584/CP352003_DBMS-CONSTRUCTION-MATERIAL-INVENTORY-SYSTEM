
   <?php
session_start();
include("../../db.php");

// ดึงข้อมูลสรุปสำหรับ Dashboard
$q_products = mysqli_query($con, "SELECT COUNT(*) as total FROM products");
$total_products = mysqli_fetch_assoc($q_products)['total'];

$q_lowstock = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE stock_qty <= min_stock");
$low_stock = mysqli_fetch_assoc($q_lowstock)['total'];

$q_categories = mysqli_query($con, "SELECT COUNT(*) as total FROM categories");
$total_categories = mysqli_fetch_assoc($q_categories)['total'];

$q_suppliers = mysqli_query($con, "SELECT COUNT(*) as total FROM suppliers");
$total_suppliers = mysqli_fetch_assoc($q_suppliers)['total'];

// === การเงิน ===
// รายรับ: ยอดขายทั้งหมด (เฉพาะที่ชำระแล้ว)
$q_income = mysqli_query($con, "SELECT COALESCE(SUM(total_amount),0) as total FROM sales WHERE payment_status = 'ชำระแล้ว'");
$total_income = (float)mysqli_fetch_assoc($q_income)['total'];

// รายจ่าย: ยอดซื้อสินค้าเข้า (Receiving)
$q_expense = mysqli_query($con, "SELECT COALESCE(SUM(rd.qty * rd.cost_price),0) as total FROM receiving_detail rd");
$total_expense = (float)mysqli_fetch_assoc($q_expense)['total'];

// กำไร / ขาดทุน
$profit = $total_income - $total_expense;

// ค้างชำระ
$q_unpaid = mysqli_query($con, "SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total FROM sales WHERE payment_status = 'ค้างชำระ'");
$unpaid_row = mysqli_fetch_assoc($q_unpaid);
$unpaid_count = $unpaid_row['cnt'];
$unpaid_total = (float)$unpaid_row['total'];

include "sidenav.php";
include "topheader.php";
?>

<div class="content">
  <div class="container-fluid">
    
    <div class="row">
        <div class="col-md-12">
            <h3 class="text-white">ภาพรวมระบบคลังสินค้า (Dashboard)</h3>
        </div>
    </div>

    <div class="row mt-3">
      
      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-success card-header-icon">
            <div class="card-icon">
              <i class="material-icons">inventory_2</i>
            </div>
            <p class="card-category">รายการสินค้าทั้งหมด</p>
            <h3 class="card-title"><?php echo $total_products; ?> <small>รายการ</small></h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons text-success">update</i> อัปเดตล่าสุด
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-danger card-header-icon">
            <div class="card-icon">
              <i class="material-icons">warning</i>
            </div>
            <p class="card-category">สินค้าใกล้หมดสต๊อก!</p>
            <h3 class="card-title"><?php echo $low_stock; ?> <small>รายการ</small></h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons">local_offer</i> ต้องสั่งซื้อเพิ่มด่วน
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-warning card-header-icon">
            <div class="card-icon">
              <i class="material-icons">category</i>
            </div>
            <p class="card-category">หมวดหมู่วัสดุก่อสร้าง</p>
            <h3 class="card-title"><?php echo $total_categories; ?> <small>หมวดหมู่</small></h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons">folder</i> จัดการหมวดหมู่สินค้า
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-info card-header-icon">
            <div class="card-icon">
              <i class="material-icons">local_shipping</i>
            </div>
            <p class="card-category">คู่ค้า / ร้านส่ง</p>
            <h3 class="card-title"><?php echo $total_suppliers; ?> <small>บริษัท</small></h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons">contacts</i> รายชื่อผู้จัดจำหน่าย
            </div>
          </div>
        </div>
      </div>
      
    </div>

    <!-- ===  สรุปการเงิน === -->
    <div class="row mt-2">
      <div class="col-md-12">
        <h5 class="text-white" style="border-left:4px solid #4caf50; padding-left:10px; margin-bottom:15px;">
          <i class="material-icons" style="vertical-align:middle;">bar_chart</i>
          สรุปการเงิน (Financial Summary)
        </h5>
      </div>
    </div>

    <div class="row">

      <!-- รายรับ -->
      <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-success card-header-icon">
            <div class="card-icon"><i class="material-icons">trending_up</i></div>
            <p class="card-category">รายรับรวม (ยอดขายที่ชำระแล้ว)</p>
            <h3 class="card-title"><?php echo number_format($total_income, 2); ?> <small>บาท</small></h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons text-success">receipt_long</i>
              <a href="salesofday.php" style="color:inherit;">ดูประวัติการออกบิล</a>
            </div>
          </div>
        </div>
      </div>

      <!-- รายจ่าย -->
      <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-danger card-header-icon">
            <div class="card-icon"><i class="material-icons">trending_down</i></div>
            <p class="card-category">รายจ่ายรวม (ต้นทุนสินค้ารับเข้า)</p>
            <h3 class="card-title"><?php echo number_format($total_expense, 2); ?> <small>บาท</small></h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons">local_shipping</i>
              <a href="receiving_history.php" style="color:inherit;">ดูประวัติการรับสินค้า</a>
            </div>
          </div>
        </div>
      </div>

      <!-- กำไร / ขาดทุน -->
      <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header <?php echo ($profit >= 0) ? 'card-header-warning' : 'card-header-danger'; ?> card-header-icon">
            <div class="card-icon">
              <i class="material-icons"><?php echo ($profit >= 0) ? 'monetization_on' : 'money_off'; ?></i>
            </div>
            <p class="card-category"><?php echo ($profit >= 0) ? 'กำไรสุทธิ' : 'ขาดทุน'; ?></p>
            <h3 class="card-title" style="color:<?php echo ($profit >= 0) ? '#4caf50' : '#f44336'; ?>">
              <?php echo ($profit >= 0) ? '+' : ''; ?><?php echo number_format($profit, 2); ?> <small>บาท</small>
            </h3>
          </div>
          <div class="card-footer">
            <div class="stats">
              <i class="material-icons"><?php echo ($profit >= 0) ? 'thumb_up' : 'thumb_down'; ?></i>
              <?php echo ($profit >= 0) ? 'รายรับมากกว่ารายจ่าย' : 'รายจ่ายมากกว่ารายรับ'; ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <?php if($unpaid_count > 0): ?>
    <!-- แจ้งเตือนค้างชำระ -->
    <div class="row mt-2">
      <div class="col-md-12">
        <div class="alert" style="background:#ff6f00; color:#fff; border-radius:10px; padding:15px 20px; display:flex; align-items:center; justify-content:space-between;">
          <span>
            <i class="material-icons" style="vertical-align:middle; margin-right:8px;">notification_important</i>
            <b>มีบิลค้างชำระ <?php echo $unpaid_count; ?> รายการ</b> &nbsp; รวมยอด <b><?php echo number_format($unpaid_total, 2); ?> บาท</b>
          </span>
          <a href="salesofday.php" class="btn btn-sm" style="background:#fff; color:#ff6f00; font-weight:bold;">
            จัดการบิลค้างชำระ
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include "footer.php"; ?>