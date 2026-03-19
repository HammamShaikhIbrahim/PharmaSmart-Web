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

    //register page
    $lang['email_exists_error'] = "عذراً، هذا البريد الإلكتروني مسجل مسبقاً!";
    $lang['location_error'] = "يرجى تحديد موقع الصيدلية بدقة على الخريطة.";
    $lang['registration_success'] = "تم إرسال طلب انضمامك بنجاح! يرجى انتظار تفعيل حسابك من الإدارة.";
    $lang['registration_error'] = "حدث خطأ أثناء التسجيل. الرجاء المحاولة مرة أخرى.";
    $lang['user_creation_error'] = "حدث خطأ أثناء إنشاء حساب المستخدم. الرجاء المحاولة مرة أخرى.";
    $lang['register_title'] = "تسجيل صيدلية جديدة - PharmaSmart";
    $lang['register_title_short'] = "تسجيل صيدلية جديدة";
    $lang['register_subtitle'] = "قم بملء بياناتك بدقة ليتم مراجعتها من قبل الإدارة";
    $lang['personal_info'] = "البيانات الشخصية";
    $lang['first_name'] = "الاسم الأول";
    $lang['last_name'] = "اسم العائلة";
    $lang['phone'] = "رقم الهاتف";
    $lang['email'] = "البريد الإلكتروني";
    $lang['password'] = "كلمة المرور";
    $lang['pharmacy_info'] = "بيانات الصيدلية";
    $lang['pharmacy_name'] = "اسم الصيدلية الرسمي";
    $lang['license_num'] = "رقم الترخيص من وزارة الصحة";
    $lang['address'] = "العنوان الوصفي (المدينة - الشارع)";
    $lang['working_hours'] = "ساعات الدوام";
    $lang['pharmacy_logo'] = "شعار الصيدلية (اختياري)";
    $lang['choose_logo'] = "اختر شعار الصيدلية";
    $lang['upload'] = "رفع";
    $lang['location_picker'] = "تحديد الموقع الجغرافي";
    $lang['location_description'] = "يرجى الضغط على الخريطة لتحديد موقع الصيدلية بدقة، سيساعد هذا المرضى في العثور عليك عبر التطبيق.";
    $lang['register_button'] = "إرسال طلب الانضمام";
    $lang['already_have_account'] = "لديك حساب بالفعل؟";
    $lang['login_link'] = "العودة لتسجيل الدخول";
    $lang['success'] = "اكتمل الطلب!";
    $lang['error'] = "خطأ";

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
    $lang['no_data'] = "لا يوجد بيانات";

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
    $lang['pending_orders'] = "قيد الانتظار"; // تم التعديل لحل مشكلة الفلتر
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
    $lang['add_product'] = "إضافة دواء";
    $lang['search_product'] = "ابحث عن دواء بالاسم...";
    $lang['product'] = "المنتج";
    $lang['category'] = "التصنيف";
    $lang['stock'] = "المخزون";
    $lang['price'] = "سعر البيع";
    $lang['cost'] = "التكلفة";
    $lang['margin'] = "الربح";
    $lang['expiry'] = "تاريخ الانتهاء";
    $lang['add_new_product'] = "إضافة دواء جديد";
    $lang['edit_product'] = "تعديل بيانات الدواء";
    $lang['product_name'] = "اسم الدواء";
    $lang['min_stock'] = "الحد الأدنى للمخزون";
    $lang['description'] = "الوصف / ملاحظات";
    $lang['product_image'] = "صورة الدواء";
    $lang['is_controlled'] = "دواء مراقب (يحتاج وصفة طبية)";
    $lang['cancel'] = "إلغاء";
    $lang['confirm'] = "تأكيد";
    $lang['uncategorized'] = "غير مصنف";
    $lang['select_category'] = "اختر تصنيفاً...";
    $lang['controlled_description'] = "هذا دواء مراقب ويتطلب وصفة طبية.";
    $lang['no_medicines'] = "لا يوجد أدوية في المخزون حالياً";
    $lang['no_medicines_description'] = "ابدأ بإضافة أول دواء لمخزونك عبر الزر بالأعلى لتتمكن من استقبال الطلبات.";
    $lang['select_image'] = "اختر صورة";
    $lang['file_selected'] = "تم اختيار الملف:";
    $lang['select_file'] = "اختر ملف";

    // إضافة: صفحة إدارة الطلبات (Orders Page)
    // -----------------------------------------
    $lang['manage_orders'] = "إدارة الطلبات";
    $lang['filter_processing'] = "جاري التجهيز";
    $lang['filter_delivered'] = "مكتملة";
    $lang['status_pending'] = "بانتظار الموافقة";
    $lang['status_processing'] = "قيد التجهيز والتوصيل";
    $lang['status_delivered'] = "تم التسليم";
    $lang['status_rejected'] = "مرفوض";
    $lang['order_number'] = "رقم الطلب";
    $lang['rx_alert'] = "يحتوي على أدوية مراقبة (يستلزم مراجعة الوصفة)";
    $lang['details_btn'] = "التفاصيل";
    $lang['delivered_btn'] = "تم التوصيل";
    $lang['no_orders'] = "لا توجد طلبات";
    $lang['no_orders_desc'] = "لم يتم العثور على أي طلبات مطابقة للفلتر الحالي.";

    // نافذة تفاصيل الطلب (Modal)
    $lang['order_details'] = "تفاصيل الطلب";
    $lang['customer_info'] = "بيانات العميل";
    $lang['payment_summary'] = "ملخص الدفع";
    $lang['total_required'] = "الإجمالي المطلوب:";
    $lang['cod'] = "الدفع عند الاستلام (COD)";
    $lang['requested_items'] = "المنتجات المطلوبة";
    $lang['qty'] = "الكمية";
    $lang['item_total'] = "الإجمالي";
    $lang['attached_rx'] = "الوصفة الطبية المرفقة (مطلوبة)";
    $lang['rx_protocol'] = "بناءً على بروتوكول وزارة الصحة، يرجى التحقق مما يلي قبل قبول الطلب:";
    $lang['rx_verify_check'] = "أقر بأني راجعت الوصفة الطبية، وصحتها، ومطابقتها للأدوية المطلوبة.";
    $lang['close'] = "إغلاق";
    $lang['accept_prepare'] = "قبول وتجهيز";
    $lang['confirm_delivery'] = "تأكيد التسليم";

    // كلمات إضافية للنافذة (Modal Translations)
    $lang['order_summary'] = "ملخص الطلب";
    $lang['purchases'] = "المشتريات";
    $lang['prescription_rx'] = "الوصفة الطبية (Rx)";
    $lang['contains_controlled'] = "الطلب يحتوي على أدوية مراقبة";
    $lang['action_taken'] = "تم اتخاذ إجراء مسبقاً";
    $lang['reject'] = "رفض";
    $lang['delivered_successfully'] = "تم تسليم الطلب بنجاح";
    $lang['pickup_pharmacy'] = "استلام من الصيدلية";
    $lang['no_phone'] = "لا يوجد رقم";
    $lang['quantity'] = "الكمية:";

    // كلمات صفحة الطلبات (Orders Page)
    $lang['filter_rejected'] = "ملغي";
    $lang['status_processing'] = "هل أنت متأكد من قبول الطلب وبدء تجهيزه؟";
    $lang['status_rejected'] = "هل أنت متأكد من إلغاء هذا الطلب؟";
    $lang['delivery_location'] = "موقع التوصيل (الخريطة)";
    $lang['verify_rx_btn'] = "اعتماد الوصفة";
    $lang['reject_rx_btn'] = "رفض الوصفة";
    $lang['no_location_provided'] = "لم يقم المريض بتحديد موقعه على الخريطة";

    $lang['all_orders'] = "جميع الطلبات";
    $lang['search_order_patient'] = "ابحث برقم الطلب أو المريض...";
    $lang['customer_contact'] = "العميل / الاتصال";
    $lang['address_col'] = "العنوان";
    $lang['items_col'] = "الأصناف";
    $lang['total_amount'] = "الإجمالي";

} else {

    // Login Page
    $lang['login_title'] = "Login";
    $lang['login_subtitle'] = "Your gateway to professional pharmacy management";
    $lang['email'] = "Email Address";
    $lang['password'] = "Password";
    $lang['btn_login'] = "Login";
    $lang['new_account'] = "New Pharmacy?";
    $lang['register_link'] = "Submit Join Request";

    //register page
    $lang['email_exists_error'] = "Sorry, this email is already registered!";
    $lang['location_error'] = "Please select the pharmacy location accurately on the map.";
    $lang['registration_success'] = "Your join request has been submitted successfully! Please wait for admin approval.";
    $lang['registration_error'] = "An error occurred during registration. Please try again.";
    $lang['user_creation_error'] = "An error occurred while creating the user account. Please try again.";
    $lang['register_title'] = "New Pharmacy Registration - PharmaSmart";
    $lang['register_title_short'] = "New Pharmacy Registration";
    $lang['register_subtitle'] = "Please fill in your details accurately for review by the administration.";
    $lang['personal_info'] = "Personal Information";
    $lang['first_name'] = "First Name";
    $lang['last_name'] = "Last Name";
    $lang['phone'] = "Phone Number";
    $lang['email'] = "Email Address";
    $lang['password'] = "Password";
    $lang['pharmacy_info'] = "Pharmacy Information";
    $lang['pharmacy_name'] = "Official Pharmacy Name";
    $lang['license_num'] = "License Number from Ministry of Health";
    $lang['address'] = "Address (City - Street)";
    $lang['working_hours'] = "Working Hours";
    $lang['pharmacy_logo'] = "Pharmacy Logo (Optional)";
    $lang['choose_logo'] = "Choose Pharmacy Logo";
    $lang['upload'] = "Upload";
    $lang['location_picker'] = "Select Geographic Location";
    $lang['location_description'] = "Please click on the map to select the pharmacy location accurately, this will help patients find you through the app.";
    $lang['register_button'] = "Submit Join Request";
    $lang['already_have_account'] = "Already have an account?";
    $lang['login_link'] = "Return to Login";
    $lang['success'] = "Request completed!";
    $lang['error'] = "Error";

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
    $lang['no_data'] = "No data available";

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
    $lang['currency'] = "₪"; 
    $lang['pending_orders'] = "Pending"; // تم التعديل لحل مشكلة الفلتر
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
    $lang['add_product'] = "Add Product";
    $lang['search_product'] = "Search products by name...";
    $lang['product'] = "Product";
    $lang['category'] = "Category";
    $lang['stock'] = "Stock";
    $lang['price'] = "Price";
    $lang['cost'] = "Cost";
    $lang['margin'] = "Margin";
    $lang['expiry'] = "Expiry Date";
    $lang['add_new_product'] = "Add New Product";
    $lang['edit_product'] = "Edit Product";
    $lang['product_name'] = "Product Name";
    $lang['min_stock'] = "Minimum Stock";
    $lang['description'] = "Description / Notes";
    $lang['product_image'] = "Product Image";
    $lang['is_controlled'] = "Controlled Med (Requires Prescription)";
    $lang['cancel'] = "Cancel";
    $lang['confirm'] = "Confirm";
    $lang['uncategorized'] = "Uncategorized";
    $lang['select_category'] = "Select a category...";
    $lang['controlled_description'] = "This is a controlled medicine and requires a prescription.";
    $lang['no_medicines'] = "No medicines in stock currently";
    $lang['no_medicines_description'] = "Start by adding your first medicine to your inventory using the button above to start receiving orders.";
    $lang['select_image'] = "Select Image";
    $lang['file_selected'] = "File selected:";
    $lang['select_file'] = "Select File";

    // -----------------------------------------
    // إضافة: صفحة إدارة الطلبات (Orders Page)
    // -----------------------------------------
    $lang['manage_orders'] = "Orders Management";
    $lang['filter_processing'] = "Processing";
    $lang['filter_delivered'] = "Delivered";
    $lang['status_pending'] = "Awaiting Approval";
    $lang['status_processing'] = "Processing & Delivery";
    $lang['status_delivered'] = "Delivered";
    $lang['status_rejected'] = "Rejected";
    $lang['order_number'] = "Order ID";
    $lang['rx_alert'] = "Contains Controlled Meds (Rx Review Required)";
    $lang['details_btn'] = "Details";
    $lang['delivered_btn'] = "Delivered";
    $lang['no_orders'] = "No Orders Found";
    $lang['no_orders_desc'] = "No orders match the current filter criteria.";

    // Order Details Modal
    $lang['order_details'] = "Order Details";
    $lang['customer_info'] = "Customer Info";
    $lang['payment_summary'] = "Payment Summary";
    $lang['total_required'] = "Total Required:";
    $lang['cod'] = "Cash on Delivery (COD)";
    $lang['requested_items'] = "Requested Items";
    $lang['qty'] = "Qty";
    $lang['item_total'] = "Total";
    $lang['attached_rx'] = "Attached Prescription (Required)";
    $lang['rx_protocol'] = "Based on health protocols, please verify the following before accepting:";
    $lang['rx_verify_check'] = "I have reviewed the prescription and assume professional responsibility.";
    $lang['close'] = "Close";
    $lang['accept_prepare'] = "Accept & Prepare";
    $lang['confirm_delivery'] = "Confirm Delivery";

    // كلمات إضافية للنافذة (Modal Translations)
    $lang['order_summary'] = "Order Summary";
    $lang['purchases'] = "Purchases";
    $lang['prescription_rx'] = "Prescription (Rx)";
    $lang['contains_controlled'] = "Order contains controlled medicines";
    $lang['action_taken'] = "Action already taken";
    $lang['reject'] = "Reject";
    $lang['delivered_successfully'] = "Order Delivered Successfully";
    $lang['pickup_pharmacy'] = "Pickup from Pharmacy";
    $lang['no_phone'] = "No phone number";
    $lang['quantity'] = "Qty:";

    // Orders Page Translations
    $lang['filter_rejected'] = "Rejected";
    $lang['status_processing'] = "Are you sure you want to accept and prepare this order?";
    $lang['status_rejected'] = "Are you sure you want to reject this order?";
    $lang['delivery_location'] = "Delivery Location (Map)";
    $lang['verify_rx_btn'] = "Approve Rx";
    $lang['reject_rx_btn'] = "Reject Rx";
    $lang['no_location_provided'] = "Patient did not provide a map location";

    $lang['all_orders'] = "All Orders";
    $lang['search_order_patient'] = "Search by Order ID or Patient...";
    $lang['customer_contact'] = "Customer / Contact";
    $lang['address_col'] = "Address";
    $lang['items_col'] = "Items";
    $lang['total_amount'] = "Total";
}
?>