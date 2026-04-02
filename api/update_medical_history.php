<?php
// ==========================================
// تحديث السجل المرضي للمريض
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id)) {
    $user_id = (int)$data->user_id;
    // استلام السجل المرضي (قد يكون فارغاً إذا أراد مسحه)
    $medical_history = isset($data->medical_history) ? mysqli_real_escape_string($conn, $data->medical_history) : '';

    // التحديث في جدول Patient بناءً على الـ ID
    $sql = "UPDATE Patient SET MedicalHistory='$medical_history' WHERE PatientID=$user_id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "تم تحديث السجل المرضي بنجاح"]);
    } else {
        echo json_encode(["status" => "error", "message" => "فشل التحديث: " . mysqli_error($conn)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
}
?>