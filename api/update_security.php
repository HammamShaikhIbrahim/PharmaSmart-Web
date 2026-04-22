<?php
// ==========================================
// خانة الخصوصية والامان (مع رقم الهاتف)
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id) || empty($data->email) || empty($data->old_pass)) {
    echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
    exit();
}

$user_id = (int)$data->user_id;
$email   = mysqli_real_escape_string($conn, $data->email);
$phone   = isset($data->phone) ? mysqli_real_escape_string($conn, $data->phone) : '';
$old_pass = $data->old_pass;

$res  = mysqli_query($conn, "SELECT Password FROM User WHERE UserID=$user_id AND RoleID=3");
$user = mysqli_fetch_assoc($res);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "المستخدم غير موجود"]);
    exit();
}

$isPasswordCorrect = false;
if (password_verify($old_pass, $user['Password'])) {
    $isPasswordCorrect = true;
} elseif ($old_pass === $user['Password']) {
    $isPasswordCorrect = true;
}

if (!$isPasswordCorrect) {
    echo json_encode(["status" => "error", "message" => "كلمة المرور الحالية غير صحيحة"]);
    exit();
}

if (!empty($data->new_pass)) {
    $newHash = password_hash($data->new_pass, PASSWORD_DEFAULT);
    $sql = "UPDATE User SET Email='$email', Phone='$phone', Password='$newHash' WHERE UserID=$user_id";
} else {
    $sql = "UPDATE User SET Email='$email', Phone='$phone' WHERE UserID=$user_id";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode(["status" => "success", "message" => "تم تحديث بيانات الأمان بنجاح"]);
} else {
    echo json_encode(["status" => "error", "message" => "فشل التحديث: " . mysqli_error($conn)]);
}
?>