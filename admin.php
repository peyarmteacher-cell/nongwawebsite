<?php
/**
 * 💻 ศูนย์กลางควบคุมระบบหลังบ้านผู้ดูแลระบบ (Admin Central Dashboard Controller)
 * ควบคุมข้อมูลโรงเรียน, สตรีมอัพเดตตารางฐานข้อมูลอัตโนมัติ และแยกส่วนเทมเพลตเพื่อความง่ายต่อการอภิเดต
 * ออกแบบด้วยอัตลักษณ์ "ชมพู-ขาว" สวยงาม เป็นสัดส่วนและใช้ง่ายที่สุด
 */

require_once 'db_connect.php';

// 1. ตรวจสอบสิทธิ์ผู้รักษาการระบบ Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. ระบบออกจากระบบ (Log Out)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 2.5 บล็อกจัดการ AJAX อัปโหลดไฟล์ด่วน (Instant Web App AJAX Uploader)
if (isset($_GET['action']) && $_GET['action'] === 'ajax_upload') {
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวัตถุไฟล์ถูกส่งมาในระบบ']);
        exit;
    }
    
    $allowed_param = isset($_GET['allowed']) ? trim($_GET['allowed']) : 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip';
    $file_url = uploadFileToServer($_FILES['file'], $allowed_param);
    
    if ($file_url) {
        echo json_encode([
            'status' => 'success',
            'url' => $file_url,
            'filename' => $_FILES['file']['name']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => !empty($global_last_upload_error) ? $global_last_upload_error : 'การทำงานประมวลผลเซิร์ฟเวอร์ล้มเหลว'
        ]);
    }
    exit;
}

// 3. ตัวแปรสำหรับเก็บรายการแจ้งเตือนสัญกรณ์สำเร็จ / ล้มเหลว
$success_alert = '';
$err_alert = '';

// ตัวแปรส่วนตัวสำหรับสนับสนุนการจัดหาเหตุผลของการอัปโหลดที่ล้มเหลว
$global_last_upload_error = '';

/**
 * ฟังก์ชันช่วยแปลงรหัสข้อผิดพลาดของการอัปโหลดไฟล์ใน PHP
 */
function getUploadErrorMessage($errorCode) {
    if (defined('UPLOAD_ERR_INI_SIZE') && $errorCode === UPLOAD_ERR_INI_SIZE) {
        $max_size = ini_get('upload_max_filesize');
        return "ขนาดไฟล์ใหญ่เกินขีดจำกัดสูงสุดที่ระบบเซิร์ฟเวอร์ตั้งไว้ใน php.ini (" . $max_size . "B) กรุณาย่อรูปภาพหรือลดขนาดเอกสารให้ต่ำกว่า " . $max_size . " ก่อนทำรายการใหม่";
    }
    switch ($errorCode) {
        case 2: // UPLOAD_ERR_FORM_SIZE
            return "ขนาดไฟล์ใหญ่เกินขีดจำกัดความกว้างฟอร์ม (MAX_FILE_SIZE)";
        case 3: // UPLOAD_ERR_PARTIAL
            return "การอัปโหลดไฟล์ไม่เสร็จสิ้น มีข้อมูลเคลื่อนย้ายมาเพียงบางส่วน";
        case 4: // UPLOAD_ERR_NO_FILE
            return "ไม่พบลายแทงไฟล์ที่ถูกยื่นเข้ามา";
        case 6: // UPLOAD_ERR_NO_TMP_DIR
            return "เซิร์ฟเวอร์ระบบขัดข้อง ไม่มีทางผ่านแฟ้มเก็บข้อมูลชั่วคราวหลัก (Temp Directory)";
        case 7: // UPLOAD_ERR_CANT_WRITE
            return "เซิร์ฟเวอร์ไม่สามารถเขียนจัดเก็บไฟล์ของท่านลงบนระบบบันทึกข้อมูลดิสก์ได้สำเร็จ (Diskfull/Permission Denied)";
        case 8: // UPLOAD_ERR_EXTENSION
            return "การทำงานถูกปิดตัวกลางคันโดย PHP Extension บนเซิร์ฟเวอร์";
        default:
            return "ระบบพบข้อผิดพลาดที่ไม่ทราบรหัสต้นตอ: " . $errorCode;
    }
}

/**
 * ฟังก์ชันสำหรับช่วยเหลืออัปโหลดไฟล์ระดับสากลแยกโฟลเดอร์อัตโนมัติ
 * แยกไฟล์รูปภาพเข้า uploads/images | ไฟล์ PDF เข้า uploads/pdfs | ไฟล์เอกสารอื่นๆ เข้า uploads/documents
 */
