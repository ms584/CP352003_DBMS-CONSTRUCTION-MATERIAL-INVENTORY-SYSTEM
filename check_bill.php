<?php
include "header.php";

$bill_found = false;
$error_msg = "";

if(isset($_POST['btn_check'])) {
    $receipt_no = mysqli_real_escape_string($con, $_POST['receipt_no']);
    $phone      = mysqli_real_escape_string($con, $_POST['phone']);

    $sql_check = "SELECT s.*, c.customer_name, c.phone, c.address, e.full_name as emp_name 
                  FROM sales s 
                  LEFT JOIN customers c ON s.customer_id = c.customer_id 
                  LEFT JOIN employees e ON s.employee_id = e.employee_id
                  WHERE s.receipt_no = '$receipt_no' AND c.phone = '$phone'";
    
    $query_check = mysqli_query($con, $sql_check);

    if(mysqli_num_rows($query_check) > 0) {
        $bill_found = true;
        $bill       = mysqli_fetch_array($query_check);
        $sale_id    = $bill['sale_id'];

        if($bill['payment_method'] == 'Cash')         { $pay_method_th = 'เงินสด'; }
        elseif($bill['payment_method'] == 'Transfer') { $pay_method_th = 'โอนเงิน'; }
        else                                           { $pay_method_th = 'เครดิต'; }

        $pay_status_color = ($bill['payment_status'] == 'ชำระแล้ว') ? '#16a34a' : '#dc2626';
    } else {
        $error_msg = "ไม่พบบิลเลขที่นี้ หรือเบอร์โทรศัพท์ไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง";
    }
}
?>

