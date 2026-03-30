<?php
// ==========================================
// ملف API لعرض تفاصيل الدواء والصيدليات المتوفر فيها
// يُوضع في: PharmaSmart_Web/api/medicine_details.php
// ==========================================
// 💡 تم تحديثه ليُرجع StockID و PharmacistID اللذان تحتاجهما السلة

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "معرّف الدواء غير صالح"]);
    exit();
}

// 1. جلب تفاصيل الدواء من الكتالوج الموحد
$medQuery = "SELECT sm.*, c.NameAR AS CategoryName 
             FROM SystemMedicine sm 
             LEFT JOIN Category c ON sm.CategoryID = c.CategoryID 
             WHERE sm.SystemMedID = $id";

$medResult = mysqli_query($conn, $medQuery);

if (mysqli_num_rows($medResult) == 0) {
    echo json_encode(["status" => "error", "message" => "الدواء غير موجود"]);
    exit();
}

$details = mysqli_fetch_assoc($medResult);

// 2. جلب الصيدليات التي يتوفر فيها هذا الدواء (مع StockID و PharmacistID)
$phQuery = "SELECT 
                ps.StockID,
                ps.PharmacistID,
                ps.Price,
                ps.Stock,
                ph.PharmacyName,
                ph.Location,
                ph.Latitude,
                ph.Longitude,
                ph.Logo
            FROM PharmacyStock ps
            JOIN Pharmacist ph ON ps.PharmacistID = ph.PharmacistID
            WHERE ps.SystemMedID = $id 
              AND ps.Stock > 0
            ORDER BY ps.Price ASC";

$phResult = mysqli_query($conn, $phQuery);

$pharmacies = [];
while ($row = mysqli_fetch_assoc($phResult)) {
    $pharmacies[] = $row;
}

echo json_encode([
    "status" => "success",
    "details" => $details,
    "pharmacies" => $pharmacies
]);
?>
