<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id > 0) {
    // 1. جلب الطلبات الأساسية لهذا المريض
    $orderQuery = "
        SELECT 
            o.OrderID, o.OrderDate, o.Status, o.TotalAmount, o.RejectionReason,
            ph.PharmacyName, ph.Logo
        FROM `Order` o
        -- لمعرفة اسم الصيدلية، نربط الطلب بأول دواء فيه، ومن الدواء نعرف الصيدلية
        JOIN OrderItems oi ON o.OrderID = oi.OrderID
        JOIN PharmacyStock ps ON oi.StockID = ps.StockID
        JOIN Pharmacist ph ON ps.PharmacistID = ph.PharmacistID
        WHERE o.PatientID = $patient_id
        GROUP BY o.OrderID
        ORDER BY o.OrderDate DESC
    ";

    $orderResult = mysqli_query($conn, $orderQuery);
    $orders = [];

    if ($orderResult && mysqli_num_rows($orderResult) > 0) {
        while ($orderRow = mysqli_fetch_assoc($orderResult)) {
            $order_id = $orderRow['OrderID'];

            // 2. جلب الأدوية (العناصر) داخل هذا الطلب
            $itemsQuery = "
                SELECT 
                    oi.Quantity, oi.SoldPrice, 
                    sm.Name as MedicineName, sm.Image
                FROM OrderItems oi
                JOIN PharmacyStock ps ON oi.StockID = ps.StockID
                JOIN SystemMedicine sm ON ps.SystemMedID = sm.SystemMedID
                WHERE oi.OrderID = $order_id
            ";
            $itemsResult = mysqli_query($conn, $itemsQuery);
            $items = [];
            while ($itemRow = mysqli_fetch_assoc($itemsResult)) {
                $items[] = $itemRow;
            }

            // إضافة الأدوية كقائمة فرعية داخل الطلب
            $orderRow['items'] = $items;
            $orders[] = $orderRow;
        }
    }

    echo json_encode([
        "status" => "success",
        "orders" => $orders
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "رقم المريض غير صالح"
    ]);
}
