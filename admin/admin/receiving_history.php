<?php
session_start();
include("../../db.php");

// ===== ลบประวัติรับของเข้า (Admin เท่านั้น) =====
if(isset($_GET['delete_id']) && $_SESSION['role'] === 'Admin') {
    $del_id = (int)$_GET['delete_id'];
    try {
        // ลบ receiving_detail ก่อน แล้วลบ receiving header
        mysqli_query($con, "DELETE FROM receiving_detail WHERE receive_id = '$del_id'");
        if(mysqli_query($con, "DELETE FROM receiving WHERE receive_id = '$del_id'")) {
            echo "<script>alert('ลบประวัติการรับของเข้าเรียบร้อยแล้ว!'); window.location.href='receiving_history.php';</script>";
        } else {
            echo "<script>alert('ไม่สามารถลบได้'); window.location.href='receiving_history.php';</script>";
        }
    } catch(Exception $e) {
        echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); window.location.href='receiving_history.php';</script>";
    }
    exit();
}

include "sidenav.php";
include "topheader.php";
?>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-success" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 class="card-title">ประวัติการรับของเข้า (Receiving History)</h4>
                <p class="card-category">รายการสั่งซื้อและรับวัสดุก่อสร้างเข้าโกดัง</p>
            </div>
            <a href="stock_in.php" class="btn btn-info btn-sm">รับของเข้าเพิ่ม</a>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="text-success">
                  <th>รหัสรับเข้า</th>
                  <th>วันที่รับของ</th>
                  <th>บริษัทคู่ค้า (Supplier)</th>
                  <th>เลขที่บิลโรงงาน</th>
                  <th>ยอดรวมต้นทุน (บาท)</th>
                  <th>จัดการ</th>
                </thead>
                <tbody>
                  <?php 
                    $sql = "SELECT r.*, s.supplier_name 
                            FROM receiving r 
                            LEFT JOIN suppliers s ON r.supplier_id = s.supplier_id 
                            ORDER BY r.receive_date DESC";
                    
                    $result = mysqli_query($con, $sql);
                    
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_array($result)) {
                            $date = date_create($row['receive_date']);
                            $formatted_date = date_format($date, "d/m/Y H:i");
                            $rec_code = 'REC-IN' . sprintf('%04d', $row['receive_id']);

                            echo "<tr>";
                            echo "<td><b>$rec_code</b></td>";
                            echo "<td>" . $formatted_date . "</td>";
                            echo "<td>" . $row['supplier_name'] . "</td>";
                            echo "<td>" . ($row['invoice_no'] ? $row['invoice_no'] : '-') . "</td>";
                            echo "<td class='text-danger'><b>" . number_format($row['total_amount'], 2) . "</b></td>";
                            echo "<td style='white-space:nowrap;'>
                                    <a href='view_receiving.php?id=".$row['receive_id']."' class='btn btn-sm btn-success'><i class='material-icons'>visibility</i> ดูรายละเอียด</a>";
                            // ปุ่มลบ — เฉพาะ Admin
                            if(isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
                                echo " <a href='#' class='btn btn-sm btn-danger' onclick='confirmDelRecv(".$row['receive_id'].",\"".$rec_code."\")'><i class='material-icons'>delete</i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>ยังไม่มีประวัติการรับของเข้า</td></tr>";
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

<script>
function confirmDelRecv(id, code) {
    if(confirm('ต้องการลบประวัติการรับของ ' + code + ' ใช่ไหม?\n\n⚠️ รายการสินค้าที่รับเข้าในบิลนี้จะถูกลบด้วย (สต็อกไม่ลดลงอัตโนมัติ)')) {
        window.location.href = 'receiving_history.php?delete_id=' + id;
    }
}
</script>

<?php include "footer.php"; ?>