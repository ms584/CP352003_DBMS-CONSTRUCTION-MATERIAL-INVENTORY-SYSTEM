<?php
session_start();
include("../../db.php");

if(isset($_POST['btn_stock_out'])) {
    $payment_method = $_POST['payment_method'];
    $payment_status = $_POST['payment_status']; 
    
    // รับค่า customer_id (ถ้าไม่ได้เลือก จะให้เป็น NULL)
    $customer_id = !empty($_POST['customer_id']) ? "'" . $_POST['customer_id'] . "'" : "NULL";
    
    $employee_id = 1; // ดึงจาก Session แอดมิน
    $receipt_no = "REC-" . date('YmdHis');

    // รับข้อมูล Array สินค้า
    $product_ids = $_POST['product_id']; 
    $qtys = $_POST['qty'];
    $prices = $_POST['price'];

    $total_amount = 0;
    
    // 1. คำนวณยอดรวมทั้งหมด 
    for($i = 0; $i < count($product_ids); $i++) {
        // ถ้าแถวไหนไม่ได้เลือกสินค้า หรือไม่ได้กรอกจำนวน ให้ข้ามไปเลย 
        if(empty($product_ids[$i]) || empty($qtys[$i])) continue; 

        // แปลงค่าให้เป็นตัวเลขทศนิยมก่อนคำนวณ
        $q = (float)$qtys[$i];
        $p = (float)$prices[$i];
        
        $total_amount += ($q * $p);
    }

    // 2. บันทึกหัวบิล
    $sql_sale = "INSERT INTO sales (receipt_no, employee_id, customer_id, payment_method, payment_status, total_amount) 
                 VALUES ('$receipt_no', '$employee_id', $customer_id, '$payment_method', '$payment_status', '$total_amount')";
    
    if(mysqli_query($con, $sql_sale)) {
        $sale_id = mysqli_insert_id($con);
        $stock_error = ""; // เก็บ error ถ้าของไม่พอ

        // 3. ตรวจสอบ stock ก่อน และวนบันทึกรายละเอียด
        for($i = 0; $i < count($product_ids); $i++) {
            if(empty($product_ids[$i]) || empty($qtys[$i])) continue;

            $p_id = mysqli_real_escape_string($con, $product_ids[$i]);
            $q = (float)$qtys[$i];
            $p_price = (float)$prices[$i];

            // ดึง stock ปัจจุบันของสินค้านั้น
            $stock_check = mysqli_query($con, "SELECT product_name, stock_qty FROM products WHERE product_id = '$p_id'");
            $stock_row = mysqli_fetch_array($stock_check);
            $available_stock = (float)$stock_row['stock_qty'];

            // ตรวจสอบว่าเบิกเกิน stock ไหม
            if($q > $available_stock) {
                $stock_error = "สินค้า \"" . $stock_row['product_name'] . "\" มีในสต็อกเพียง " . (int)$available_stock . " " . " ชิ้น แต่ขอเบิก " . (int)$q . " ชิ้น!";
                break;
            }
        }

        if($stock_error != "") {
            // ยกเลิกบิลที่เพิ่งสร้าง เพราะ stock ไม่พอ
            mysqli_query($con, "DELETE FROM sales WHERE sale_id = '$sale_id'");
            echo "<script>alert('ไม่สามารถบันทึกได้!\\n\\n" . addslashes($stock_error) . "\\n\\nกรุณาตรวจสอบจำนวนสินค้าใหม่อีกครั้ง');</script>";
        } else {
            // stock เพียงพอ บันทึกรายละเอียดและตัดสต็อก
            for($i = 0; $i < count($product_ids); $i++) {
                if(empty($product_ids[$i]) || empty($qtys[$i])) continue;

                $p_id = mysqli_real_escape_string($con, $product_ids[$i]);
                $q = (float)$qtys[$i];
                $p_price = (float)$prices[$i];

                mysqli_query($con, "INSERT INTO sales_detail (sale_id, product_id, qty, selling_price) 
                                    VALUES ('$sale_id', '$p_id', '$q', '$p_price')");
                
                mysqli_query($con, "UPDATE products SET stock_qty = stock_qty - $q WHERE product_id = '$p_id'");
            }
            echo "<script>alert('ทำรายการเบิก/ขายสำเร็จ ออกบิลเรียบร้อย!'); window.location.href='salesofday.php';</script>";
        }
    } else {
        echo "<script>alert('Error: " . mysqli_error($con) . "');</script>";
    }
}

include "sidenav.php";
include "topheader.php";
?>
<style>
  
    select.form-control option {
        color: #000000 !important;
        background-color: #ffffff !important;
    }
    
   
    select.form-control {
        color: #ffffff !important; 
    }

    /* ช่องราคาต่อหน่วย - สีเข้มตามธีม */
    input.form-control.price-input {
        background-color: #1a1a2e !important;
        color: #ffffff !important;
        border: 1px solid #444 !important;
    }
    input.form-control.price-input::placeholder {
        color: #888 !important;
    }
</style>

<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header card-header-warning">
        <h4 class="card-title">เบิกสินค้า / ขายหน้าร้าน (ออกบิล)</h4>
      </div>
      <div class="card-body">
        <form action="" method="post">
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-primary"><b>เลือกลูกค้า / ผู้รับเหมา</b></label>
              <select name="customer_id" class="form-control">
                <option value="">-- ลูกค้าทั่วไป (Walk-in) --</option>
                <?php 
                  // ดึงข้อมูลจากตารางลูกค้า
                  $cust_q = mysqli_query($con, "SELECT * FROM customers ORDER BY customer_name ASC");
                  while($c = mysqli_fetch_array($cust_q)){ 
                      echo "<option value='".$c['customer_id']."'>".$c['customer_name']." (โทร: ".$c['phone'].")</option>"; 
                  }
                ?>
              </select>
            </div>
            <div class="col-md-6"> <label class="text-primary"><b>สถานะการชำระเงิน</b></label>
    <select name="payment_status" class="form-control" required>
        <option value="ชำระแล้ว">ชำระแล้ว (Paid)</option>
        <option value="ค้างชำระ">ค้างชำระ (Unpaid)</option>
    </select>
</div>
            <div class="col-md-6">
              <label class="text-primary"><b>รูปแบบการชำระเงิน</b></label>
              <select name="payment_method" class="form-control" required>
                <option value="Cash">เงินสด (Cash)</option>
                <option value="Transfer">โอนเงิน (Transfer)</option>
                <option value="Credit">เครดิต (Credit)</option>
              </select>
            </div>
          </div>

          <table class="table table-bordered mt-4">
            <thead class="text-primary">
              <tr>
                <th width="50%">เลือกสินค้า</th>
                <th width="20%">จำนวน</th>
                <th width="20%">ราคาต่อหน่วย</th>
                <th width="10%">ลบ</th>
              </tr>
            </thead>
            <tbody id="item-tbody">
              <tr>
                <td>
                  <select name="product_id[]" class="form-control prod-select" required>
                    <option value="" data-price="0">-- เลือกสินค้า --</option>
                    <?php 
                      $prod_query = mysqli_query($con, "SELECT * FROM products WHERE stock_qty > 0 ORDER BY product_name ASC");
                      while($p = mysqli_fetch_array($prod_query)){
                        echo "<option value='".$p['product_id']."' data-price='".$p['selling_price']."'>[".$p['product_code']."] ".$p['product_name']." (เหลือ: ".$p['stock_qty'].")</option>";
                      }
                    ?>
                  </select>
                </td>
                <td><input type="number" name="qty[]" class="form-control" min="1" placeholder="จำนวน" required></td>
                <td><input type="number" step="0.01" name="price[]" class="form-control price-input" placeholder="ราคา" readonly required></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="material-icons">close</i></button></td>
              </tr>
            </tbody>
          </table>

          <button type="button" class="btn btn-info btn-sm" id="add-row"><i class="material-icons">add</i> เพิ่มรายการสินค้า</button>
          
          <button type="submit" name="btn_stock_out" class="btn btn-warning pull-right">บันทึกบิลขาย & ตัดสต๊อก</button>
          <div class="clearfix"></div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/core/jquery.min.js"></script>
<script>
$(document).ready(function(){
    $("#add-row").click(function(){
        var newRow = $("#item-tbody tr:first").clone();
        newRow.find("input").val(""); 
        newRow.find("select").val(""); 
        $("#item-tbody").append(newRow); 
    });

    $("body").on("click", ".remove-row", function(){
        if($("#item-tbody tr").length > 1){
            $(this).closest("tr").remove();
        } else {
            alert("ต้องมีสินค้าอย่างน้อย 1 รายการในบิล");
        }
    });

    $("body").on("change", ".prod-select", function(){
        var price = $(this).find(":selected").data("price");
        $(this).closest("tr").find(".price-input").val(price);
    });
});
</script>

<?php include "footer.php"; ?>