function uploadFileToServer($file, $allowed_types = 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip') {
    global $global_last_upload_error, $pdo;
    $global_last_upload_error = '';

    if (!isset($file)) {
        $global_last_upload_error = "ไม่มีข้อมูลวัตถุไฟล์ถูกส่งเข้ามาเข้าระบบ";
        return false;
    }

    if ($file['error'] !== 0) { // 0 คือ UPLOAD_ERR_OK
        $global_last_upload_error = getUploadErrorMessage($file['error']);
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = explode(',', $allowed_types);

    if (!in_array($ext, $allowed)) {
        $global_last_upload_error = "ไม่รับรองไฟล์สกุล '." . $ext . "' (ช่องทางนี้รองรับเฉพาะ: " . implode(', ', $allowed) . ")";
        return false;
    }

    // เจาะลึกดึงค่า Google Apps Script Web App URL และ Google Drive Folder ID จาก POST / GET หรือฐานข้อมูล เพื่อนำพาการอัพโหลดขึ้น Google Drive
    $gas_url = '';
    $gas_folder_id = '';

    // ดึงข้อมูลแบบเรียลไทม์จาก POST/GET ก่อนเพื่อรองรับกรณีการทดลองตั้งค่าบนแผงควบคุมที่ยังไม่ได้บันทึกลงตารางจริง
    if (!empty($_POST['google_apps_script_url'])) {
        $gas_url = trim($_POST['google_apps_script_url']);
    } elseif (!empty($_GET['google_apps_script_url'])) {
        $gas_url = trim($_GET['google_apps_script_url']);
    }

    if (!empty($_POST['google_drive_folder_id'])) {
        $gas_folder_id = trim($_POST['google_drive_folder_id']);
    } elseif (!empty($_GET['google_drive_folder_id'])) {
        $gas_folder_id = trim($_GET['google_drive_folder_id']);
    }

    if (empty($gas_url)) {
        try {
            if (isset($pdo)) {
                $gas_stmt = $pdo->query("SELECT `google_apps_script_url`, `google_drive_folder_id` FROM `settings` WHERE `id` = 1");
                if ($gas_stmt) {
                    $gas_row = $gas_stmt->fetch();
                    $gas_url = !empty($gas_row['google_apps_script_url']) ? trim($gas_row['google_apps_script_url']) : '';
                    if (empty($gas_folder_id)) {
                        $gas_folder_id = !empty($gas_row['google_drive_folder_id']) ? trim($gas_row['google_drive_folder_id']) : '';
                    }
                }
            }
        } catch (Exception $db_err) {
            $gas_url = '';
            $gas_folder_id = '';
        }
    }

    // กรณีตรวจพบ URL ของ Google Apps Script Web App ให้ทำการส่งไฟล์ภาพไปเก็บที่ Google Drive ของผู้ใช้โดยตรง
    if (!empty($gas_url) && filter_var($gas_url, FILTER_VALIDATE_URL)) {
        $file_content = @file_get_contents($file['tmp_name']);
        if ($file_content !== false) {
            $base64_data = base64_encode($file_content);
            
            // หาประเภท Mime Type สากล
            $mime_type = 'application/octet-stream';
            if (function_exists('mime_content_type')) {
                $mime_type = @mime_content_type($file['tmp_name']);
            }
            if (!$mime_type || $mime_type === 'application/octet-stream') {
                $mtypes = [
                    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
                    'pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'zip' => 'application/zip'
                ];
                if (isset($mtypes[$ext])) {
                    $mime_type = $mtypes[$ext];
                }
            }

            // จัดทำ Payload สำหรับส่งไปยัง Google Apps Script Web App
            $payload = json_encode([
                'filename' => $file['name'],
                'mimeType' => $mime_type,
                'base64' => $base64_data,
                'folderId' => $gas_folder_id
            ]);

            // ส่ง HTTP POST ไปประมวลผลบนเซิร์ฟเวอร์กูเกิลไดรฟ์โดยตรงด้วยความเสถียรสูงสุด (Manual redirection handling to bypass open_basedir)
            $current_url = $gas_url;
            $max_redirects = 5;
            $response_body = '';
            $upload_success = false;
            $curl_err = '';

            for ($i = 0; $i < $max_redirects; $i++) {
                $ch = curl_init($current_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                // คำขอแรกส่งแบบ POST พร้อม Payload / คำขอเปลี่ยนทางส่งแบบ GET ธรรมดา
                if ($i === 0) {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($payload)
                    ]);
                } else {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Accept: application/json'
                    ]);
                }
                
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
                curl_setopt($ch, CURLOPT_HEADER, true); // ปลดเปิดอ่าน Headers เพื่อดักหน้าผันแปร Location
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 40); // เพิ่มเวลาเป็น 40 วินาที

                $response = curl_exec($ch);
                $info = curl_getinfo($ch);
                $curl_err = curl_error($ch);
                curl_close($ch);

                if ($response === false) {
                    break;
                }

                $header_size = $info['header_size'];
                $header = substr($response, 0, $header_size);
                $body = substr($response, $header_size);
                $http_code = $info['http_code'];

                // ตรวจรหัสคำชี้แนะการเปลี่ยนเส้นทาง 301/302 Redirect
                if (($http_code == 301 || $http_code == 302 || $http_code == 307 || $http_code == 308) && preg_match('/Location:\s*(.*)/i', $header, $matches)) {
                    $current_url = trim($matches[1]);
                    continue; // ขยายขั้นตอนรังวัดการสลับลิงก์แอปสคริปต์ขั้นถัดไป
                }

                $response_body = $body;
                $upload_success = true;
                break;
            }

            if ($upload_success) {
                $clean_body = trim($response_body);
                // ดึงเฉพาะสตริงในขอบเขตวงเล็บปีกกา { ... } ป้องกันอักขระแปลกปลอมรบกวนการถอดรหัส JSON
                $start_pos = strpos($clean_body, '{');
                $end_pos = strrpos($clean_body, '}');
                $res_json = null;
                if ($start_pos !== false && $end_pos !== false && $end_pos > $start_pos) {
                    $json_substr = substr($clean_body, $start_pos, $end_pos - $start_pos + 1);
                    $res_json = json_decode($json_substr, true);
                } else {
                    $res_json = json_decode($clean_body, true);
                }

                if (isset($res_json['status']) && $res_json['status'] === 'success' && !empty($res_json['url'])) {
                    // ดึงพาร์ท URL ของไฟล์ที่อัปโหลดเข้าสู่ Google Drive สำเร็จรูปและแปลงทันที!
                    return fixGoogleDriveUrl($res_json['url']);
                } else {
                    $gas_err_msg = isset($res_json['message']) ? $res_json['message'] : 'สคริปต์สแกนตอบกลับไม่สมบูรณ์: ' . strip_tags($clean_body);
                    $global_last_upload_error = "สตรีมคลาวด์ได้รับผลตอบรับไม่สมบูรณ์: " . $gas_err_msg . " (ระบบสลับมาอัปโหลดพาร์ทโลคอลสำรองแล้ว)";
                }
            } else {
                $global_last_upload_error = "การจัดส่งไฟล์ไปยัง Google Apps Script เกิดปัญหาเชื่อมต่อล้มเหลว: " . $curl_err . " (ระบบสลับมาอัปโหลดพาร์ทโลคอลสำรองแล้ว)";
            }
        } else {
            $global_last_upload_error = "ไม่สามารถเปิดอ่านเนื้อหาไฟล์ชั่วคราวบนเซิร์ฟเวอร์ระบบได้ (ระบบสลับมาอัปโหลดพาร์ทโลคอลสำรองแล้ว)";
        }
    }

    // จัดแยกประเภทโฟลเดอร์ตามความประสงค์ของผู้ใช้เพื่อความเป็นระเบียบระนาบ
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $target_dir = 'uploads/images/';
    } elseif ($ext === 'pdf') {
        $target_dir = 'uploads/pdfs/';
    } else {
        $target_dir = 'uploads/documents/';
    }

    // นำเข้า absolute path ในการชี้พารามิเตอร์ปลายทาง เพื่อป้องกันรันไทม์เปลี่ยน directory ใน sub-include อื่นๆ
    $abs_target_dir = __DIR__ . '/' . $target_dir;

    // ตรวจสอบเช็คสร้างไดเรกทอรีถ้าไม่มี
    if (!file_exists($abs_target_dir)) {
        if (!@mkdir($abs_target_dir, 0777, true)) {
            $global_last_upload_error = "ระบบไม่สามารถสร้างไดเรกทอรี " . $target_dir . " เพื่อบันทึกไฟล์ได้สำเร็จ (กรุณาให้สิทธิ์ Permission โฟลเดอร์ปลายทาง)";
            return false;
        }
    }

    @chmod($abs_target_dir, 0777);

    $new_filename = 'file_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $target_filepath = $target_dir . $new_filename;
    $abs_target_filepath = $abs_target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $abs_target_filepath)) {
        @chmod($abs_target_filepath, 0755);
        return $target_filepath;
    }

    // เจาะลึกตรวจสอบเหตุผลความผิดพลาดของการก๊อปปี้ไฟล์
    if (!is_writable($abs_target_dir)) {
        $global_last_upload_error = "ไม่สามารถบันทึกเก็บข้อมูลย้ายไฟล์ได้เนื่องจากสิทธิ์ในการแก้ไขเขียนของโฟลเดอร์ปลายทาง '" . $target_dir . "' ถูกจำกัด (Not Writable)";
    } else {
        $l_err = error_get_last();
        $global_last_upload_error = "เกิดข้อผิดพลาดรันไทม์ระบบย้ายไฟล์ชั่วคราวล้มเหลว (move_uploaded_file failed)" . ($l_err ? ": " . $l_err['message'] : "");
    }

    return false;
}

// 4. ตัวรับและประมวลผล POST Requests ฝั่งบันทึกข้อมูลหลัก

