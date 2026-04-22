<?php
// ==========================================
// تحديث الملف الشخصي (بدون رقم الهاتف)
// ==========================================

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->fname) && !empty($data->lname)) {

    $user_id = (int)$data->user_id;
    $fname   = mysqli_real_escape_string($conn, $data->fname);
    $lname   = mysqli_real_escape_string($conn, $data->lname);

    $sql = "UPDATE User SET Fname='$fname', Lname='$lname' 
            WHERE UserID=$user_id AND RoleID=3";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status" => "success", "message" => "تم تحديث البيانات بنجاح"]);
    } else {
        echo json_encode(["status" => "error", "message" => "فشل التحديث: " . mysqli_error($conn)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
}
?>