<!-- ซ่อน header/footer ของเว็บตอนพิมพ์ -->
<style>
/* ===== SCREEN STYLES ===== */
.receipt-wrapper {
    max-width: 800px;
    margin: 30px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.12);
    overflow: hidden;
    font-family: 'Sarabun', 'Prompt', sans-serif;
    color: #1a1a2e;
}
.receipt-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #D10024 100%);
    color: #fff;
    padding: 30px 35px 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.receipt-header .shop-name {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 1px;
}
.receipt-header .shop-sub {
    font-size: 12px;
    opacity: 0.8;
    margin-top: 4px;
}
.receipt-header .doc-info {
    text-align: right;
}
.receipt-header .doc-info .receipt-no {
    font-size: 18px;
    font-weight: 700;
    background: rgba(255,255,255,0.15);
    border-radius: 6px;
    padding: 4px 12px;
    display: inline-block;
    margin-bottom: 5px;
}
.receipt-header .doc-info small {
    display: block;
    opacity: 0.85;
    font-size: 12px;
    line-height: 1.6;
}
.receipt-info-row {
    display: flex;
    justify-content: space-between;
    padding: 18px 35px;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    gap: 20px;
}
.receipt-info-row .info-block h6 {
    font-weight: 700;
    color: #D10024;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}
.receipt-info-row .info-block p {
    margin: 0;
    font-size: 13px;
    color: #374151;
    line-height: 1.7;
}
.receipt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.receipt-table thead tr {
    background: #1a1a2e;
    color: #fff;
}
.receipt-table thead th {
    padding: 10px 14px;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.receipt-table tbody tr:nth-child(even) {
    background: #f9fafb;
}
.receipt-table tbody tr:hover {
    background: #fef2f2;
}
.receipt-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}
.receipt-table tfoot tr {
    background: #fff8f8;
}
.receipt-table tfoot td {
    padding: 14px 14px;
    font-size: 16px;
    font-weight: 700;
    border-top: 2px solid #D10024;
}
.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
}
.receipt-footer-note {
    padding: 15px 35px 25px;
    font-size: 12px;
    color: #6b7280;
    border-top: 1px dashed #e5e7eb;
    margin-top: 5px;
}
.receipt-footer-note .signature-block {
    display: flex;
    justify-content: flex-end;
    gap: 80px;
    margin-top: 30px;
}
.receipt-footer-note .sig-line {
    text-align: center;
}
.receipt-footer-note .sig-line div {
    border-bottom: 1px solid #aaa;
    width: 140px;
    margin: 0 auto 5px;
    height: 30px;
}
.print-btn {
    display: block;
    width: fit-content;
    margin: 20px auto;
    background: #1a1a2e;
    color: #fff;
    border: none;
    padding: 11px 28px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: background 0.2s;
}
.print-btn:hover { background: #D10024; }

/* ===== PRINT STYLES ===== */
@media print {
    /* ซ่อน navbar, footer, ส่วนหน้าเว็บ */
    #header, #footer, nav, .navbar,
    .section:not(.print-section),
    .print-hide, .primary-btn, .print-btn,
    form[action=""] { display: none !important; }

    body { margin: 0; padding: 0; background: #fff !important; }

    .receipt-wrapper {
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        page-break-inside: avoid;
    }
    .receipt-header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: linear-gradient(135deg, #1a1a2e 0%, #D10024 100%) !important;
    }
    .receipt-table thead tr {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: #1a1a2e !important;
        color: #fff !important;
    }
    .receipt-table tbody tr:nth-child(even) {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background: #f9fafb !important;
    }
    @page {
        size: A4;
        margin: 10mm 12mm;
    }
}
</style>

<div class="section print-hide" style="padding: 30px 0;">
  <div class="container">
    <div class="row">
      <div class="col-md-8 col-md-offset-2 text-center" style="margin-bottom: 30px;">
        <h2 style="color: #D10024; font-weight: 700;">ตรวจสอบบิล / ใบเสร็จรับเงิน</h2>
        <p style="color: #555;">กรุณากรอกเลขที่บิลและเบอร์โทรศัพท์ของคุณเพื่อดูรายละเอียดรายการสินค้า</p>
        
        <form action="" method="post" class="form-inline" style="margin-top: 20px; justify-content: center;">
            <input type="text" name="receipt_no" class="input" placeholder="เลขที่บิล (เช่น REC-...)" required
                   style="width: 240px; margin-right: 8px; border-radius:6px;">
            <input type="text" name="phone" class="input" placeholder="เบอร์โทรศัพท์ที่แจ้งไว้" required
                   style="width: 190px; margin-right: 8px; border-radius:6px;">
            <button type="submit" name="btn_check" class="primary-btn" style="border:none; padding: 10px 22px; border-radius:6px;">
                ค้นหาบิล
            </button>
        </form>

        <?php if($error_msg != "") { ?>
            <div style="color:#dc2626; margin-top:15px; font-weight:bold; background:#fef2f2; padding:10px 16px; border-radius:8px; display:inline-block;">
                ⚠️ <?php echo $error_msg; ?>
            </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php if($bill_found) { ?>
<!-- ===== ใบเสร็จ ===== -->
<div class="section print-section" style="padding: 0 0 40px;">
  <div class="container">
    <div class="receipt-wrapper" id="receipt-area">

        <!-- HEADER: ชื่อร้าน + เลขที่บิล -->
        <div class="receipt-header">
            <div>
                <div class="shop-name">🏗 CONSTRUCTSHOP</div>
                <div class="shop-sub">ระบบคลังสินค้าวัสดุก่อสร้าง<br>โทร: +66 012-345-6789 | info@constructshop.com</div>
            </div>
            <div class="doc-info">
                <div class="receipt-no"><?php echo $bill['receipt_no']; ?></div>
                <small>
                    วันที่: <?php echo date('d/m/Y H:i', strtotime($bill['sale_date'])); ?><br>
                    ผู้เปิดบิล: <?php echo $bill['emp_name'] ? $bill['emp_name'] : 'Admin'; ?><br>
                    วิธีชำระ: <?php echo $pay_method_th; ?>
                </small>
            </div>
        </div>

        <!-- INFO ROW: ลูกค้า + สถานะ -->
        <div class="receipt-info-row">
            <div class="info-block">
                <h6>ข้อมูลลูกค้า</h6>
                <p>
                    <b>ชื่อ-นามสกุล/บริษัท:</b> <?php echo $bill['customer_name']; ?><br>
                    <b>เบอร์โทร:</b> <?php echo $bill['phone']; ?><br>
                    <b>ที่อยู่จัดส่ง:</b> <?php echo ($bill['address']) ? $bill['address'] : '-'; ?>
                </p>
            </div>
            <div class="info-block" style="text-align:right;">
                <h6>สถานะการชำระเงิน</h6>
                <span class="status-badge" style="background:<?php echo $pay_status_color; ?>; font-size:14px; padding:6px 18px;">
                    <?php echo $bill['payment_status']; ?>
                </span>
                <p style="margin-top:8px; font-size:12px; color:#6b7280;">ใบเสร็จรับเงิน / ใบส่งสินค้า</p>
            </div>
        </div>

        <!-- TABLE: รายการสินค้า -->
        <table class="receipt-table">
            <thead>
                <tr>
                    <th style="width:45px; text-align:center;">ลำดับ</th>
                    <th>รายการสินค้า</th>
                    <th style="text-align:center; width:110px;">จำนวน</th>
                    <th style="text-align:right; width:120px;">ราคา/หน่วย</th>
                    <th style="text-align:right; width:120px;">รวมเป็นเงิน</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sql_detail = "SELECT sd.*, p.product_name, u.unit_name 
                               FROM sales_detail sd 
                               LEFT JOIN products p ON sd.product_id = p.product_id 
                               LEFT JOIN units u ON p.unit_id = u.unit_id
                               WHERE sd.sale_id = '$sale_id'";
                $res_detail = mysqli_query($con, $sql_detail);
                $i = 1; $sum_total = 0;
                while($item = mysqli_fetch_array($res_detail)) {
                    $subtotal   = $item['qty'] * $item['selling_price'];
                    $sum_total += $subtotal;
                    echo "<tr>";
                    echo "<td style='text-align:center; color:#6b7280;'>".$i++."</td>";
                    echo "<td style='font-weight:500;'>".$item['product_name']."</td>";
                    echo "<td style='text-align:center;'>".$item['qty']." ".$item['unit_name']."</td>";
                    echo "<td style='text-align:right;'>".number_format($item['selling_price'], 2)."</td>";
                    echo "<td style='text-align:right; font-weight:600;'>".number_format($subtotal, 2)."</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right; color:#D10024;">ยอดชำระทั้งสิ้น:</td>
                    <td style="text-align:right; color:#D10024;">฿ <?php echo number_format($sum_total, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- FOOTER NOTE: ลายเซ็น + หมายเหตุ -->
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

    <!-- ปุ่มพิมพ์ (ซ่อนตอนพิมพ์) -->
    <button onclick="window.print()" class="print-btn print-hide">
        🖨 พิมพ์ / บันทึกเป็น PDF
    </button>

  </div>
</div>
<?php } ?>

<?php include "footer.php"; ?>