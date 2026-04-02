<?php
// ==========================================
// جلب الوصفات الطبية الخاصة بالمريض
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id > 0) {
    // دمج جدول الوصفات مع جدول الطلبات لجلب الوصفات التابعة لهذا المريض فقط
    $query = "
        SELECT p.PrescriptionID, p.ImagePath, p.IsVerified, o.OrderID, o.OrderDate
        FROM Prescription p
        JOIN `Order` o ON p.OrderID = o.OrderID
        WHERE o.PatientID = $patient_id
        ORDER BY o.OrderDate DESC
    ";

    $result = mysqli_query($conn, $query);
    $prescriptions = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $prescriptions[] = $row;
        }
    }

    echo json_encode([
        "status" => "success",
        "prescriptions" => $prescriptions
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "رقم المريض غير صالح"
    ]);
}
?>  