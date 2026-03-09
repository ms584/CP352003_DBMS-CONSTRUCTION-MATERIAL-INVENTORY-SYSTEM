
    <?php
session_start();
include("../../db.php");

// อัปเดตสถานะการชำระเงิน: ค้างชำระ → ชำระแล้ว
if(isset($_GET['confirm_pay']) && is_numeric($_GET['confirm_pay'])) {
    $sale_id_pay = intval($_GET['confirm_pay']);
    $update_sql = "UPDATE sales SET payment_status = 'ชำระแล้ว' WHERE sale_id = '$sale_id_pay'";
    if(mysqli_query($con, $update_sql)) {
        echo "<script>alert('อัปเดตสถานะเป็น ชำระแล้ว เรียบร้อย!'); window.location.href='salesofday.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($con) . "');</script>";
    }
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
                <h4 class="card-title">ประวัติการออกบิล (Bill History)</h4>
                <p class="card-category">รายการบิลขายหน้าร้าน และการเบิกสินค้าออกจากสต๊อกทั้งหมด</p>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="text-primary">
                  <th>เลขที่บิล (Receipt No.)</th>
                  <th>วันที่ทำรายการ</th>
                  <th>พนักงานที่ทำรายการ</th>
                  <th>รูปแบบชำระเงิน</th>
                  <th>ยอดรวม (บาท)</th>
                  <th>สถานะการชำระเงิน</th> <th>จัดการ</th>
                </thead>
                <tbody>
                  <?php 
                    // ดึงข้อมูลหัวบิลจากตาราง sales และดึงชื่อพนักงานจาก employees
                    $sql = "SELECT s.*, e.full_name 
                            FROM sales s 
                            LEFT JOIN employees e ON s.employee_id = e.employee_id 
                            ORDER BY s.sale_date DESC";
                    
                    $result = mysqli_query($con, $sql);
                    
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_array($result)) {
                            
                            // 1. จัดการป้ายกำกับสำหรับรูปแบบชำระเงิน 
                            $pay_badge = "";
                            if($row['payment_method'] == 'Cash') {
                                $pay_badge = "<span class='badge badge-success' style='padding:5px 10px;'>เงินสด</span>";
                            } elseif($row['payment_method'] == 'Transfer') {
                                $pay_badge = "<span class='badge badge-info' style='padding:5px 10px;'>โอนเงิน</span>";
                            } else {
                                $pay_badge = "<span class='badge badge-warning' style='padding:5px 10px;'>เครดิต</span>";
                            }

                            //  2. การจัดการป้ายกำกับสำหรับสถานะการชำระเงิน 
                            $status_badge = "";
                            if($row['payment_status'] == 'ชำระแล้ว') {
                                $status_badge = "<span class='badge badge-success' style='padding:5px 10px;'>ชำระแล้ว</span>";
                            } else {
                                $status_badge = "<span class='badge badge-danger' style='padding:5px 10px;'>ค้างชำระ</span>";
                            }

                            // แปลงรูปแบบวันที่ให้อ่านง่าย
                            $date = date_create($row['sale_date']);
                            $formatted_date = date_format($date, "d/m/Y H:i:s");

                            echo "<tr>";
                            echo "<td><b>" . $row['receipt_no'] . "</b></td>";
                            echo "<td>" . $formatted_date . "</td>";
                            echo "<td>" . ($row['full_name'] ? $row['full_name'] : 'Admin') . "</td>";
                            echo "<td>" . $pay_badge . "</td>";
                            echo "<td class='text-primary'><b>" . number_format($row['total_amount'], 2) . "</b></td>";
                            
                            //  3. นำตัวแปร $status_badge มาแสดงในคอลัมน์ที่ 6
                            echo "<td>" . $status_badge;
                            // ถ้าค้างชำระ แสดงปุ่มยืนยันชำระเงิน
                            if($row['payment_status'] != 'ชำระแล้ว') {
                                echo "<br><a href='salesofday.php?confirm_pay=".$row['sale_id']."' 
                                           class='btn btn-success btn-sm mt-1' 
                                           style='font-size:11px; padding:3px 8px;'
                                           onclick='return confirm(\"ยืนยันว่าลูกค้าชำระเงินแล้วใช่ไหม?\")'>
                                        <i class='material-icons' style='font-size:14px;vertical-align:middle;'>check_circle</i>
                                        ยืนยันชำระเงิน
                                      </a>";
                            }
                            echo "</td>";
                            
                            echo "<td>
                              <a href='view_bill.php?sale_id=".$row['sale_id']."' class='btn btn-sm btn-info'><i class='material-icons'>visibility</i> ดูรายการสินค้า</a>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        
                        echo "<tr><td colspan='7' class='text-center'>ยังไม่มีประวัติการทำรายการเบิก/ขาย</td></tr>";
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

<?php include "footer.php"; ?>