<?php
session_start();
include("../../db.php");

// ===== Admin และ Stock เท่านั้น =====
$_r = $_SESSION['role'] ?? '';
if($_r !== 'Admin' && $_r !== 'Stock') {
    echo "<script>alert('ขออภัย! เฉพาะ Admin และ Stock เท่านั้น'); window.location.href='index.php';</script>";
    exit();
}

if(isset($_POST['btn_stock_in'])) {
    $supplier_id = $_POST['supplier_id'];
    $invoice_no = $_POST['invoice_no'];
    $note = $_POST['note'];
    $employee_id = 1;

    // รับข้อมูล Array
    $product_ids = $_POST['product_id'];
    $cost_prices = $_POST['cost_price'];

    // ===== Packaging Conversion =====
    // qty[] = base unit (กรอกตรงๆ)
    // pkg_qty[] = packaging qty → ต้องแปลงเป็น base ก่อน
    $qtys = [];
    $all_ids = $product_ids;
    foreach($all_ids as $i => $pid) {
        if(isset($_POST['pkg_qty'][$i]) && $_POST['pkg_qty'][$i] > 0) {
            // โหมด Packaging: ดึง rate จาก product_packaging
            $pkg_qty_val = (float)$_POST['pkg_qty'][$i];
            // ดึง units_per_package ที่ตรงกับ package ที่ส่งมา
            // ส่งมาเป็น hidden field pkg_rate[]
            $pkg_rate = isset($_POST['pkg_rate'][$i]) ? (float)$_POST['pkg_rate'][$i] : 1;
            $qtys[$i] = round($pkg_qty_val * $pkg_rate, 4);
        } else {
            $qtys[$i] = (float)($_POST['qty'][$i] ?? 0);
        }
    }

    $total_amount = 0;
    for($i = 0; $i < count($product_ids); $i++) {
        $total_amount += ($qtys[$i] * $cost_prices[$i]);
    }

    // บันทึกหัวบิลรับเข้า
    $sql_receive = "INSERT INTO receiving (supplier_id, invoice_no, employee_id, total_amount, note) 
                    VALUES ('$supplier_id', '$invoice_no', '$employee_id', '$total_amount', '$note')";
    
    if(mysqli_query($con, $sql_receive)) {
        $receive_id = mysqli_insert_id($con);

        // วนลูปบันทึกรายละเอียด และบวกสต๊อก
        for($i = 0; $i < count($product_ids); $i++) {
            $p_id = $product_ids[$i];
            $q = $qtys[$i];
            $cp = $cost_prices[$i];

            mysqli_query($con, "INSERT INTO receiving_detail (receive_id, product_id, qty, cost_price) 
                                VALUES ('$receive_id', '$p_id', '$q', '$cp')");
            
            // บวกสต๊อก และอัปเดตต้นทุนล่าสุด
            mysqli_query($con, "UPDATE products SET stock_qty = stock_qty + $q, cost_price = '$cp' WHERE product_id = '$p_id'");
        }

        echo "<script>alert('บันทึกรับสินค้าเข้าโกดังสำเร็จ!'); window.location.href='products_list.php';</script>";
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
    
</style>

<div class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header card-header-success">
        <h4 class="card-title">รับสินค้าเข้าโกดัง </h4>
      </div>
      <div class="card-body">
        <form action="" method="post">
          <div class="row mb-3">
            <div class="col-md-4">
              <label>ผู้จัดจำหน่าย (ซัพพลายเออร์)</label>
              <select name="supplier_id" class="form-control" required>
                <option value="">-- เลือกบริษัท --</option>
                <?php 
                  $sup_q = mysqli_query($con, "SELECT * FROM suppliers");
                  while($s = mysqli_fetch_array($sup_q)){ echo "<option value='".$s['supplier_id']."'>".$s['supplier_name']."</option>"; }
                ?>
              </select>
            </div>
            <div class="col-md-4">
              <label>เลขที่บิลโรงงาน</label>
              <input type="text" name="invoice_no" class="form-control">
            </div>
            <div class="col-md-4">
              <label>หมายเหตุ</label>
              <input type="text" name="note" class="form-control">
            </div>
          </div>

          <table class="table table-bordered">
            <thead class="text-success">
              <tr>
                <th width="38%">เลือกสินค้า</th>
                <th width="20%">จำนวนที่รับเข้า</th>
                <th width="22%">ต้นทุนต่อหน่วย</th>
                <th width="10%">ลบ</th>
              </tr>
            </thead>
            <tbody id="item-tbody">
              <tr>
                <td>
                  <select name="product_id[]" class="form-control prod-select" required onchange="loadPackaging(this)">
                    <option value="" data-cost="0">-- เลือกสินค้า --</option>
                    <?php 
                      $prod_query = mysqli_query($con, "SELECT * FROM products ORDER BY product_name ASC");
                      while($p = mysqli_fetch_array($prod_query)){
                        echo "<option value='".$p['product_id']."' data-cost='".$p['cost_price']."'>[".$p['product_code']."] ".$p['product_name']."</option>";
                      }
                    ?>
                  </select>
                  <!-- packaging toggle area -->
                  <div class="pkg-area" style="margin-top:8px; display:none;">
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                      <label class="pkg-toggle-label" style="font-size:12px; color:#aaa; margin:0;">
                        <input type="checkbox" class="pkg-toggle" onchange="togglePkgMode(this)"> ใช้หน่วย Packaging
                      </label>
                      <select class="form-control pkg-select" style="width:auto; display:none; padding:2px 8px; font-size:13px;" onchange="updatePkgCalc(this)">
                        <option value="">เลือกหน่วย Packaging</option>
                      </select>
                      <span class="pkg-calc" style="color:#00b894; font-size:13px;"></span>
                    </div>
                  </div>
                </td>
                <td>
                  <!-- hidden rate submitted always — JS updates value -->
                  <input type="hidden" name="pkg_rate[]" class="pkg-rate-input" value="0">
                  <input type="number" name="qty[]" class="form-control qty-input" min="1" required oninput="updatePkgCalcFromQty(this)">
                </td>
                <td><input type="number" step="0.01" name="cost_price[]" class="form-control cost-input" required></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="material-icons">close</i></button></td>
              </tr>
            </tbody>
          </table>

          <button type="button" class="btn btn-info btn-sm" id="add-row"><i class="material-icons">add</i> เพิ่มรายการสินค้า</button>
          
          <button type="submit" name="btn_stock_in" class="btn btn-success pull-right">บันทึกสต๊อกรับเข้า</button>
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
        newRow.find("input, select").val("");
        newRow.find(".pkg-rate-input").val("0");
        newRow.find(".pkg-area").hide();
        newRow.find(".pkg-select").hide().html('<option value="">เลือกหน่วย Packaging</option>');
        newRow.find(".pkg-calc").text("");
        newRow.find(".pkg-toggle").prop("checked", false);
        newRow.find(".qty-input").attr("name", "qty[]");
        newRow.find(".prod-select")[0].onchange = function(){ loadPackaging(this); };
        $("#item-tbody").append(newRow);
    });
    $("body").on("click", ".remove-row", function(){
        if($("#item-tbody tr").length > 1){
            $(this).closest("tr").remove();
        } else { alert("ต้องมีอย่างน้อย 1 รายการ"); }
    });
    $("body").on("change", ".prod-select", function(){
        var cost = $(this).find(":selected").data("cost");
        $(this).closest("tr").find(".cost-input").val(cost);
    });
});