// ก. การบันทึกข้อมูลทั่วไปของสถาบัน (Settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $school_name = cleanInput($_POST['school_name'] ?? '');
    $short_name = cleanInput($_POST['short_name'] ?? '');
    $school_motto = cleanInput($_POST['school_motto'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $jurisdiction = cleanInput($_POST['jurisdiction'] ?? '');
    $levels = cleanInput($_POST['levels'] ?? '');
    $director_name = cleanInput($_POST['director_name'] ?? '');
    $director_title = cleanInput($_POST['director_title'] ?? '');
    $director_image = cleanInput($_POST['director_image'] ?? '');
    $youtube_intro_url = cleanInput($_POST['youtube_intro_url'] ?? '');
    $banner_title = cleanInput($_POST['banner_title'] ?? '');
    $banner_subtitle = cleanInput($_POST['banner_subtitle'] ?? '');
    $director_message_title = cleanInput($_POST['director_message_title'] ?? '');
    $director_message = cleanInput($_POST['director_message'] ?? '');
    $google_apps_script_url = cleanInput($_POST['google_apps_script_url'] ?? '');
    $google_drive_folder_id = cleanInput($_POST['google_drive_folder_id'] ?? '');

    try {
        $existing_stmt = $pdo->query("SELECT school_logo, banner_bg_image, banner_right_image, director_image FROM `settings` WHERE `id` = 1");
        $existing_sets = $existing_stmt->fetch();
        
        $school_logo = $existing_sets['school_logo'] ?? '';
        $banner_bg_image = $existing_sets['banner_bg_image'] ?? '';
        $banner_right_image = $existing_sets['banner_right_image'] ?? '';
        $director_image = $existing_sets['director_image'] ?? '';
        
        $upload_warnings = [];

        // 1. โลโก้โรงเรียน
        if (isset($_FILES['school_logo_file']) && $_FILES['school_logo_file']['name'] !== '') {
            if ($_FILES['school_logo_file']['error'] === 0) { // UPLOAD_ERR_OK
                $uploaded_logo = uploadFileToServer($_FILES['school_logo_file'], 'jpg,jpeg,png,gif');
                if ($uploaded_logo) {
                    $school_logo = $uploaded_logo;
                } else {
                    $upload_warnings[] = "โลโก้โรงเรียน (เกิดข้อผิดพลาด: " . $global_last_upload_error . ")";
                }
            } else {
                $upload_warnings[] = "โลโก้โรงเรียน (ระบบอัปโหลดขัดข้อง: " . getUploadErrorMessage($_FILES['school_logo_file']['error']) . ")";
            }
        } else {
            // รักษาค่าเดิม หรือกำหนดตามที่ผู้ใช้เขียนระบุไว้ในฟิตฟิลด์ URL
            $school_logo = !empty($_POST['school_logo_url']) ? cleanInput($_POST['school_logo_url']) : $school_logo;
        }
        
        // 2. ภาพแบนเนอร์พื้นหลังหลัก
        if (isset($_FILES['banner_bg_file']) && $_FILES['banner_bg_file']['name'] !== '') {
            if ($_FILES['banner_bg_file']['error'] === 0) { // UPLOAD_ERR_OK
                $uploaded_bg = uploadFileToServer($_FILES['banner_bg_file'], 'jpg,jpeg,png,gif');
                if ($uploaded_bg) {
                    $banner_bg_image = $uploaded_bg;
                } else {
                    $upload_warnings[] = "ภาพพื้นหลังแบนเนอร์ (เกิดข้อผิดพลาด: " . $global_last_upload_error . ")";
                }
            } else {
                $upload_warnings[] = "ภาพพื้นหลังแบนเนอร์ (ระบบอัปโหลดขัดข้อง: " . getUploadErrorMessage($_FILES['banner_bg_file']['error']) . ")";
            }
        } else {
            $banner_bg_image = !empty($_POST['banner_bg_url']) ? cleanInput($_POST['banner_bg_url']) : $banner_bg_image;
        }
        
        // 3. ภาพตกแต่งหน้าแบนเนอร์ซ้าย
        if (isset($_FILES['banner_right_file']) && $_FILES['banner_right_file']['name'] !== '') {
            if ($_FILES['banner_right_file']['error'] === 0) { // UPLOAD_ERR_OK
                $uploaded_right = uploadFileToServer($_FILES['banner_right_file'], 'jpg,jpeg,png,gif');
                if ($uploaded_right) {
                    $banner_right_image = $uploaded_right;
                } else {
                    $upload_warnings[] = "ภาพหน้าแบนเนอร์ซ้าย (เกิดข้อผิดพลาด: " . $global_last_upload_error . ")";
                }
            } else {
                $upload_warnings[] = "ภาพหน้าแบนเนอร์ซ้าย (ระบบอัปโหลดขัดข้อง: " . getUploadErrorMessage($_FILES['banner_right_file']['error']) . ")";
            }
        } else {
            $banner_right_image = !empty($_POST['banner_right_url']) ? cleanInput($_POST['banner_right_url']) : $banner_right_image;
        }

        // 4. ภาพถ่ายผู้อำนวยการโรงเรียน (ตัวเพิ่มใหม่สนับสนุนอธิการบดี)
        if (isset($_FILES['director_image_file']) && $_FILES['director_image_file']['name'] !== '') {
            if ($_FILES['director_image_file']['error'] === 0) { // UPLOAD_ERR_OK
                $uploaded_dir_img = uploadFileToServer($_FILES['director_image_file'], 'jpg,jpeg,png,gif');
                if ($uploaded_dir_img) {
                    $director_image = $uploaded_dir_img;
                } else {
                    $upload_warnings[] = "ภาพผู้อำนวยการโรงเรียน (เกิดข้อผิดพลาด: " . $global_last_upload_error . ")";
                }
            } else {
                $upload_warnings[] = "ภาพผู้อำนวยการโรงเรียน (ระบบอัปโหลดขัดข้อง: " . getUploadErrorMessage($_FILES['director_image_file']['error']) . ")";
            }
        } else {
            $director_image = !empty($_POST['director_image']) ? cleanInput($_POST['director_image']) : $director_image;
        }

        $stmt = $pdo->prepare("UPDATE `settings` SET 
            `school_name` = :school_name,
            `short_name` = :short_name,
            `school_motto` = :school_motto,
            `address` = :address,
            `phone` = :phone,
            `email` = :email,
            `jurisdiction` = :jurisdiction,
            `levels` = :levels,
            `director_name` = :director_name,
            `director_title` = :director_title,
            `director_image` = :director_image,
            `youtube_intro_url` = :youtube_intro_url,
            `school_logo` = :school_logo,
            `banner_bg_image` = :banner_bg_image,
            `banner_right_image` = :banner_right_image,
            `banner_title` = :banner_title,
            `banner_subtitle` = :banner_subtitle,
            `director_message_title` = :director_message_title,
            `director_message` = :director_message,
            `google_apps_script_url` = :google_apps_script_url,
            `google_drive_folder_id` = :google_drive_folder_id
            WHERE `id` = 1");
        
        $stmt->execute([
            'school_name' => $school_name,
            'short_name' => $short_name,
            'school_motto' => $school_motto,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'jurisdiction' => $jurisdiction,
            'levels' => $levels,
            'director_name' => $director_name,
            'director_title' => $director_title,
            'director_image' => $director_image,
            'youtube_intro_url' => $youtube_intro_url,
            'school_logo' => $school_logo,
            'banner_bg_image' => $banner_bg_image,
            'banner_right_image' => $banner_right_image,
            'banner_title' => $banner_title,
            'banner_subtitle' => $banner_subtitle,
            'director_message_title' => $director_message_title,
            'director_message' => $director_message,
            'google_apps_script_url' => $google_apps_script_url,
            'google_drive_folder_id' => $google_drive_folder_id
        ]);

        $success_alert = 'อัปเดตข้อมูลทั่วไปของสถานศึกษาโรงเรียนบ้านหนองหว้าเรียบร้อยแล้ว!';
        if (!empty($upload_warnings)) {
            $success_alert .= '<br><div class="mt-2 text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-xl border border-amber-200"><strong>⚠️ ข้อแนะนำเกี่ยวกับการจัดการสื่อประกอบ:</strong><ul class="list-disc pl-4 mt-1 space-y-1"><li>' . implode('</li><li>', $upload_warnings) . '</li></ul><p class="mt-1.5 font-bold text-slate-700">💡 คำแนะนำ: หากไม่สามารถตัดอัปโหลดไฟล์เข้ามาได้เนื่องจากสัญญานหรือขนาดขีดจำกัดสูงสุดของเซิร์ฟเวอร์ ท่านสามารถเลือกฝากรูปภาพกับบริการออนไลน์ภายนอก และนำลิงก์พาร์ทตรง (.jpg/.png) มาใส่ที่ช่อง "หรือระบุเป็น URL ภาพตรง" ทดแทนได้เลยครับ!</p></div>';
        }
    } catch (Exception $e) {
        $err_alert = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลทั่วไป: ' . $e->getMessage();
    }
}

// ค. การสั่งรีเซ็ตกู้คืนฐานข้อมูลตัวอย่างเริ่มต้น (On-demand Seed Restore)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_database_defaults'])) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `settings`;");
        $pdo->exec("TRUNCATE TABLE `banners`;");
        $pdo->exec("TRUNCATE TABLE `news`;");
        $pdo->exec("TRUNCATE TABLE `teachers`;");
        $pdo->exec("TRUNCATE TABLE `students`;");
        $pdo->exec("TRUNCATE TABLE `downloads`;");
        $pdo->exec("TRUNCATE TABLE `student_stats`;");
        $pdo->exec("TRUNCATE TABLE `student_yearly_stats`;");
        $pdo->exec("TRUNCATE TABLE `external_links`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch (Exception $ex2) {
        try {
            $pdo->exec("DELETE FROM `settings`;");
            $pdo->exec("DELETE FROM `banners`;");
            $pdo->exec("DELETE FROM `news`;");
            $pdo->exec("DELETE FROM `teachers`;");
            $pdo->exec("DELETE FROM `students`;");
            $pdo->exec("DELETE FROM `downloads`;");
            $pdo->exec("DELETE FROM `student_stats`;");
            $pdo->exec("DELETE FROM `student_yearly_stats`;");
            $pdo->exec("DELETE FROM `external_links`;");
        } catch (Exception $ex3) {}
    }

    try {
        $pdo->exec("INSERT INTO `settings` 
            (`id`, `school_name`, `short_name`, `address`, `phone`, `email`, `jurisdiction`, `levels`, `director_name`, `director_title`, `director_image`, `visitor_count`, `school_theme_color`, `school_motto`, `youtube_intro_url`, `banner_title`, `banner_subtitle`, `director_message_title`, `director_message`, `current_academic_year`) 
            VALUES 
            (1, 'โรงเรียนบ้านหนองหว้า', 'ร.ร.บ้านหนองหว้า', 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210', '044-641123', 'bannongwaschool@gmail.com', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3', 'ระดับปฐมวัย (อนุบาล 2-3) ถึงระดับชั้นประถมศึกษาปีที่ 6', 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 15422, 'pink-white', 'ชมพู-ขาว ก้าวไกลวิชาการ', 'https://www.youtube.com/embed/gCOk8X63Rpk', 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ', 'เน้นทักษะชีวิต ความดีงาม คุณธรรมสูงส่ง ส่งผ่านความใส่ใจในระดับชั้น:', 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี', '\"โรงเรียนบ้านหนองหว้า ขอตลับใจเป็นพันธมิตรร่วมกับชุมชน ผู้ปกครอง เพื่อขับเคลื่อนและสร้างสรรค์โอกาสทางวิชาการและวิชาชีพแก่นักเรียน สู่ความพร้อมในการปฏิสัมพันธ์และดำรงชีพในศตวรรษที่ 21 เรามุ่งเสกสร้างสภาพแวดล้อมที่สะอาด ปลอดภัย เพื่อเสริมองค์ความรู้อย่างบูรณาการสูงสุด\"', '2569');");

        $pdo->exec("INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `active`) VALUES 
            (1, 'ยินดีต้อนรับสู่ โรงเรียนบ้านหนองหว้า', 'แหล่งวิทยาการ กีฬาเด่น เน้นคุณธรรม สัมพันธ์ชุมชน', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&q=80&w=1200', 1),
            (2, 'เปิดรับสมัครเรียน ปีการศึกษา 2569', 'ตั้งแต่ชั้น อนุบาล 1 ถึง ชั้นประถมศึกษาปีที่ 6', 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&q=80&w=1200', 1);");

        $pdo->exec("INSERT INTO `news` (`id`, `title`, `category`, `content`, `summary`, `image_url`, `views`, `date`, `sticky_flag`) VALUES 
            (1, 'ประกาศเปิดเรียนภาคเรียนที่ 1 ปีการศึกษา 2569 อย่างเป็นทางการ', 'ประชาสัมพันธ์ทั่วไป', 'โรงเรียนบ้านหนองหว้า ขอประกาศกำหนดการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 ขอความกรุณาผู้ปกครองเตรียมความพร้อมของนักเรียนในเรื่องของเครื่องแบบ อุปกรณ์การเรียน และสุขอนามัย ทางโรงเรียนได้ทำความสะอาดฉีดพ่นฆ่าเชื้อและเตรียมอาคารสถานที่เรียบร้อยแล้ว', 'ประกาศอย่างเป็นทางการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 พร้อมทั้งเตรียมความสะอาดของอาคารสถานที่และการดูแลความปลอดภัยในทุกด้าน', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600', 312, '2026-05-10', 1);");

        $pdo->exec("INSERT INTO `teachers` (`id`, `name`, `position`, `level`, `subject_group`, `image_url`, `sort_order`) VALUES 
            (1, 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'ผู้อำนวยการโรงเรียน (คศ.3)', 'ผู้บริหาร', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 1),
            (2, 'นางสมศรี ปัญญาไว', 'ครูวิชาการระดับประถม / ครูประจำชั้นประถมศึกษาปีที่ 6', 'ครูชำนาญการพิเศษ (คศ.3)', 'วิชาการคณิตศาสตร์', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300', 2);");

        $pdo->exec("INSERT INTO `students` (`id`, `name`, `grade`, `classroom`, `gender`) VALUES 
            (1, 'เด็กชายจิรายุ สมพงษ์', 'ประถมศึกษาปีที่ 6', '6/1', 'ชาย'),
            (2, 'เด็กหญิงรัตนาภรณ์ แสนดี', 'ประถมศึกษาปีที่ 6', '6/1', 'หญิง'),
            (3, 'เด็กหญิงนภัสสร แก้วมณี', 'ประถมศึกษาปีที่ 5', '5/1', 'หญิง'),
            (4, 'เด็กชายชินดนัย มีสุข', 'ประถมศึกษาปีที่ 4', '4/1', 'ชาย'),
            (5, 'เด็กหญิงพิชชาภา เกิดดี', 'ประถมศึกษาปีที่ 3', '3/1', 'หญิง'),
            (6, 'เด็กหญิงกานต์พิชชา ผลเจริญ', 'ประถมศึกษาปีที่ 2', '2/1', 'หญิง'),
            (7, 'เด็กชายอนุรักษ์ รักเรียน', 'ประถมศึกษาปีที่ 1', '1/1', 'ชาย'),
            (8, 'เด็กหญิงมัทนา งามศิลป์', 'อนุบาล 3', 'อ.3/1', 'หญิง');");

        $pdo->exec("INSERT INTO `downloads` (`id`, `title`, `category`, `file_type`, `file_size`, `download_count`, `uploaded_date`, `file_url`) VALUES 
            (1, 'ใบสมัครเข้าศึกษาต่อ ระดับชั้นอนุบาลและประถมศึกษา โรงเรียนบ้านหนองหว้า', 'เอกสารทั่วไป', 'PDF', '1.2 MB', 145, '2026-03-01', '#'),
            (2, 'แผนพัฒนาการศึกษา 5 ปี (พ.ศ. 2568 - 2572) โรงเรียนบ้านหนองหว้า', 'แผนงานและนโยบาย', 'PDF', '4.5 MB', 56, '2026-02-15', '#');");

        $pdo->exec("INSERT INTO `student_stats` (`grade_name`, `student_count`) VALUES 
            ('อนุบาล 2', 45),
            ('อนุบาล 3', 48),
            ('ประถมศึกษาปีที่ 1', 56),
            ('ประถมศึกษาปีที่ 2', 52),
            ('ประถมศึกษาปีที่ 3', 54),
            ('ประถมศึกษาปีที่ 4', 59),
            ('ประถมศึกษาปีที่ 5', 58),
            ('ประถมศึกษาปีที่ 6', 60);");

        $initial_yearly_stats = [
            ['year' => '2566', 'grade' => 'อนุบาล 2', 'count' => 40],
            ['year' => '2566', 'grade' => 'อนุบาล 3', 'count' => 42],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 50],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 48],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 50],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 52],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 51],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 53],
            ['year' => '2567', 'grade' => 'อนุบาล 2', 'count' => 42],
            ['year' => '2567', 'grade' => 'อนุบาล 3', 'count' => 44],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 52],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 50],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 52],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 55],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 54],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 56],
            ['year' => '2568', 'grade' => 'อนุบาล 2', 'count' => 44],
            ['year' => '2568', 'grade' => 'อนุบาล 3', 'count' => 46],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 54],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 52],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 54],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 57],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 56],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 58],
            ['year' => '2569', 'grade' => 'อนุบาล 2', 'count' => 45],
            ['year' => '2569', 'grade' => 'อนุบาล 3', 'count' => 48],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 56],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 52],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 54],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 59],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 58],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 60]
        ];
        $stmt_ins = $pdo->prepare("INSERT INTO `student_yearly_stats` (`academic_year`, `grade_name`, `student_count`) VALUES (:year, :grade, :count)");
        foreach ($initial_yearly_stats as $stat) {
            $stmt_ins->execute([
                'year' => $stat['year'],
                'grade' => $stat['grade'],
                'count' => $stat['count']
            ]);
        }

        $pdo->exec("INSERT INTO `external_links` (`id`, `title`, `description`, `url_link`, `image_url`, `category`) VALUES 
            (1, 'ระบบคลังสื่อเทคโนโลยีสารสนเทศ OBEC Content Center', 'แหล่งรวบรวมสื่อการเรียนรู้ดิจิทัลหลากหลายประเภทสำหรับครูและนักเรียน', 'https://contentcenter.obec.go.th', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&q=80&w=300', 'สื่อการเรียนรู้'),
            (2, 'ระบบสารสนเทศเพื่อการจัดการศึกษา EMIS', 'ระบบจัดเก็บข้อมูลนักเรียนรายบุคคลและสารสนเทศโรงเรียน', 'https://emis.obec.go.th', 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&q=80&w=300', 'งานครูและลิงก์หน่วยงาน'),
            (3, 'DLTV มูลนิธิการศึกษาทางไกลผ่านดาวเทียม', 'รับชมการเรียนการสอนทางไกลและดาวน์โหลดสื่อประกอบการสอนปฐมวัย-ประถม', 'https://www.dltv.ac.th', 'https://images.unsplash.com/photo-1516534775068-ba3e84589d90?auto=format&fit=crop&q=80&w=300', 'สื่อการเรียนรู้'),
            (4, 'ระบบปัจจัยพื้นฐานนักเรียนยากจนพิเศษ CCT', 'บันทึกคุณลักษณะและการดำเนินงานจัดสรรงบประมาณช่วยเหลือนักเรียน', 'https://www.cct.or.th', 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&q=80&w=300', 'งานครูและลิงก์หน่วยงาน');");

        $success_alert = '🔄 กู้คืนฐานข้อมูลมาตรฐานโรงเรียนบ้านหนองหว้าเรียบร้อยแล้ว! ทุกแผนผัง ตารางนักเรียน ระบบงานครู และหมวดสถิติได้รับการ Seeding คืนชีพอย่างสมบูรณ์แบบ';
    } catch (Exception $e) {
        $err_alert = 'เกิดข้อผิดพลาดในการกู้คืนฐานข้อมูลตัวอย่าง: ' . $e->getMessage();
    }
}

// ข. บันทึก/อัปเดตสถิติจนวนนักเรียนรายชั้นเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_students'])) {
    if (isset($_POST['student_counts']) && is_array($_POST['student_counts'])) {
        try {
            foreach ($_POST['student_counts'] as $grade_id => $count) {
                $update_stat_stmt = $pdo->prepare("UPDATE `student_stats` SET `student_count` = :count WHERE `id` = :id");
                $update_stat_stmt->execute([
                    'count' => intval($count),
                    'id' => intval($grade_id)
                ]);
            }
            $success_alert = 'อัปเดตข้อมูลสถิติจำนวนนักเรียนรายชั้นเรียนเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการปรับสถิตินักเรียน: ' . $e->getMessage();
        }
    }
}

// ค. ดำเนินการปรับรายละเอียดแก้ไขข่าวสาร (Edit News Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news_submit'])) {
    $id = intval($_POST['news_id'] ?? 0);
    $title = cleanInput($_POST['news_title'] ?? '');
    $category = cleanInput($_POST['news_category'] ?? 'ประชาสัมพันธ์ทั่วไป');
    $summary = cleanInput($_POST['news_summary'] ?? '');
    $content = cleanInput($_POST['news_content'] ?? '');
    $image_url = cleanInput($_POST['news_image'] ?? '');
    $sticky_flag = isset($_POST['news_sticky']) ? 1 : 0;

    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['news_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) $image_url = $uploaded_image;
    }

    if (empty($title) || empty($content)) {
        $err_alert = 'กรุณากรอกหัวข้อ และเนื้อหาอย่างครบถ้วนเพื่อแก้ไข';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `news` SET `title` = :title, `category` = :category, `summary` = :summary, `content` = :content, `image_url` = :image, `sticky_flag` = :sticky WHERE `id` = :id");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'summary' => $summary,
                'content' => $content,
                'image' => $image_url,
                'sticky' => $sticky_flag,
                'id' => $id
            ]);
            $success_alert = 'แก้ไขบันทึกและสารประชาสัมพันธ์เรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการแก้ไขข่าว: ' . $e->getMessage();
        }
    }
}

