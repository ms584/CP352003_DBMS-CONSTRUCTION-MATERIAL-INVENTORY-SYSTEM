<?php
session_start();
include("../../db.php");

if(!isset($_GET['sale_id'])) {
    echo "<script>window.location.href='salesofday.php';</script>";
    exit();
}
$sale_id = $_GET['sale_id'];

// 1. ดึงข้อมูลหัวบิล พร้อม JOIN ชื่อพนักงาน และ ชื่อลูกค้า
$sql_header = "SELECT s.*, e.full_name, c.customer_name, c.address, c.phone 
               FROM sales s 
               LEFT JOIN employees e ON s.employee_id = e.employee_id 
               LEFT JOIN customers c ON s.customer_id = c.customer_id
               WHERE s.sale_id = '$sale_id'";
$query_header = mysqli_query($con, $sql_header);
$bill = mysqli_fetch_array($query_header);

// กำหนดป้ายสีการชำระเงิน
$pay_method = "";
if($bill['payment_method'] == 'Cash') { $pay_method = "<span class='badge badge-success'>เงินสด</span>"; }
elseif($bill['payment_method'] == 'Transfer') { $pay_method = "<span class='badge badge-info'>โอนเงิน</span>"; }
else { $pay_method = "<span class='badge badge-warning'>เครดิต </span>"; }

