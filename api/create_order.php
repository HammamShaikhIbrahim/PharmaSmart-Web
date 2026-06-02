<?php
// ==========================================
// ملف API لإنشاء طلب جديد (create_order.php)
// تم التعديل لإضافة منطق "حجز المخزون" فور الطلب
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->patient_id) &&
    isset($data->total_amount) &&
    !empty($data->items) &&
    is_array($data->items)
) {
    $patient_id = (int)$data->patient_id;
    $total_amount = (float)$data->total_amount;

    // استلام العنوان النصي الذي كتبه المريض بيده
    $delivery_address = isset($data->delivery_address) ? mysqli_real_escape_string($conn, $data->delivery_address) : '';

    // استلام قرار المريض: هل يريد موقعه المسجل أم موقع جديد؟
    $use_saved_location = isset($data->use_saved_location) ? $data->use_saved_location : false;

    if ($use_saved_location) {
        // إذا اختار موقعه القديم، نجلبه من قاعدة البيانات
        $patQuery = mysqli_query($conn, "SELECT Latitude, Longitude FROM Patient WHERE PatientID = $patient_id");
        if ($patQuery && mysqli_num_rows($patQuery) > 0) {
            $patData = mysqli_fetch_assoc($patQuery);
            $delivery_lat = $patData['Latitude'] !== null ? $patData['Latitude'] : "NULL";
            $delivery_lng = $patData['Longitude'] !== null ? $patData['Longitude'] : "NULL";
        } else {
            $delivery_lat = "NULL";
            $delivery_lng = "NULL";
        }
    } else {
        // إذا اختار موقع جديد، نأخذه من الموبايل
        $delivery_lat = (isset($data->delivery_lat) && $data->delivery_lat !== null) ? (float)$data->delivery_lat : "NULL";
        $delivery_lng = (isset($data->delivery_lng) && $data->delivery_lng !== null) ? (float)$data->delivery_lng : "NULL";
    }

    $prescription_base64 = isset($data->prescription_image) ? $data->prescription_image : null;

    // 💡 بدء المعاملة لضمان سلامة البيانات ومنع التضارب
    mysqli_begin_transaction($conn);

    try {
        // 1. التحقق من توفر الكمية في المخزون لكل دواء قبل إنشاء الطلب
        foreach ($data->items as $item) {
            $stock_id = (int)$item->stock_id;
            $requested_qty = (int)$item->quantity;

            // استعلام لفحص المخزون الحالي مع قفل السطر لمنع تضارب العمليات (SELECT FOR UPDATE)
            $stockCheck = mysqli_query($conn, "SELECT Stock FROM PharmacyStock WHERE StockID = $stock_id FOR UPDATE");
            
            if (!$stockCheck || mysqli_num_rows($stockCheck) == 0) {
                throw new Exception("أحد الأدوية المطلوبة غير موجود في المخزون.");
            }
            
            $stockData = mysqli_fetch_assoc($stockCheck);
            
            if ($stockData['Stock'] < $requested_qty) {
                throw new Exception("عذراً، الكمية المطلوبة غير متوفرة لبعض الأدوية حالياً.");
            }
        }

        // 2. إدخال الطلب الأساسي
        $orderQuery = "INSERT INTO `Order` (PatientID, TotalAmount, PaymentMethod, DeliveryAddress, DeliveryLatitude, DeliveryLongitude, Status)
                       VALUES ($patient_id, $total_amount, 'COD', '$delivery_address', $delivery_lat, $delivery_lng, 'Pending')";

        if (!mysqli_query($conn, $orderQuery)) {
            throw new Exception("فشل إنشاء الطلب: " . mysqli_error($conn));
        }

        $order_id = mysqli_insert_id($conn);

        // 3. إدخال العناصر وخصم المخزون فورياً (الحجز)
        foreach ($data->items as $item) {
            $stock_id = (int)$item->stock_id;
            $quantity = (int)$item->quantity;
            $sold_price = (float)$item->sold_price;

            $itemQuery = "INSERT INTO OrderItems (OrderID, StockID, Quantity, SoldPrice)
                          VALUES ($order_id, $stock_id, $quantity, $sold_price)";

            if (!mysqli_query($conn, $itemQuery)) {
                throw new Exception("فشل إدخال عنصر الطلب");
            }

            // 💡 الخصم الفوري من المخزون لحجز الكمية لهذا المريض
            $updateStock = "UPDATE PharmacyStock SET Stock = Stock - $quantity WHERE StockID = $stock_id";
            if (!mysqli_query($conn, $updateStock)) {
                throw new Exception("حدث خطأ أثناء تحديث المخزون");
            }
        }

        // 4. معالجة الوصفة الطبية (إن وُجدت)
        if ($prescription_base64 != null && !empty($prescription_base64)) {
            $image_parts = explode(";base64,", $prescription_base64);
            $image_base64 = base64_decode(count($image_parts) > 1 ? $image_parts[1] : $image_parts[0]);
            
            $upload_dir = '../uploads/prescriptions/';
            if (!file_exists($upload_dir)) { 
                mkdir($upload_dir, 0777, true); 
            }
            
            $filename = 'rx_' . time() . '_' . rand(1000, 9999) . '.jpg';
            $filepath = $upload_dir . $filename;

            if (file_put_contents($filepath, $image_base64)) {
                $db_image_path = 'uploads/prescriptions/' . $filename;
                $rxQuery = "INSERT INTO Prescription (ImagePath, IsVerified, OrderID) VALUES ('$db_image_path', 0, $order_id)";
                if (!mysqli_query($conn, $rxQuery)) {
                    throw new Exception("فشل حفظ مسار الوصفة");
                }
            } else {
                throw new Exception("فشل إنشاء ملف الصورة");
            }
        }

        // تأكيد كل العمليات بنجاح
        mysqli_commit($conn);

        echo json_encode([
            "status" => "success",
            "message" => "تم إنشاء الطلب بنجاح!",
            "order_id" => $order_id
        ]);

    } catch (Exception $e) {
        // في حال حدوث أي خطأ، نتراجع عن كل شيء (بما فيه الخصم من المخزون)
        mysqli_rollback($conn);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "بيانات الطلب غير مكتملة"
    ]);
}
?>