// ง. ดำเนินการสร้างข่าวโพสต์ประชาสัมพันธ์ชิ้นใหม่ (Add News)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = cleanInput($_POST['news_title'] ?? '');
    $category = cleanInput($_POST['news_category'] ?? 'ประชาสัมพันธ์ทั่วไป');
    $summary = cleanInput($_POST['news_summary'] ?? '');
    $content = cleanInput($_POST['news_content'] ?? '');
    $image_url = cleanInput($_POST['news_image'] ?? '');
    $sticky_flag = isset($_POST['news_sticky']) ? 1 : 0;
    $date = date('Y-m-d');

    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['news_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) $image_url = $uploaded_image;
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600';
    }

    if (empty($title) || empty($content)) {
        $err_alert = 'กรุณาระบุหัวเรื่องประกาศข่าวประชาสัมพันธ์และเนื้อหาข่าวให้ระดมสมบรูณ์';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `news` (`title`, `category`, `summary`, `content`, `image_url`, `sticky_flag`, `date`) VALUES (:title, :category, :summary, :content, :image, :sticky, :date)");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'summary' => $summary,
                'content' => $content,
                'image' => $image_url,
                'sticky' => $sticky_flag,
                'date' => $date
            ]);
            $success_alert = 'เพิ่มหัวข้อเขียนข่าวประชาสัมพันธ์ชิ้นใหม่เข้าสู่ตารางสำเร็จ!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการเพิ่มประกาศข่าวสาร: ' . $e->getMessage();
        }
    }
}