// ===== Packaging AJAX & UI =====
async function loadPackaging(sel) {
    var pid = sel.value;
    var row = sel.closest('tr');
    var cost = sel.options[sel.selectedIndex].dataset.cost || 0;
    row.querySelector('.cost-input').value = cost;
    var pkgArea   = row.querySelector('.pkg-area');
    var pkgSelect = row.querySelector('.pkg-select');
    var pkgCalc   = row.querySelector('.pkg-calc');
    var toggle    = row.querySelector('.pkg-toggle');
    pkgArea.style.display = 'none';
    pkgSelect.style.display = 'none';
    pkgSelect.innerHTML = '<option value="">เลือกหน่วย Packaging</option>';
    pkgCalc.textContent = '';
    toggle.checked = false;
    // reset qty name to base
    row.querySelector('.qty-input').name = 'qty[]';
    row.querySelector('.qty-input').removeAttribute('data-rate');
    if(!pid) return;
    const res = await fetch('get_packaging.php?product_id='+pid);
    const data = await res.json();
    if(data.packages && data.packages.length > 0) {
        pkgArea.style.display = 'block';
        data.packages.forEach(function(p){
            var opt = document.createElement('option');
            opt.value = p.units_per_package;
            opt.dataset.unit = p.package_unit;
            opt.dataset.base = data.base_unit;
            opt.textContent = p.label; // "1 พาเหรด = 40 ถุง"
            pkgSelect.appendChild(opt);
        });
    }
}

function togglePkgMode(chk) {
    var row = chk.closest('tr');
    var pkgSelect = row.querySelector('.pkg-select');
    var qtyInput  = row.querySelector('.qty-input');
    var rateInput = row.querySelector('.pkg-rate-input');
    if(chk.checked) {
        pkgSelect.style.display = 'inline-block';
        qtyInput.name = 'pkg_qty[]'; // submit เป็น pkg, PHP จะแปลง
    } else {
        pkgSelect.style.display = 'none';
        qtyInput.name = 'qty[]';
        if(rateInput) rateInput.value = '0';
        row.querySelector('.pkg-calc').textContent = '';
    }
    updatePkgCalc(pkgSelect);
}

function updatePkgCalc(sel) {
    var row      = sel.closest('tr');
    var rate     = parseFloat(sel.value) || 0;
    var qtyInput = row.querySelector('.qty-input');
    var pkgCalc  = row.querySelector('.pkg-calc');
    var rateInput = row.querySelector('.pkg-rate-input');
    if(rateInput) rateInput.value = rate; // บันทึก rate ลง hidden field ทันที
    var opt  = sel.options[sel.selectedIndex];
    var unit = opt ? (opt.dataset.unit || '') : '';
    var base = opt ? (opt.dataset.base || '') : '';
    var qty  = parseFloat(qtyInput.value) || 0;
    if(rate > 0 && qty > 0)
        pkgCalc.textContent = qty + ' ' + unit + ' × ' + rate + ' = ' + (qty*rate) + ' ' + base;
    else if(rate > 0)
        pkgCalc.textContent = '1 ' + unit + ' = ' + rate + ' ' + base;
    else
        pkgCalc.textContent = '';
}

function updatePkgCalcFromQty(qtyInput) {
    var row = qtyInput.closest('tr');
    var pkgSelect = row.querySelector('.pkg-select');
    if(pkgSelect.style.display !== 'none') updatePkgCalc(pkgSelect);
}

// ===== ลบ submit handler เดิมที่ inject ใน tr (obsolete) =====
</script>

<?php include "footer.php"; ?>