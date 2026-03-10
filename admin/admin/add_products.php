<?php
session_start();
include("../../db.php"); // ดึงไฟล์เชื่อมต่อ DB

// ===== Admin, Manager, Stock เท่านั้น =====
$_r = $_SESSION['role'] ?? '';
if(!in_array($_r, ['Admin','Manager','Stock'])) {
    echo "<script>alert('ขออภัย! ไม่มีสิทธิ์เพิ่มสินค้า'); window.location.href='index.php';</script>";
    exit();
}

// โค้ดสำหรับบันทึกข้อมูลเมื่อกดปุ่ม Submit
if(isset($_POST['btn_save'])) {
    $product_code = $_POST['product_code'];
    $product_name = $_POST['product_name'];
    $cost_price = $_POST['cost_price'];
    $selling_price = $_POST['selling_price'];
    $stock_qty = $_POST['stock_qty'];
    $min_stock = $_POST['min_stock'];

    // 1. รับค่าหมวดหมู่ 
    $category_id = $_POST['category_id'];

    // 2. รับค่าที่แอดมินพิมพ์เข้ามาเอง 
    $brand_name  = trim($_POST['brand_name']);
    $unit_name   = trim($_POST['unit_name']);
    $base_unit   = trim($_POST['base_unit']);  // หน่วยหลัก stock

    //  รับค่า Location 
    $location = trim($_POST['location']);

    // --- ระบบเช็คและสร้าง ยี่ห้อ อัตโนมัติ 
    $brand_id = 0;
    $chk_brand = mysqli_query($con, "SELECT brand_id FROM brands WHERE brand_name = '$brand_name'");
    if(mysqli_num_rows($chk_brand) > 0) {
        $r_brand = mysqli_fetch_array($chk_brand);
        $brand_id = $r_brand['brand_id'];
    } else {
        mysqli_query($con, "INSERT INTO brands (brand_name) VALUES ('$brand_name')");
        $brand_id = mysqli_insert_id($con);
    }

    // --- ระบบเช็คและสร้าง หน่วยนับ อัตโนมัติ 
    $unit_id = 0;
    $chk_unit = mysqli_query($con, "SELECT unit_id FROM units WHERE unit_name = '$unit_name'");
    if(mysqli_num_rows($chk_unit) > 0) {
        $r_unit = mysqli_fetch_array($chk_unit);
        $unit_id = $r_unit['unit_id'];
    } else {
        mysqli_query($con, "INSERT INTO units (unit_name) VALUES ('$unit_name')");
        $unit_id = mysqli_insert_id($con);
    }

    // 3. จัดการรูปภาพ
    $picture_name = $_FILES['picture']['name'];
    $picture_type = $_FILES['picture']['type'];
    $picture_tmp_name = $_FILES['picture']['tmp_name'];
    $picture_size = $_FILES['picture']['size'];

    if($picture_name != "") {
        $pic_name = time() . "_" . $picture_name;
        move_uploaded_file($picture_tmp_name, "../../product_images/" . $pic_name);
    } else {
        $pic_name = "default.jpg";
    }

    // 4. บันทึกข้อมูลลงฐานข้อมูล 
    $sql = "INSERT INTO products (product_code, product_name, category_id, brand_id, unit_id, base_unit, cost_price, selling_price, stock_qty, min_stock, location, product_image) 
            VALUES ('$product_code', '$product_name', '$category_id', '$brand_id', '$unit_id', '$base_unit', '$cost_price', '$selling_price', '$stock_qty', '$min_stock', '$location', '$pic_name')";
    
    if(mysqli_query($con, $sql)) {
        $new_product_id = mysqli_insert_id($con);
        // 5. บันทึก packaging options
        if(isset($_POST['pkg_unit']) && is_array($_POST['pkg_unit'])) {
            $pkg_units = $_POST['pkg_unit'];
            $pkg_rates = $_POST['pkg_rate'];
            for($i = 0; $i < count($pkg_units); $i++) {
                $pu = mysqli_real_escape_string($con, trim($pkg_units[$i]));
                $pr = (float)$pkg_rates[$i];
                if($pu != '' && $pr > 0) {
                    mysqli_query($con, "INSERT IGNORE INTO product_packaging (product_id, package_unit, units_per_package) VALUES ('$new_product_id', '$pu', '$pr')");
                }
            }
        }
        echo "<script>alert('เพิ่มรายการวัสดุก่อสร้างและสถานที่เก็บสำเร็จ!'); window.location.href='products_list.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
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
          <div class="card-header card-header-primary">
            <h4 class="card-title">เพิ่มรายการวัสดุก่อสร้างใหม่</h4>
            <p class="card-category">กรอกรายละเอียดสินค้าเพื่อนำเข้าสต๊อก</p>
          </div>
          <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
              
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="bmd-label-floating">รหัสสินค้า / Barcode</label>
                    <input type="text" name="product_code" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-8">
                  <div class="form-group">
                    <label class="bmd-label-floating">ชื่อวัสดุก่อสร้าง (เช่น ปูนเสือ 50กก.)</label>
                    <input type="text" name="product_name" class="form-control" required>
                  </div>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>หมวดหมู่</label> 
                    <select name="category_id" class="form-control" required style="background-color: transparent;">
                      <option value="" disabled selected>-- เลือกหมวดหมู่ --</option>
                      <?php 
                        // ดึงหมวดหมู่จากฐานข้อมูลมาแสดงเป็นตัวเลือก
                        $cat_query = mysqli_query($con, "SELECT * FROM categories");
                        while($row = mysqli_fetch_array($cat_query)){
                          echo "<option value='".$row['category_id']."' style='color: black;'>".$row['category_name']."</option>";
                        }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="bmd-label-floating">ยี่ห้อ (Brand)</label>
                    <input type="text" name="brand_name" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label class="bmd-label-floating">หน่วยนับ</label>
                    <input type="text" name="unit_name" class="form-control" required placeholder="ชิ้น">
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label class="bmd-label-floating">หน่วยหลัก (Base Unit)</label>
                    <input type="text" name="base_unit" class="form-control" placeholder="ถุง / เส้น / ตัว">
                  </div>
                </div>
              </div>

              <div class="row mt-3">
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="bmd-label-floating">ราคาต้นทุน (บาท)</label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="bmd-label-floating">ราคาขาย (บาท)</label>
                    <input type="number" step="0.01" name="selling_price" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="bmd-label-floating">จำนวนเริ่มต้นในโกดัง</label>
                    <input type="number" name="stock_qty" class="form-control" value="0" required>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label class="bmd-label-floating">จุดแจ้งเตือนของหมด (ชิ้น)</label>
                    <input type="number" name="min_stock" class="form-control" value="10" required>
                  </div>
                </div>
              </div>
              
              <div class="row mt-4">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>สถานที่เก็บ </label>
                    <input type="text" name="location" class="form-control" placeholder="เช่น โกดัง A, เชลฟ์ 2">
                  </div>
                </div>
                <div class="col-md-6">
                  <label>รูปภาพสินค้า</label>
                  <div>
                    <!-- hidden real input -->
                    <input type="file" name="picture" id="picture-input" accept=".jpg,.jpeg,.png,.webp,.gif"
                           style="display:none;" onchange="previewNewImage(this)">
                    <!-- styled button -->
                    <button type="button" class="btn btn-warning"
                            onclick="document.getElementById('picture-input').click()"
                            style="margin-bottom:6px;">
                      <i class="material-icons" style="vertical-align:middle; font-size:18px;">add_photo_alternate</i>
                      เลือกรูปภาพ
                    </button>
                    <span id="pic-filename" style="color:#aaa; font-size:13px; margin-left:8px;">ยังไม่ได้เลือกไฟล์</span>
                    <div id="pic-preview-wrap" style="display:none; margin-top:8px;">
                      <img id="pic-preview" src="" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #555;">
                    </div>
                    <div style="color:#888; font-size:12px; margin-top:4px;">รูปแบบที่รองรับ: JPG, PNG, WEBP, GIF (ไม่เกิน 5MB)</div>
                  </div>
                </div>
              </div>

              <!-- ===== Packaging Section ===== -->
              <div class="row mt-4">
                <div class="col-md-12">
                  <div style="background:rgba(255,255,255,0.05); border:1px dashed #6c757d; border-radius:8px; padding:16px;">
                    <h5 style="color:#a29bfe; margin-bottom:12px;">📦 ตั้งค่า Packaging <small style="color:#aaa; font-size:12px;">(optional — เว้นว่างถ้าไม่มีหน่วย packaging)</small></h5>
                    <table class="table" id="pkg-table" style="color:#ccc;">
                      <thead><tr>
                        <th>หน่วย Packaging</th>
                        <th>× จำนวนต่อ Package</th>
                        <th>ตัวอย่าง</th>
                        <th></th>
                      </tr></thead>
                      <tbody id="pkg-tbody"></tbody>
                    </table>
                    <button type="button" class="btn btn-sm" onclick="addPkgRow()" style="background:#6c5ce7; color:#fff;">
                      <i class="material-icons" style="font-size:16px; vertical-align:middle;">add</i> เพิ่มสูตร Packaging
                    </button>
                  </div>
                </div>
              </div>

              <button type="submit" name="btn_save" class="btn btn-primary pull-right mt-3">บันทึกสินค้าใหม่</button>
              <div class="clearfix"></div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>
<script>
function previewNewImage(input) {
    var filename = input.files[0] ? input.files[0].name : 'ยังไม่ได้เลือกไฟล์';
    document.getElementById('pic-filename').textContent = filename;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('pic-preview').src = e.target.result;
            document.getElementById('pic-preview-wrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<script>
function addPkgRow() {
    var base = document.querySelector('[name="base_unit"]').value || 'หน่วย';
    var idx  = document.querySelectorAll('#pkg-tbody tr').length;
    var row  = `<tr>
      <td><input type="text" name="pkg_unit[]" class="form-control pkg-unit" placeholder="เช่น พาเหรด"></td>
      <td><input type="number" name="pkg_rate[]" step="0.01" min="1" class="form-control pkg-rate" oninput="updateLabel(this)"></td>
      <td class="pkg-label" style="color:#00b894; font-size:13px; vertical-align:middle;">-</td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="material-icons" style="font-size:16px;">close</i></button></td>
    </tr>`;
    document.getElementById('pkg-tbody').insertAdjacentHTML('beforeend', row);
}
function updateLabel(input) {
    var base = document.querySelector('[name="base_unit"]').value || 'หน่วย';
    var row  = input.closest('tr');
    var unit = row.querySelector('.pkg-unit').value || '?';
    var rate = parseFloat(input.value) || 0;
    row.querySelector('.pkg-label').textContent = rate > 0 ? '1 '+unit+' = '+rate+' '+base : '-';
}
// update labels live when base_unit changes
document.querySelector('[name="base_unit"]').addEventListener('input', function(){
    document.querySelectorAll('.pkg-rate').forEach(function(el){ updateLabel(el); });
});
</script>