// จ. แก้ไขสเปคปรับปรุงเอกสารดาวน์โหลด (Edit Download Doc Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_doc_submit'])) {
    $id = intval($_POST['doc_id'] ?? 0);
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_doc_path = uploadFileToServer($_FILES['doc_file'], 'pdf,doc,docx,xls,xlsx,zip,jpg,png,jpeg');
        if ($uploaded_doc_path) {
            $file_url = $uploaded_doc_path;
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            
            if ($ext === 'docx' || $ext === 'doc') $file_type = 'WORD';
            elseif ($ext === 'xlsx' || $ext === 'xls') $file_type = 'EXCEL';
            else $file_type = strtoupper($ext);
            
            $bytes = $_FILES['doc_file']['size'];
            if ($bytes >= 1048576) $file_size = round($bytes / 1048576, 1) . ' MB';
            else $file_size = round($bytes / 1024, 1) . ' KB';
        }
    }

    if (empty($title)) {
        $err_alert = 'กรุณาระบุชื่อประกาศเอกสารจดทะเบียนให้ครบถ้วน';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `downloads` SET `title` = :title, `category` = :category, `file_type` = :file_type, `file_size` = :file_size, `file_url` = :url WHERE `id` = :id");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'file_type' => $file_type,
                'file_size' => $file_size,
                'url' => $file_url,
                'id' => $id
            ]);
            $success_alert = 'แก้ไขรายละเอียดข้อมูลเอกสารประกาศดาวน์โหลดไฟล์เรียบร้อย!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถแก้ไขข้อมูลจำเพาะเอกสาร: ' . $e->getMessage();
        }
    }
}

// ฉ. เพิ่มข้อมูลประกวดเอกสาร / อัปลงเครื่อง (Add Doc File)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doc'])) {
    $title = cleanInput($_POST['doc_title'] ?? '');
    $category = cleanInput($_POST['doc_category'] ?? 'เอกสารทั่วไป');
    $file_type = cleanInput($_POST['doc_type'] ?? 'PDF');
    $file_size = cleanInput($_POST['doc_size'] ?? '1.5 MB');
    $file_url = cleanInput($_POST['doc_url'] ?? '#');
    $date = date('Y-m-d');

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_doc_path = uploadFileToServer($_FILES['doc_file'], 'pdf,doc,docx,xls,xlsx,zip,jpg,png,jpeg');
        if ($uploaded_doc_path) {
            $file_url = $uploaded_doc_path;
            $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
            
            if ($ext === 'docx' || $ext === 'doc') $file_type = 'WORD';
            elseif ($ext === 'xlsx' || $ext === 'xls') $file_type = 'EXCEL';
            else $file_type = strtoupper($ext);
            
            $bytes = $_FILES['doc_file']['size'];
            if ($bytes >= 1048576) $file_size = round($bytes / 1048576, 1) . ' MB';
            else $file_size = round($bytes / 1024, 1) . ' KB';
        }
    }

    if (empty($title)) {
        $err_alert = 'กรุณากรอกชื่อสสารเอกสารประกวดคู่จัดส่งคลังดาวน์โหลด';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `downloads` (`title`, `category`, `file_type`, `file_size`, `uploaded_date`, `file_url`, `download_count`) VALUES (:title, :category, :file_type, :file_size, :date, :url, 0)");
            $stmt->execute([
                'title' => $title,
                'category' => $category,
                'file_type' => $file_type,
                'file_size' => $file_size,
                'date' => $date,
                'url' => $file_url
            ]);
            $success_alert = 'บันทึกอัปและจัดสร้างไฟล์เอกสารดาวน์โหลดคลังแผนการเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถลงทะเบียนแทรกไฟล์คลัง: ' . $e->getMessage();
        }
    }
}

