<?php
// ==========================================
// ملف API لإنشاء طلب جديد (create_order.php)
// يُوضع في: PharmaSmart_Web/api/create_order.php
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

// استلام البيانات من التطبيق
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->patient_id) &&
    !empty($data->total_amount) &&
    !empty($data->items) &&
    is_array($data->items)
) {
    $patient_id = (int)$data->patient_id;
    $total_amount = (float)$data->total_amount;
    $delivery_address = isset($data->delivery_address) ? mysqli_real_escape_string($conn, $data->delivery_address) : '';
    $delivery_lat = isset($data->delivery_lat) ? (float)$data->delivery_lat : null;
    $delivery_lng = isset($data->delivery_lng) ? (float)$data->delivery_lng : null;

    // ==========================================
    // بدء Transaction لضمان سلامة البيانات
    // ==========================================
    mysqli_begin_transaction($conn);

    try {
        // 1. إنشاء الطلب في جدول Order
        $lat_val = $delivery_lat !== null ? $delivery_lat : "NULL";
        $lng_val = $delivery_lng !== null ? $delivery_lng : "NULL";

        $orderQuery = "INSERT INTO `Order` (PatientID, TotalAmount, PaymentMethod, DeliveryAddress, DeliveryLatitude, DeliveryLongitude, Status)
                       VALUES ($patient_id, $total_amount, 'COD', '$delivery_address', $lat_val, $lng_val, 'Pending')";

        if (!mysqli_query($conn, $orderQuery)) {
            throw new Exception("فشل إنشاء الطلب: " . mysqli_error($conn));
        }

        $order_id = mysqli_insert_id($conn);

        // 2. إدخال عناصر الطلب في جدول OrderItems
        foreach ($data->items as $item) {
            $stock_id = (int)$item->stock_id;
            $quantity = (int)$item->quantity;
            $sold_price = (float)$item->sold_price;

            $itemQuery = "INSERT INTO OrderItems (OrderID, StockID, Quantity, SoldPrice)
                          VALUES ($order_id, $stock_id, $quantity, $sold_price)";

            if (!mysqli_query($conn, $itemQuery)) {
                throw new Exception("فشل إدخال عنصر الطلب: " . mysqli_error($conn));
            }

            // 3. (اختياري) تقليل المخزون تلقائياً
            $updateStock = "UPDATE PharmacyStock SET Stock = Stock - $quantity WHERE StockID = $stock_id AND Stock >= $quantity";
            mysqli_query($conn, $updateStock);
        }

        // تأكيد العملية
        mysqli_commit($conn);

        echo json_encode([
            "status" => "success",
            "message" => "تم إنشاء الطلب بنجاح!",
            "order_id" => $order_id
        ]);

    } catch (Exception $e) {
        // التراجع عن كل شيء في حال حدوث خطأ
        mysqli_rollback($conn);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "بيانات الطلب غير مكتملة!"
    ]);
}
?>