include "sidenav.php";
include "topheader.php";
?>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-10 offset-md-1">
        <div class="card">
          <div class="card-header card-header-info" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 class="card-title">ใบเสร็จรับเงิน / ใบส่งสินค้า</h4>
                <p class="card-category">เลขที่เอกสาร: <b><?php echo $bill['receipt_no']; ?></b></p>
            </div>
            <a href="salesofday.php" class="btn btn-default btn-sm d-print-none">กลับหน้ารายการ</a>
          </div>
          <div class="card-body">
            
            <!-- ===== RECEIPT AREA ===== -->
            <div class="receipt-wrapper" id="receipt-area">

                <!-- HEADER -->
                <div class="receipt-header">
                    <div>
                        <div class="shop-name">🏗 CONSTRUCTSHOP</div>
                        <div class="shop-sub">ระบบคลังสินค้าวัสดุก่อสร้าง<br>โทร: +66 012-345-6789 | info@constructshop.com</div>
                    </div>
                    <div class="doc-info">
                        <div class="receipt-no"><?php echo $bill['receipt_no']; ?></div>
                        <small>
                            วันที่: <?php echo date('d/m/Y H:i:s', strtotime($bill['sale_date'])); ?><br>
                            ผู้เปิดบิล: <?php echo ($bill['full_name']) ? $bill['full_name'] : 'Admin'; ?><br>
                            วิธีชำระ: <?php
                                if($bill['payment_method'] == 'Cash') echo 'เงินสด';
                                elseif($bill['payment_method'] == 'Transfer') echo 'โอนเงิน';
                                else echo 'เครดิต';
                            ?>
                        </small>
                    </div>
                </div>

                <!-- INFO ROW -->
                <div class="receipt-info-row">
                    <div class="info-block">
                        <h6>ข้อมูลลูกค้า (Customer)</h6>
                        <p>
                            <b>ชื่อ:</b> <?php echo ($bill['customer_name']) ? $bill['customer_name'] : 'ลูกค้าทั่วไป (Walk-in)'; ?><br>
                            <b>เบอร์โทร:</b> <?php echo ($bill['phone']) ? $bill['phone'] : '-'; ?><br>
                            <b>ที่อยู่จัดส่ง:</b> <?php echo ($bill['address']) ? $bill['address'] : '-'; ?>
                        </p>
                    </div>
                    <div class="info-block" style="text-align:right;">
                        <h6>สถานะการชำระเงิน</h6>
                        <?php 
                        $sc = ($bill['payment_status'] == 'ชำระแล้ว') ? '#16a34a' : '#dc2626';
                        ?>
                        <span class="status-badge" style="background:<?php echo $sc; ?>; font-size:14px; padding:6px 18px;">
                            <?php echo $bill['payment_status']; ?>
                        </span>
                        <p style="margin-top:8px; font-size:12px; color:#6b7280;">ใบเสร็จรับเงิน / ใบส่งสินค้า</p>
                    </div>
                </div>

                <!-- TABLE -->
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="width:45px; text-align:center;">ลำดับ</th>
                            <th style="width:90px;">รหัสสินค้า</th>
                            <th>ชื่อรายการสินค้า</th>
                            <th style="text-align:center; width:100px;">จำนวน</th>
                            <th style="text-align:right; width:120px;">ราคาต่อหน่วย (฿)</th>
                            <th style="text-align:right; width:120px;">รวมเป็นเงิน (฿)</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php 
                        $sql_detail = "SELECT sd.*, p.product_code, p.product_name, u.unit_name 
                                       FROM sales_detail sd 
                                       LEFT JOIN products p ON sd.product_id = p.product_id 
                                       LEFT JOIN units u ON p.unit_id = u.unit_id
                                       WHERE sd.sale_id = '$sale_id'";
                        $result_detail = mysqli_query($con, $sql_detail);
                        $i = 1; $sum_total = 0;
                        if(mysqli_num_rows($result_detail) > 0) {
                            while($item = mysqli_fetch_array($result_detail)) {
                                $subtotal = $item['qty'] * $item['selling_price'];
                                $sum_total += $subtotal;
                                echo "<tr>";
                                echo "<td style='text-align:center; color:#6b7280;'>".$i++."</td>";
                                echo "<td style='color:#6b7280; font-size:12px;'>".$item['product_code']."</td>";
                                echo "<td style='font-weight:500;'>".$item['product_name']."</td>";
                                echo "<td style='text-align:center;'>".$item['qty']." ".$item['unit_name']."</td>";
                                echo "<td style='text-align:right;'>".number_format($item['selling_price'], 2)."</td>";
                                echo "<td style='text-align:right; font-weight:600;'>".number_format($subtotal, 2)."</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; color:#dc2626;'>ไม่พบรายการสินค้า</td></tr>";
                        }
                      ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align:right; color:#D10024;">ยอดรวมทั้งสิ้น:</td>
                            <td style="text-align:right; color:#D10024;">฿ <?php echo number_format($sum_total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- FOOTER NOTE -->
                <div class="receipt-footer-note">
                    <p>* ใบเสร็จนี้ออกโดยระบบอัตโนมัติ กรุณาเก็บไว้เป็นหลักฐานการชำระเงิน</p>
                    <div class="signature-block">
                        <div class="sig-line">
                            <div></div>
                            <small>ผู้รับสินค้า / ลูกค้า</small>
                        </div>
                        <div class="sig-line">
                            <div></div>
                            <small>ผู้ออกใบเสร็จ / เจ้าหน้าที่</small>
                        </div>
                    </div>
                </div>

            </div><!-- end receipt-wrapper -->

            <!-- ปุ่มพิมพ์ -->
            <div class="mt-3 text-center d-print-none">
                <button onclick="window.print()" class="print-btn">
                    🖨 พิมพ์ / บันทึกเป็น PDF
                </button>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.receipt-wrapper {
    max-width: 760px;
    margin: 20px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    font-family: 'Sarabun', 'Prompt', sans-serif;
    color: #1a1a2e;
}
.receipt-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #D10024 100%);
    color: #fff;
    padding: 25px 30px 18px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.receipt-header .shop-name { font-size: 20px; font-weight: 700; }
.receipt-header .shop-sub  { font-size: 11px; opacity: 0.8; margin-top: 4px; }
.receipt-header .doc-info  { text-align: right; }
.receipt-header .doc-info .receipt-no {
    font-size: 15px; font-weight: 700;
    background: rgba(255,255,255,0.15);
    border-radius: 6px; padding: 4px 12px;
    display: inline-block; margin-bottom: 5px;
}
.receipt-header .doc-info small { display: block; opacity: 0.85; font-size: 11px; line-height: 1.7; }
.receipt-info-row {
    display: flex; justify-content: space-between;
    padding: 16px 30px; background: #f9fafb;
    border-bottom: 2px solid #e5e7eb; gap: 20px;
}
.receipt-info-row .info-block h6 {
    font-weight: 700; color: #D10024; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;
}
.receipt-info-row .info-block p { margin: 0; font-size: 13px; color: #374151; line-height: 1.7; }
.receipt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.receipt-table thead tr { background: #1a1a2e; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.receipt-table thead th { padding: 9px 12px; font-weight: 600; }
.receipt-table tbody tr:nth-child(even) { background: #f9fafb; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.receipt-table tbody td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; }
.receipt-table tfoot td { padding: 13px 12px; font-size: 15px; font-weight: 700; border-top: 2px solid #D10024; }
.receipt-footer-note { padding: 14px 30px 22px; font-size: 12px; color: #6b7280; border-top: 1px dashed #e5e7eb; margin-top: 5px; }
.receipt-footer-note .signature-block { display: flex; justify-content: flex-end; gap: 80px; margin-top: 28px; }
.receipt-footer-note .sig-line { text-align: center; }
.receipt-footer-note .sig-line div { border-bottom: 1px solid #aaa; width: 140px; margin: 0 auto 5px; height: 30px; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #fff; }
.print-btn {
    background: #1a1a2e; color: #fff; border: none;
    padding: 10px 26px; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer; margin-bottom: 10px;
}
.print-btn:hover { background: #D10024; }

@media print {
    .d-print-none, .sidebar, .navbar, #footer-admin { display: none !important; }
    .main-panel { width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .content { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    .card-header { display: none !important; }
    .receipt-wrapper { box-shadow: none !important; max-width: 100% !important; margin: 0 !important; border-radius: 0 !important; page-break-inside: avoid; }
    @page { size: A4; margin: 8mm 12mm; }
}
</style>

<?php include "footer.php"; ?>