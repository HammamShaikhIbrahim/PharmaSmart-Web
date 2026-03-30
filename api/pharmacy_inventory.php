<?php
// ==========================================
// ملف API لعرض مخزون صيدلية معينة
// يُوضع في: PharmaSmart_Web/api/pharmacy_inventory.php
// ==========================================
// 💡 تم تحديثه ليُرجع StockID, SystemMedID, IsControlled اللازمة للسلة

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$pharmacy_id = isset($_GET['pharmacy_id']) ? (int)$_GET['pharmacy_id'] : 0;

if ($pharmacy_id <= 0) {
    echo json_encode(["status" => "error", "items" => [], "message" => "معرّف الصيدلية غير صالح"]);
    exit();
}

$query = "SELECT 
              ps.StockID,
              ps.SystemMedID,
              ps.Price,
              ps.Stock,
              sm.Name,
              sm.ScientificName,
              sm.Image,
              sm.IsControlled,
              sm.Description,
              c.NameAR AS CategoryName
          FROM PharmacyStock ps
          JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
          LEFT JOIN Category c ON sm.CategoryID = c.CategoryID
          WHERE ps.PharmacistID = $pharmacy_id
            AND ps.Stock > 0
          ORDER BY sm.Name ASC";

$result = mysqli_query($conn, $query);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode([
    "status" => "success",
    "items" => $items
]);
?>