// ช. แก้ไขประวัติคุณครู / เอกสารข้อตกลง PA (Edit Teacher)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher_submit'])) {
    $id = intval($_POST['teacher_id'] ?? 0);
    $name = cleanInput($_POST['teacher_name'] ?? '');
    $position = cleanInput($_POST['teacher_position'] ?? '');
    $level = cleanInput($_POST['teacher_level'] ?? 'ครูผู้ช่วย');
    $subject_group = cleanInput($_POST['teacher_group'] ?? 'งานสอนทั่วไป');
    $image_url = cleanInput($_POST['teacher_image'] ?? '');
    $sort_order = intval($_POST['teacher_order'] ?? 99);
    $pa_link_url = cleanInput($_POST['teacher_pa_url'] ?? '');
    $portfolio_url = cleanInput($_POST['teacher_portfolio_url'] ?? '');

    // อัปโหลดไฟล์รูปประจำกายครู
    if (isset($_FILES['teacher_image_file']) && $_FILES['teacher_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_img = uploadFileToServer($_FILES['teacher_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_img) $image_url = $uploaded_img;
    }

    // อัปโหลดรายงานผลการปฏิบัติงาน PA
    if (isset($_FILES['teacher_pa_file']) && $_FILES['teacher_pa_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_pa = uploadFileToServer($_FILES['teacher_pa_file'], 'pdf,doc,docx,zip');
        if ($uploaded_pa) $pa_link_url = $uploaded_pa;
    }

    // อัปโหลดพอร์ตลิทเทอร์แลนด์
    if (isset($_FILES['teacher_portfolio_file']) && $_FILES['teacher_portfolio_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_port = uploadFileToServer($_FILES['teacher_portfolio_file'], 'pdf,doc,docx,zip,jpg,png,jpeg');
        if ($uploaded_port) $portfolio_url = $uploaded_port;
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณากรอกชื่อ-สกุล และตำแหน่งวิชาชีพข้าราชการครูท่านนั้นๆ';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `teachers` SET `name` = :name, `position` = :pos, `level` = :level, `subject_group` = :group, `image_url` = :img, `pa_link_url` = :pa, `portfolio_url` = :portfolio, `sort_order` = :sort WHERE `id` = :id");
            $stmt->execute([
                'name' => $name,
                'pos' => $position,
                'level' => $level,
                'group' => $subject_group,
                'img' => $image_url,
                'pa' => $pa_link_url,
                'portfolio' => $portfolio_url,
                'sort' => $sort_order,
                'id' => $id
            ]);
            $success_alert = 'แก้ไขแฟ้มบุคลากรและลิงค์รายงานแผน PA สำเร็จ!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการปรับข้อมูลประวัติครู: ' . $e->getMessage();
        }
    }
}

// ซ. การลงทะเบียนประวัติคุณครูท่านใหม่ (Add Teacher to Database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name = cleanInput($_POST['teacher_name'] ?? '');
    $position = cleanInput($_POST['teacher_position'] ?? '');
    $level = cleanInput($_POST['teacher_level'] ?? 'ครูผู้ช่วย');
    $subject_group = cleanInput($_POST['teacher_group'] ?? 'งานสอนทั่วไป');
    $image_url = cleanInput($_POST['teacher_image'] ?? '');
    $sort_order = intval($_POST['teacher_order'] ?? 99);
    $pa_link_url = cleanInput($_POST['teacher_pa_url'] ?? '');
    $portfolio_url = cleanInput($_POST['teacher_portfolio_url'] ?? '');

    if (isset($_FILES['teacher_image_file']) && $_FILES['teacher_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_img = uploadFileToServer($_FILES['teacher_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_img) $image_url = $uploaded_img;
    }

    if (isset($_FILES['teacher_pa_file']) && $_FILES['teacher_pa_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_pa = uploadFileToServer($_FILES['teacher_pa_file'], 'pdf,doc,docx,zip');
        if ($uploaded_pa) $pa_link_url = $uploaded_pa;
    }

    if (isset($_FILES['teacher_portfolio_file']) && $_FILES['teacher_portfolio_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_port = uploadFileToServer($_FILES['teacher_portfolio_file'], 'pdf,doc,docx,zip,jpg,png,jpeg');
        if ($uploaded_port) $portfolio_url = $uploaded_port;
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=300';
    }

    if (empty($name) || empty($position)) {
        $err_alert = 'กรุณาระบุชื่อจริงและตำแหน่งสายข้าราชการครูที่ชัดเจน';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `teachers` (`name`, `position`, `level`, `subject_group`, `image_url`, `pa_link_url`, `portfolio_url`, `sort_order`) VALUES (:name, :pos, :level, :group, :img, :pa, :portfolio, :sort)");
            $stmt->execute([
                'name' => $name,
                'pos' => $position,
                'level' => $level,
                'group' => $subject_group,
                'img' => $image_url,
                'pa' => $pa_link_url,
                'portfolio' => $portfolio_url,
                'sort' => $sort_order
            ]);
            $success_alert = 'เพิ่มรายชื่อประวัติครูท่านใหม่และบันทึกลิงค์ทางวิชาการและสารสนเทศเรียบร้อย!';
        } catch (Exception $e) {
            $err_alert = 'ไม่สามารถลงทะเบียนประวัติข้าราชการเพิ่ม: ' . $e->getMessage();
        }
    }
}

// 5. จัดการเหตุการณ์ลบข้อมูล (Delete actions)
if (isset($_GET['action'])) {
    $action_to_do = $_GET['action'];
    $item_id = intval($_GET['id'] ?? 0);

    if ($action_to_do === 'delete_news' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `news` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบข่าวประชาสัมพันธ์ออกจากประคบคลังเรียบร้อยแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบข่าว: ' . $e->getMessage();
        }
    } elseif ($action_to_do === 'delete_doc' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `downloads` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบเอกสารประกาศดาวน์โหลดไฟล์ออกจากระบบแล้ว!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบเอกสารดาวน์โหลด: ' . $e->getMessage();
        }
    } elseif ($action_to_do === 'delete_teacher' && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `teachers` WHERE `id` = :id");
            $stmt->execute(['id' => $item_id]);
            $success_alert = 'ลบแฟ้มข้อมูลคุณครูข้าราชการท่านนั้นทิ้งเสร็จสมบูรณ์!';
        } catch (Exception $e) {
            $err_alert = 'เกิดข้อผิดพลาดในการลบข้อมูลครู: ' . $e->getMessage();
        }
    }
}

