<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$query_term = isset($_GET['query']) ? mysqli_real_escape_string($conn, $_GET['query']) : '';
$cat_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 0;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 0;
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($conn, $_GET['sort_by']) : 'price';

$where_clauses = ["ps.Stock > 0", "ps.ExpiryDate >= CURDATE()", "ph.IsApproved = 1"];

if (!empty($query_term)) {
    $where_clauses[] = "(sm.Name LIKE '%$query_term%' OR sm.ScientificName LIKE '%$query_term%' OR ph.PharmacyName LIKE '%$query_term%')";
}

if ($cat_id > 0) {
    $where_clauses[] = "sm.CategoryID = $cat_id";
}

$where_sql = implode(" AND ", $where_clauses);

// حساب المسافة بالكيلومتر
$distance_sql = ($lat != 0 && $lng != 0)
    ? "(6371 * acos(cos(radians($lat)) * cos(radians(ph.Latitude)) * cos(radians(ph.Longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(ph.Latitude))))"
    : "0";

// تحديد طريقة الترتيب
$order_sql = ($sort_by == 'distance' && $lat != 0) ? "Distance ASC, ps.Price ASC" : "ps.Price ASC";

// 💡 التعديل هنا: أضفنا ps.StockID لكي يفهمه الموبايل ويرسله للسلة
$sql = "
    SELECT
        ps.StockID, 
        sm.SystemMedID, sm.Name as MedName, sm.ScientificName, sm.Image, sm.IsControlled,
        ps.Price, ps.Stock,
        ph.PharmacyName, ph.Location, ph.PharmacistID, ph.Latitude, ph.Longitude,
        c.NameAR as CategoryName,
        $distance_sql AS Distance
    FROM SystemMedicine sm
    JOIN PharmacyStock ps ON sm.SystemMedID = ps.SystemMedID
    JOIN Pharmacist ph ON ps.PharmacistID = ph.PharmacistID
    JOIN Category c ON sm.CategoryID = c.CategoryID
    WHERE $where_sql
    ORDER BY $order_sql
";

$result = mysqli_query($conn, $sql);
$search_results = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['Price'] = number_format((float)$row['Price'], 2, '.', '');
        $row['Distance'] = number_format((float)$row['Distance'], 1, '.', '');
        $search_results[] = $row;
    }
}

echo json_encode(["status" => "success", "results" => $search_results]);
