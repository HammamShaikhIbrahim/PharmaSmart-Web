<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/database.php';
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id)) {
    $user_id = (int)$data->user_id;
    
    // تجهيز المتغيرات
    $updates = [];
    
    if (isset($data->medical_history)) {
        $history = mysqli_real_escape_string($conn, $data->medical_history);
        $updates[] = "MedicalHistory = '$history'";
    }
    
    if (isset($data->address)) {
        $address = mysqli_real_escape_string($conn, $data->address);
        $updates[] = "Address = '$address'";
    }
    
    if (isset($data->lat) && isset($data->lng)) {
        $lat = (float)$data->lat;
        $lng = (float)$data->lng;
        $updates[] = "Latitude = $lat, Longitude = $lng";
    }
    
    if (count($updates) > 0) {
        $sql = "UPDATE Patient SET " . implode(", ", $updates) . " WHERE PatientID = $user_id";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "تم حفظ البيانات بنجاح"]);
        } else {
            echo json_encode(["status" => "error", "message" => "فشل التحديث: " . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "لا توجد بيانات للتحديث"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "معرف المستخدم مفقود"]);
}
?>