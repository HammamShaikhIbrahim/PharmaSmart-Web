<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$response = [
    'status' => 'success',
    'categories' => [],
    'pharmacies' => []
];

try {
    // 1. جلب التصنيفات
    $catResult = mysqli_query($conn, "SELECT CategoryID, NameAR FROM Category");
    while ($row = mysqli_fetch_assoc($catResult)) {
        $response['categories'][] = $row;
    }

    // 2. جلب الصيدليات (تمت إضافة WorkingHours هنا لحل المشكلة!)
    $pharQuery = "
        SELECT p.PharmacistID, p.PharmacyName, p.Location, p.WorkingHours, p.Latitude, p.Longitude, p.Logo,
               u.Fname, u.Lname, u.Phone
        FROM Pharmacist p
        JOIN User u ON p.PharmacistID = u.UserID
        WHERE p.IsApproved = 1
    ";
    $pharResult = mysqli_query($conn, $pharQuery);
    while ($row = mysqli_fetch_assoc($pharResult)) {
        $response['pharmacies'][] = $row;
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
