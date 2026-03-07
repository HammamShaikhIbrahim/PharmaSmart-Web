<?php
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar';
$dir = ($current_lang == 'ar') ? 'rtl' : 'ltr';

$lang = array();

if ($current_lang == 'ar') {

    //login page
    $lang['login_title'] = "تسجيل الدخول";
    $lang['login_subtitle'] = "بوابتك لإدارة الصيدلية باحترافية";
    $lang['email'] = "البريد الإلكتروني";
    $lang['password'] = "كلمة المرور";
    $lang['btn_login'] = "تسجيل الدخول";
    $lang['new_account'] = "صيدلية جديدة؟";
    $lang['register_link'] = "قدم طلب انضمام";

    // أخطاء تسجيل الدخول (عربي)
    $lang['err_patient'] = "عذراً، نظام الويب مخصص للصيادلة والإدارة فقط. يرجى استخدام تطبيق الموبايل.";
    $lang['err_pending'] = "حسابك معلق حالياً، يرجى انتظار موافقة الإدارة.";
    $lang['err_pass'] = "كلمة المرور غير صحيحة.";
    $lang['err_email'] = "البريد الإلكتروني غير مسجل.";
    $lang['err_title'] = "خطأ في تسجيل الدخول";
    $lang['ok_btn'] = "حسناً";

    // عام
    $lang['dashboard'] = "لوحة القيادة";
    $lang['pharmacies'] = "إدارة الصيدليات";
    $lang['patients'] = "إدارة المرضى";
    $lang['logout'] = "تسجيل الخروج";
    $lang['actions'] = "الإجراءات";
    $lang['status'] = "الحالة";
    $lang['dashboard_link'] = "الرئيسية";
    $lang['medicines_stock'] = "الأدوية والمخزون";
    $lang['orders'] = "الطلبات";
    $lang['chats'] = "المحادثات";
    $lang['switch_lang_text'] = "English";
    $lang['switch_lang_code'] = "en";

    //Admin Pages

    // الداشبورد
    $lang['admin_panel'] = "لوحة الإدارة";
    $lang['pharmacy_system'] = "نظام إدارة الصيدلية";
    $lang['active_pharma'] = "الصيدليات العاملة";
    $lang['pending_req'] = "طلبات الانضمام";
    $lang['total_patients'] = "إجمالي المرضى";
    $lang['total_orders'] = "عمليات الشراء";
    $lang['map_title'] = "خريطة انتشار الصيدليات";
    $lang['filter_all'] = "الكل";
    $lang['filter_active'] = "نشط";
    $lang['filter_pending'] = "معلق";
    $lang['live_update'] = "تحديث مباشر";
    $lang['pharma_info'] = "معلومات الصيدلية";
    $lang['click_map'] = "اضغط على أي نقطة في الخريطة لعرض التفاصيل.";

    // معلومات الصيدلية (الجافاسكربت)
    $lang['pharmacist_name'] = "الصيدلاني";
    $lang['address'] = "العنوان";
    $lang['working_hours'] = "الدوام";
    $lang['phone'] = "الهاتف";
    $lang['license_num'] = "رقم الترخيص";
    $lang['not_available'] = "غير متوفر";

    // جدول الصيدليات
    $lang['pharmacy_name'] = "الصيدلية";
    $lang['owner'] = "المالك";
    $lang['phone'] = "الهاتف";
    $lang['location_work'] = "الموقع / العمل";
    $lang['join_date'] = "تاريخ الانضمام";
    $lang['active'] = "نشط";
    $lang['pending'] = "معلق";
    $lang['search_pharmacy'] = "ابحث عن صيدلية بالاسم...";

    // جدول المرضى
    $lang['patient_name'] = "المريض";
    $lang['contact_info'] = "بيانات الاتصال";
    $lang['age'] = "العمر";
    $lang['years'] = "سنة";
    $lang['not_specified'] = "غير محدد";
    $lang['search_patient'] = "ابحث عن مريض بالاسم...";

    // Admin vs Pharmacist
    $lang['admin'] = "مدير";
    $lang['pharmacist'] = "صيدلي";

    // SweetAlert Translations
    $lang['swal_title'] = "هل أنت متأكد؟";
    $lang['swal_text'] = "لن تتمكن من التراجع عن هذا الإجراء!";
    $lang['swal_confirm'] = "نعم، احذف!";
    $lang['swal_cancel'] = "إلغاء";
    $lang['suspend_title'] = "تعليق الحساب؟";
    $lang['suspend_text'] = "سيتم إيقاف الصيدلية مؤقتاً عن العمل.";
    $lang['suspend_confirm'] = "نعم، علق الحساب";

    // Pharmacy Pages

    //الداشبورد
    $lang['todays_sales'] = "مبيعات اليوم";
    $lang['currency'] = "₪"; // أو العملة التي تفضلها
    $lang['pending_orders'] = "طلبات قيد الانتظار";
    $lang['low_stock_items'] = "نواقص المخزون";
    $lang['expiring_soon'] = "قريب الانتهاء";
    $lang['recent_orders'] = "أحدث الطلبات";
    $lang['order_id'] = "رقم الطلب";
    $lang['time'] = "الوقت";
    $lang['customer'] = "العميل";
    $lang['amount'] = "المبلغ";
    $lang['items'] = "العناصر";
    $lang['low_stock_alert'] = "تنبيه نقص المخزون";
    $lang['out_of'] = "من أصل";
    $lang['days'] = "يوم";
    $lang['view_all'] = "عرض الكل";
    $lang['no_recent_orders'] = "لا يوجد طلبات حديثة";
    $lang['stock_excellent'] = "المخزون ممتاز، لا يوجد نواقص!";
    $lang['manage_inventory'] = "إدارة المخزون";
    $lang['no_low_stock_items'] = "لا توجد عناصر ناقصة في المخزون";

    // صفحة الأدوية والمخزون
    $lang['product_inventory'] = "مخزون الأدوية";
    $lang['add_product'] = "إضافة دواء +";
    $lang['search_product'] = "ابحث عن دواء بالاسم...";
    $lang['product'] = "المنتج";
    $lang['category'] = "التصنيف";
    $lang['stock'] = "المخزون";
    $lang['price'] = "سعر البيع";
    $lang['cost'] = "التكلفة";
    $lang['margin'] = "الربح";
    $lang['expiry'] = "الانتهاء";
    $lang['add_new_product'] = "إضافة دواء جديد";
    $lang['edit_product'] = "تعديل بيانات الدواء";
    $lang['product_name'] = "اسم الدواء";
    $lang['min_stock'] = "الحد الأدنى للمخزون";
    $lang['description'] = "الوصف / ملاحظات";
    $lang['product_image'] = "صورة الدواء";
    $lang['is_controlled'] = "دواء مراقب (يحتاج وصفة طبية)";
    $lang['cancel'] = "إلغاء";
    $lang['confirm'] = "تأكيد";

} else {

    // Login Page
    $lang['login_title'] = "Login";
    $lang['login_subtitle'] = "Your gateway to professional pharmacy management";
    $lang['email'] = "Email Address";
    $lang['password'] = "Password";
    $lang['btn_login'] = "Login";
    $lang['new_account'] = "New Pharmacy?";
    $lang['register_link'] = "Submit Join Request";

    // أخطاء تسجيل الدخول (إنجليزي)
    $lang['err_patient'] = "Sorry, Web system is for Pharmacists & Admin only. Please use the mobile app.";
    $lang['err_pending'] = "Your account is pending, please wait for admin approval.";
    $lang['err_pass'] = "Incorrect password.";
    $lang['err_email'] = "Email not registered.";
    $lang['err_title'] = "Login Error";
    $lang['ok_btn'] = "OK";


    // General
    $lang['dashboard'] = "Dashboard";
    $lang['pharmacies'] = "Pharmacies";
    $lang['patients'] = "Patients";
    $lang['logout'] = "Logout";
    $lang['actions'] = "Actions";
    $lang['status'] = "Status";
    $lang['dashboard_link'] = "Dashboard";
    $lang['medicines_stock'] = "Medicines & Stock";
    $lang['orders'] = "Orders";
    $lang['chats'] = "Chats";
    $lang['switch_lang_text'] = "عربي";
    $lang['switch_lang_code'] = "ar";

    // Admin Pages

    // Dashboard
    $lang['admin_panel'] = "Admin Panel";
    $lang['pharmacy_system'] = "Pharmacy Management System";
    $lang['active_pharma'] = "Active Pharmacies";
    $lang['pending_req'] = "Pending Requests";
    $lang['total_patients'] = "Total Patients";
    $lang['total_orders'] = "Total Orders";
    $lang['map_title'] = "Pharmacies Map";
    $lang['filter_all'] = "All";
    $lang['filter_active'] = "Active";
    $lang['filter_pending'] = "Pending";
    $lang['live_update'] = "Live";
    $lang['pharma_info'] = "Pharmacy Info";
    $lang['click_map'] = "Click on any point on the map to view details.";

    // Pharmacy Info (JS)
    $lang['pharmacist_name'] = "Pharmacist Name";
    $lang['address'] = "Address";
    $lang['working_hours'] = " Working Hours";
    $lang['phone'] = "Phone";
    $lang['license_num'] = "License Number";
    $lang['not_available'] = "N/A";

    // Pharmacies Table
    $lang['pharmacy_name'] = "Pharmacy";
    $lang['owner'] = "Owner";
    $lang['phone'] = "Phone";
    $lang['location_work'] = "Location / Hours";
    $lang['join_date'] = "Join Date";
    $lang['active'] = "Active";
    $lang['pending'] = "Pending";
    $lang['search_pharmacy'] = "Search pharmacy by name...";


    // Patients Table
    $lang['patient_name'] = "Patient";
    $lang['contact_info'] = "Contact Info";
    $lang['age'] = "Age";
    $lang['years'] = "years";
    $lang['not_specified'] = "Not specified";
    $lang['search_patient'] = "Search patient by name...";


    // Admin vs Pharmacist
    $lang['admin'] = "Admin";
    $lang['pharmacist'] = "Pharmacist";

    // SweetAlert Translations
    $lang['swal_title'] = "Are you sure?";
    $lang['swal_text'] = "You won't be able to revert this!";
    $lang['swal_confirm'] = "Yes, delete it!";
    $lang['swal_cancel'] = "Cancel";
    $lang['suspend_title'] = "Suspend Account?";
    $lang['suspend_text'] = "Pharmacy will be temporarily paused.";
    $lang['suspend_confirm'] = "Yes, Suspend";

    // Pharmacy Pages

    // Dashboard
    $lang['todays_sales'] = "Today's Sales";
    $lang['currency'] = "₪"; // or your preferred currency
    $lang['pending_orders'] = "Pending Orders";
    $lang['low_stock_items'] = "Low Stock Items";
    $lang['expiring_soon'] = "Expiring Soon";
    $lang['recent_orders'] = "Recent Orders";
    $lang['order_id'] = "Order ID";
    $lang['time'] = "Time";
    $lang['customer'] = "Customer";
    $lang['amount'] = "Amount";
    $lang['items'] = "Items";
    $lang['low_stock_alert'] = "Low Stock Alert";
    $lang['out_of'] = "out of";
    $lang['days'] = "days";
    $lang['view_all'] = "View All";
    $lang['no_recent_orders'] = "No recent orders.";
    $lang['stock_excellent'] = "Stock is excellent, no shortages!";
    $lang['manage_inventory'] = "Manage Inventory";
    $lang['no_low_stock_items'] = "No low stock items in inventory.";

    // Medicines & Stock Page
    $lang['product_inventory'] = "Product Inventory";
    $lang['add_product'] = "Add Product +";
    $lang['search_product'] = "Search products by name...";
    $lang['product'] = "Product";
    $lang['category'] = "Category";
    $lang['stock'] = "Stock";
    $lang['price'] = "Price";
    $lang['cost'] = "Cost";
    $lang['margin'] = "Margin";
    $lang['expiry'] = "Expiry";
    $lang['add_new_product'] = "Add New Product";
    $lang['edit_product'] = "Edit Product";
    $lang['product_name'] = "Product Name";
    $lang['min_stock'] = "Minimum Stock";
    $lang['description'] = "Description / Notes";
    $lang['product_image'] = "Product Image";
    $lang['is_controlled'] = "Controlled Med (Requires Prescription)";
    $lang['cancel'] = "Cancel";
    $lang['confirm'] = "Confirm";
}
