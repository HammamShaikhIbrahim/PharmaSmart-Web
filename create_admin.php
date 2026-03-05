<?php

include "config/database.php";

$password = password_hash("123", PASSWORD_DEFAULT);

$query = "INSERT INTO User
(Fname,Lname,Email,Password,Phone,RoleID)
VALUES
('Hammam','Admin','H@Admin.com','$password','0000000000',1)";

mysqli_query($conn, $query);

$userID = mysqli_insert_id($conn);

mysqli_query($conn, "INSERT INTO Admin (AdminID,Permissions)
VALUES ($userID,'Full Access')");

echo "Admin created successfully";