// 6. ดึงข้อมูลพื้นฐานเพื่อใช้เป็น Global State ในชุด View ต่างๆ
$settingsStmt = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1");
$settings = $settingsStmt->fetch();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบศูนย์สนับสนุนหลังบ้านแอดมิน | โรงเรียนบ้านหนองหว้า</title>
    <!-- ฟอนต์ Kanit และ Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                        heading: ['Kanit', 'sans-serif'],
                    },
                    colors: {
                        school: {
                            pink: {
                                light: '#f472b6',
                                DEFAULT: '#ec4899',
                                dark: '#be185d',
                            }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 font-sans flex flex-col justify-between">

    <!-- แถบแถบหัวเมนูด้านบนแอดมิน -->
    <header class="bg-indigo-950 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-school-pink text-white rounded-lg text-xs font-black animate-pulse">ADMIN MODULE</span>
                <span class="text-white text-sm font-semibold hidden sm:inline"><?php echo htmlspecialchars($settings['school_name']); ?></span>
            </div>
            
            <div class="flex items-center gap-4 text-xs font-bold font-heading">
                <span class="text-pink-300">เข้าใช้บัญชีโดย: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="index.php" target="_blank" class="bg-white/10 hover:bg-white/15 px-3 py-2 rounded-lg border border-white/10 transition flex items-center gap-1.5 text-[11px]">
                    👁️ เปิดหน้าแรกเว็บโรงเรียน
                </a>
                <a href="admin.php?action=logout" class="bg-rose-600 hover:bg-rose-700 px-3 py-2 rounded-lg transition">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </header>

    <!-- พื้นหลักการแสดงรายงานผลการทำงาน -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow space-y-8 w-full">
        
        <!-- แบนเนอร์อธิบายตารางหลังบ้านอัตโนมัติ -->
        <div class="bg-gradient-to-r from-school-pink-dark via-school-pink to-pink-500 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 skew-x-12 translate-x-10 pointer-events-none"></div>
            <div class="relative z-10 space-y-2">
                <div class="inline-flex items-center gap-1.5 bg-white/20 text-white rounded-full px-3 py-1 text-[10px] font-black tracking-wider uppercase backdrop-blur-sm shadow-inner mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-ping"></span>
                    การเชื่อมต่อเซิร์ฟเวอร์เรียบร้อย: ตารางสัมพันธ์ออโต้ไลนท์
                </div>
                <h1 class="text-2xl sm:text-3xl font-heading font-black">ระบบสตรีมจัดการสารสนเทศหลังบ้านโรงเรียนบ้านหนองหว้า</h1>
                <p class="text-xs text-pink-50 max-w-2xl font-light leading-relaxed">
                    ยินดีต้อนรับเข้าสู่วิเศษวิชาการจัดการโรงเรียนบ้านหนองหว้า แยกแท็บการแก้ไขออกเป็นบล็อกระบบอิสระ สะดวก รวดเร็ว สอดรับกับแนวคิดความสวยงามความเรียบง่ายสะอ้านตา
                </p>
            </div>
        </div>

        <!-- กล่องสถานะทรานแซคชั่นระบบ -->
        <?php if (!empty($success_alert)): ?>
            <div class="bg-green-50 rounded-2xl p-4 text-green-700 text-xs font-bold border border-green-100 flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <?php echo $success_alert; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($err_alert)): ?>
            <div class="bg-red-50 rounded-2xl p-4 text-red-600 text-xs font-bold border border-red-100 flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <?php echo $err_alert; ?>
            </div>
        <?php endif; ?>

        <!-- เมนูจัดแท่งจัดหมวดหมู่โมดูลความสะดวกสบาย -->
        <?php
        $active_tab = 'general';
        if (isset($_GET['tab'])) {
            $active_tab = cleanInput($_GET['tab']);
        } elseif (isset($_POST['update_students'])) {
            $active_tab = 'students';
        } elseif (isset($_GET['edit_news']) || (isset($_GET['action']) && strpos($_GET['action'], 'news') !== false) || isset($_POST['add_news']) || isset($_POST['edit_news_submit'])) {
            $active_tab = 'news';
        } elseif (isset($_GET['edit_doc']) || (isset($_GET['action']) && strpos($_GET['action'], 'doc') !== false) || isset($_POST['add_doc']) || isset($_POST['edit_doc_submit'])) {
            $active_tab = 'downloads';
        } elseif (isset($_GET['edit_teacher']) || (isset($_GET['action']) && strpos($_GET['action'], 'teacher') !== false) || isset($_POST['add_teacher']) || isset($_POST['edit_teacher_submit'])) {
            $active_tab = 'teachers';
        } elseif (isset($_GET['edit_link']) || (isset($_GET['action']) && strpos($_GET['action'], 'link') !== false) || isset($_POST['add_link']) || isset($_POST['edit_link_submit'])) {
            $active_tab = 'links';
        }
        ?>
        <div class="flex flex-wrap gap-1.5 border-b border-slate-200 pb-px">
            <a href="admin.php?tab=general" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'general' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                ⚙️ ตั้งค่าทั่วไป
            </a>
            <a href="admin.php?tab=students&sub=stats" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'students' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                📊 สถิติจำนวนนักเรียนรายปี
            </a>
            <a href="admin.php?tab=news" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'news' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                📰 ข่าวประชาสัมพันธ์
            </a>
            <a href="admin.php?tab=downloads" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'downloads' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                📂 เอกสารแผนงานต่างๆ
            </a>
            <a href="admin.php?tab=teachers" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'teachers' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                🧑‍🏫 ข้อมูลครูและทำเนียบ
            </a>
            <a href="admin.php?tab=links" class="px-4 py-3 rounded-t-2xl font-heading font-black text-xs sm:text-sm flex items-center gap-2 transition <?php echo $active_tab === 'links' ? 'bg-white text-school-pink border-t-2 border-school-pink border-x border-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-850 bg-slate-100/40 hover:bg-slate-100'; ?>">
                🔗 สื่อและระบบงานครู
            </a>
        </div>

        <!-- คลังกล่องสวิตช์โหลดไฟล์เทมเพลตที่แยกโมดูลเรียบร้อยแล้ว -->
        <div class="mt-4 transition-all duration-300">
            <?php 
            if ($active_tab === 'general') {
                require_once 'admin/general.php';
            } elseif ($active_tab === 'students') {
                require_once 'admin/students.php';
            } elseif ($active_tab === 'news') {
                require_once 'admin/news.php';
            } elseif ($active_tab === 'downloads') {
                require_once 'admin/downloads.php';
            } elseif ($active_tab === 'teachers') {
                require_once 'admin/teachers.php';
            } elseif ($active_tab === 'links') {
                require_once 'admin/external_links.php';
            } else {
                require_once 'admin/general.php';
            }
            ?>
        </div>

    </main>

    <footer class="bg-slate-900 text-slate-500 text-center py-6 border-t border-slate-800 text-[11px] font-medium leading-loose mt-8">
        <p>© 2026 โรงเรียนบ้านหนองหว้า | แผงส่งเสริมการจัดการสารสนเทศแยกส่วนอัตลักษณ์ชมพูขาว</p>
    </footer>

    <!-- ⚡ ระบบช่วยเชื่อมสายอัปโหลดส่งตรงขึ้น Google Drive / คลาวด์อัตโนมัติ -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. ตารางจับคู่ชื่อฟิลด์ไฟล์ กับชื่อฟิลด์รับค่า URL/พาร์ทปลายทาง
        const fileToUrlFieldMap = {
            'school_logo_file': ['school_logo_url', 'school_logo'],
            'banner_bg_file': ['banner_bg_url', 'banner_bg_image'],
            'banner_right_file': ['banner_right_url', 'banner_right_image'],
            'director_image_file': ['director_image'],
            'link_image_file': ['link_image_url'],
            'teacher_image_file': ['teacher_image'],
            'teacher_pa_file': ['teacher_pa_url'],
            'teacher_portfolio_file': ['teacher_portfolio_url'],
            'doc_file': ['doc_url'],
            'news_image_file': ['news_image']
        };

        // 2. สแกนหาฟิลด์อินพุตไฟล์ทั้งหมดในหน้านี้
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(function(fileInput) {
            const name = fileInput.name;
            if (!name) return;
            
            // ค้นหาฟิลด์ข้อความรับค่า URL ที่เชื่อมโยงอยู่
            let urlInput = null;
            let possibleNames = fileToUrlFieldMap[name] || [];
            
            // ลองหาจากไอดีหรือชื่อฟิลด์ในฟอร์มเดียวกัน
            const form = fileInput.closest('form');
            if (form) {
                for (let pName of possibleNames) {
                    urlInput = form.querySelector(`[name="${pName}"]`);
                    if (urlInput) break;
                }
                if (!urlInput) {
                    // ถ้ายังสแกนหาฟิลด์เฉพาะไม่เจอ ให้ค้นหาตัวเลือกที่ดีที่สุดในหมวดหมู่เดียวกัน
                    const textFields = form.querySelectorAll('input[type="text"], input[type="url"]');
                    textFields.forEach(tf => {
                        if (tf.name && (tf.name.includes('url') || tf.name.includes('image') || tf.name.includes('logo') || tf.name.includes('doc'))) {
                            urlInput = tf;
                        }
                    });
                }
            }
            
            if (!urlInput) return; // หากไม่ตรวจเจอพิกัดฟิลด์คู่ขนานที่จะจัดเก็บ URL ให้ข้าม

            // 3. ปรับแต่งและสร้างแถบควบคุม UI เพิ่มเติมอย่างแนบเนียน
            const wrapper = document.createElement('div');
            wrapper.className = "mt-2.5 p-3.5 bg-blue-50/15 border border-blue-200/50 rounded-2xl space-y-2.5 text-[11px] font-semibold text-slate-700 shadow-sm";
            
            const header = document.createElement('div');
            header.className = "flex items-center justify-between font-bold text-teal-900 gap-1.5";
            header.innerHTML = `
                <span class="flex items-center gap-1">☁️ นวัตกรรมตัวช่วยอัปโหลดตรงขึ้นระบบคลาวด์ Google Drive</span>
                <span class="text-[8px] bg-emerald-500 text-white font-extrabold px-1.5 py-0.5 rounded uppercase">RECOMMENDED</span>
            `;
            wrapper.appendChild(header);

            const controlRow = document.createElement('div');
            controlRow.className = "flex flex-wrap items-center gap-2";
            
            const uploadBtn = document.createElement('button');
            uploadBtn.type = "button";
            uploadBtn.className = "bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold py-1.5 px-3 rounded-xl transition shadow-sm flex items-center gap-1 cursor-pointer";
            uploadBtn.innerHTML = "⚡ กดส่งไฟล์ขึ้นไดรฟ์ทันที";
            uploadBtn.disabled = true; 
            
            const statusSpan = document.createElement('span');
            statusSpan.className = "text-[10px] text-slate-400 font-medium";
            statusSpan.textContent = "📂 ยังไม่ได้เลือกไฟล์ในเครื่องของคุณ";

            controlRow.appendChild(uploadBtn);
            controlRow.appendChild(statusSpan);
            wrapper.appendChild(controlRow);

            // กล่องแสดงพรีวิวผลงานทันที
            const previewContainer = document.createElement('div');
            previewContainer.className = "preview-box hidden pt-2 border-t border-blue-100/50";
            wrapper.appendChild(previewContainer);

            // แทรกกล่องควบคุมเข้าไปหลังฟลายโฆษณาเลือกไฟล์เครื่องมือนั้น
            fileInput.parentNode.insertBefore(wrapper, fileInput.nextSibling);

            // กระตุ้นการตรวจจับไฟล์เมื่อมีการคลิกเลือกเปลี่ยน (file onChange)
            fileInput.addEventListener('change', function() {
                if (fileInput.files && fileInput.files.length > 0) {
                    const selectedFile = fileInput.files[0];
                    statusSpan.className = "text-[10px] text-indigo-600 font-bold";
                    statusSpan.textContent = "✔️ ดึงไฟล์พร้อมส่ง: " + selectedFile.name + " (" + formatBytes(selectedFile.size) + ")";
                    uploadBtn.disabled = false;
                } else {
                    statusSpan.className = "text-[10px] text-slate-400 font-semibold";
                    statusSpan.textContent = "📂 ยังไม่ได้เลือกไฟล์ในเครื่องของคุณ";
                    uploadBtn.disabled = true;
                }
            });

            // ดำเนินการอัปโหลดไฟล์ด่วนแบบ asynchronous
            uploadBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                let selectedFile = fileInput.files[0];
                if (!selectedFile) return;

                uploadBtn.disabled = true;
                fileInput.disabled = true;

                // ตรวจระบบและบีบอัดปรับขนาดภาพกล้องมือถือ/ภาพกิจกรรมความละเอียดสูงโดยอัตโนมัติก่อนส่งขึ้นคลาวด์
                if (selectedFile.type.startsWith('image/')) {
                    statusSpan.className = "text-[10px] text-amber-600 font-bold animate-pulse";
                    statusSpan.innerHTML = `⚡ กำลังลดขนาดและปรับความละเอียดภาพกิจกรรมโดยอัตโนมัติ...`;
                    
                    try {
                        const originalSize = selectedFile.size;
                        const compressedFile = await compressImageIfNeeded(selectedFile, 1600, 1600, 0.75);
                        if (compressedFile && compressedFile.size < originalSize) {
                            const savedPercent = Math.round(((originalSize - compressedFile.size) / originalSize) * 100);
                            console.log(`Compressed: ${formatBytes(originalSize)} => ${formatBytes(compressedFile.size)} (Save ${savedPercent}%)`);
                            selectedFile = compressedFile;
                            statusSpan.innerHTML = `✨ ย่อขนาดรูปอัจฉริยะประหยัดพื้นที่คลาวด์ไป ${savedPercent}% (${formatBytes(compressedFile.size)})`;
                        }
                    } catch (err) {
                        console.error('Image compression failed, fallback to original:', err);
                    }
                }

                statusSpan.className = "text-[10px] text-blue-600 font-bold animate-pulse";
                statusSpan.innerHTML = `⏳ กำลังอัปโหลดส่งตรงขึ้น Google Drive แฟ้มโรงเรียน...`;

                const formData = new FormData();
                formData.append('file', selectedFile);

                // ดึงค่า URL ของคลาสกรองและ ID โฟลเดอร์ที่ผู้ใช้อาจเขียนหรือแก้ไขไว้บนหน้าจอแบบเรียลไทม์
                const gasUrlField = document.querySelector('input[name="google_apps_script_url"]');
                const folderIdField = document.querySelector('input[name="google_drive_folder_id"]');
                if (gasUrlField && gasUrlField.value.trim() !== '') {
                    formData.append('google_apps_script_url', gasUrlField.value.trim());
                }
                if (folderIdField && folderIdField.value.trim() !== '') {
                    formData.append('google_drive_folder_id', folderIdField.value.trim());
                }

                let allowedStr = "jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip";
                if (fileInput.accept) {
                    if (fileInput.accept.includes('image')) allowedStr = "jpg,jpeg,png,gif";
                    else if (fileInput.accept.includes('pdf')) allowedStr = "pdf";
                }

                // สตรีมมิ่งผ่าน AJAX API
                fetch(`admin.php?action=ajax_upload&allowed=${allowedStr}`, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // อัปเดตช่องข้อความรับพาร์ท URL อัตโนมัติ
                        urlInput.value = data.url;
                        urlInput.dispatchEvent(new Event('change'));

                        // ระบบช่วยกรอกขนาดและนามสกุลสำหรับแฟ้มเอกสารดาวน์โหลดอัตโนมัติ
                        const form = fileInput.closest('form');
                        if (form) {
                            const sizeInput = form.querySelector('input[name="doc_size"]');
                            const typeInput = form.querySelector('input[name="doc_type"]');
                            if (sizeInput && (!sizeInput.value || sizeInput.value === '1.2 MB' || sizeInput.value === '#' || sizeInput.value === '')) {
                                sizeInput.value = formatBytes(selectedFile.size);
                            }
                            if (typeInput && (!typeInput.value || typeInput.value === '#' || typeInput.value === '')) {
                                typeInput.value = selectedFile.name.split('.').pop().toUpperCase();
                            }
                        }

                        // ล้างไฟล์ออกจาก selector เพื่อไม่ให้เบราว์เซอร์ไปเซฟซ้ำซ้อนพังลงตารางโลคอล
                        fileInput.value = ''; 
                        
                        statusSpan.className = "text-[10px] text-emerald-600 font-bold";
                        statusSpan.innerHTML = `🎉 อัปโหลดขึ้นระบบ Google Drive สำเร็จไร้รอยต่อ!`;
                        
                        uploadBtn.disabled = true;
                        fileInput.disabled = false;

                        // โชว์กล่องพรีวิวเพื่อความสบายใจของผู้ใช้
                        previewContainer.classList.remove('hidden');
                        const isImg = data.url.includes('lh3.googleusercontent.com') || data.url.match(/\.(jpeg|jpg|gif|png)/i);
                        
                        if (isImg) {
                            previewContainer.innerHTML = `
                                <span class="block text-slate-500 font-bold text-[9px] mb-1">👀 ตัวอย่างความละเอียดภาพบน Google Drive:</span>
                                <div class="relative inline-block mt-1">
                                    <img src="${data.url}" referrerPolicy="no-referrer" class="max-h-24 max-w-full rounded-xl object-contain border border-emerald-300 shadow-sm p-1 bg-white">
                                    <span class="absolute bottom-1 right-1 bg-emerald-600 text-[8px] text-white font-extrabold px-1.5 py-0.5 rounded shadow">LIVE ON CLOUD</span>
                                </div>
                            `;
                        } else {
                            previewContainer.innerHTML = `
                                <span class="block text-slate-500 font-bold text-[9px] mb-1">👀 ตัวอย่างลิงก์เอกสารอัปโหลด:</span>
                                <a href="${data.url}" target="_blank" class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-700 underline font-bold bg-white p-1.5 rounded-lg border border-slate-100">
                                    📄 คลิกตรวจดูไฟล์บนไดรฟ์สุนทรียภาพ (${selectedFile.name})
                                </a>
                            `;
                        }
                    } else {
                        throw new Error(data.message || 'การแปลงส่งข้อมูลติดขัด');
                    }
                })
                .catch(err => {
                    statusSpan.className = "text-[10px] text-red-600 font-bold";
                    statusSpan.innerHTML = `❌ การอัปโหลดติดปัญหา: ` + err.message + ` (สามารถพิมพ์ใส่ URL เองตรงด้านบนได้)`;
                    uploadBtn.disabled = false;
                    fileInput.disabled = false;
                });
            });
        });

        // ฟังก์ชันบีบอัดภาพและลดขนาดความละเอียดภาพสำหรับกล้องถ่ายภาพความละเอียดสูง
        function compressImageIfNeeded(file, maxWidth = 1600, maxHeight = 1600, quality = 0.75) {
            return new Promise((resolve) => {
                if (!file || !file.type.startsWith('image/')) {
                    resolve(file);
                    return;
                }

                // สำหรับไฟล์รูปภาพที่มีขนาดต่ำกว่า 350KB ไม่จำเป็นต้องลดขนาด/บีบอัด ให้ใช้ต้นฉบับเลย
                if (file.size < 350 * 1024) {
                    resolve(file);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.onload = function() {
                        let width = img.width;
                        let height = img.height;

                        // ตรวจสอบและย่อขนาดเมื่อด้านใดด้านหนึ่งยาวเกินกำหนด
                        if (width > maxWidth || height > maxHeight) {
                            if (width > height) {
                                if (width > maxWidth) {
                                    height = Math.round((height * maxWidth) / width);
                                    width = maxWidth;
                                }
                            } else {
                                if (height > maxHeight) {
                                    width = Math.round((width * maxHeight) / height);
                                    height = maxHeight;
                                }
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(function(blob) {
                            if (blob) {
                                let originalName = file.name;
                                let extIdx = originalName.lastIndexOf('.');
                                let baseName = extIdx !== -1 ? originalName.substring(0, extIdx) : originalName;
                                let newName = baseName + '_opt.jpg';

                                const compressedFile = new File([blob], newName, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve(compressedFile);
                            } else {
                                resolve(file);
                            }
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = function() {
                        resolve(file);
                    };
                    img.src = event.target.result;
                };
                reader.onerror = function() {
                    resolve(file);
                };
                reader.readAsDataURL(file);
            });
        }

        // ฟังก์ชันช่วยจัดแจงขนาดหน่วยความจำไบต์คอมพิวเตอร์
        function formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    });
    </script>

</body>
</html>
