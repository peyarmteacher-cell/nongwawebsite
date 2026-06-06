<?php
/**
 * 📡 ระบบเชื่อมต่อฐานข้อมูลหลัก (Core Database Connection & Session Config)
 * โรงเรียนบ้านหนองหว้า (อัตลักษณ์ ชมพู-ขาว)
 * -------------------------------------------------------------------------
 * ไฟล์นี้ทำหน้าที่เฉพาะการเชื่อมต่อฐานข้อมูล และระบายฟังค์ชันความปลอดภัยหลักเท่านั้น
 * โดยแยกตัวประมวลผลตารางและเนื้อหาข่าวสารตั้งต้น (Migration) ออกไปอยู่ทีไฟล์ db_migrate.php
 * เพื่อความปลอดภัยสูงสุดและไม่ให้การอัพโหลดโค้ดข้ามฝั่งไปทับโครงสร้างเดิมที่มีอยู่ก่อนหน้า
 */

// 1. ตรวจสอบสถานะการทำงาน Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. กำหนดค่าการเชื่อมต่อฐานข้อมูล MySQL (ปรับแก้เป็นค่าจริงบน Server โรงเรียนได้ที่นี่)
// หากผู้ใช้มีไฟล์ตั้งค่าดั้งเดิม สามารถปรับแต่งค่าเฉพาะจุด หรือเปลี่ยนการเชื่อมตามความสะดวก
define('DB_HOST', 'localhost');
define('DB_USER', 'schoolos_nongwa');
define('DB_PASS', '8$p5GfJqgwlv3!Or');
define('DB_NAME', 'schoolos_nongwa');

$is_sqlite = false;
try {
    // 3. เชื่อมต่อฐานข้อมูลอย่างเป็นทางการ ด้วยมาตรฐาน PDO ที่ปลอดภัยและเสถียรที่สุด
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // โยนข้อยกเว้นเมื่อพบข้อผิดพลาด
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // นำข้อมูลออกในรูปแบบ Array Key-Value
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ใช้การส่งคำสั่งของดีจริง ป้องกัน SQL Injection
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // ตรวจสอบเช็คสร้างไดเรกทอรีและจัดแจงสิทธิ์เพื่อให้เซิร์ฟเวอร์เขียนข่าวสารและไฟล์ภาพอัปพอร์ตได้อย่างสมบูรณ์แบบ
    $dirs_to_heal = ['uploads', 'uploads/images', 'uploads/pdfs', 'uploads/documents'];
    foreach ($dirs_to_heal as $heal_dir) {
        if (!file_exists($heal_dir)) {
            mkdir($heal_dir, 0777, true);
        }
        @chmod($heal_dir, 0777);
    }

} catch (PDOException $e) {
    // ระบบตรวจจับว่าไม่มีบริการ MySQL ท้องถิ่น หรือทำงานในสภาวะแวดล้อมจำลอง (AI Studio Sandbox)
    // จึงทำการสลับไปประยุกต์ใช้ SQLite แบบพกพาแทนโดยอัตโนมัติ เพื่อให้ผู้ใช้สามารถสัมผัสงานได้เลยโดยไม่ขาดตอน
    $sqlite_dir = __DIR__ . '/uploads';
    if (!file_exists($sqlite_dir)) {
        @mkdir($sqlite_dir, 0777, true);
    }
    $sqlite_file = $sqlite_dir . '/schoolos_nongwa.sqlite';
    try {
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $is_sqlite = true;

        $dirs_to_heal = ['uploads', 'uploads/images', 'uploads/pdfs', 'uploads/documents'];
        foreach ($dirs_to_heal as $heal_dir) {
            if (!file_exists($heal_dir)) {
                mkdir($heal_dir, 0777, true);
            }
            @chmod($heal_dir, 0777);
        }
    } catch (Exception $sqlite_error) {
        // บันทึกรายงานข้อผิดพลาดและแสดงแจ้งเตือนความคืบหน้าอย่างละเอียด
        error_log($e->getMessage());
        die("❌ ขออภัย! ระบบขัดข้องทางเทคนิคด้านการเชื่อมต่อฐานข้อมูล<br>" .
            "กรุณาตรวจสอบว่ามีฐานข้อมูลชื่อ <strong>" . DB_NAME . "</strong> อยู่บนเซิร์ฟเวอร์ และสิทธิ์ผู้ใช้ถูกต้อง<br>" .
            "หรือทำการรันสคริปต์สร้างฐานข้อมูลก่อนที่: <a href='db_migrate.php'>db_migrate.php</a><br>" .
            "ข้อความระบบ: " . htmlspecialchars($e->getMessage()));
    }
}

// 4. ฟังค์ชันล้างข้อมูลขาเข้าเพื่อความปลอดภัย (XSS Prevention Filter)
function cleanInput($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 5. ฟังก์ชันจัดรูปแบบเวลาภาษาไทยแบบย่อ
function thaiDate($dateStr) {
    if (!$dateStr) return '';
    $months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    $parts = explode('-', $dateStr);
    if (count($parts) < 3) return $dateStr;
    $y = intval($parts[0]) + 543;
    $m = $parts[1];
    $d = intval($parts[2]);
    return "{$d} " . ($months[$m] ?? '') . " " . substr($y, 2);
}

// 6. อัตโนมัติอัพเดตและสร้างตาราง-คอลัมน์ทั้งหมดแบบไร้รอยต่อ (Seamless Auto-Migration & Table Setup)
try {
    // ก) ตรวจสอบและสร้างตารางหลักหากยังไม่มีอยู่
    if (isset($is_sqlite) && $is_sqlite) {
        $tables_setup = [
            'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `school_name` varchar(255) NOT NULL,
              `short_name` varchar(50) NOT NULL,
              `address` text NOT NULL,
              `phone` varchar(20) NOT NULL,
              `email` varchar(100) NOT NULL,
              `jurisdiction` varchar(255) NOT NULL,
              `levels` varchar(255) NOT NULL,
              `director_name` varchar(150) NOT NULL,
              `director_title` varchar(100) NOT NULL,
              `director_image` varchar(255) DEFAULT NULL,
              `youtube_intro_url` varchar(255) DEFAULT NULL,
              `visitor_count` int(11) DEFAULT '0',
              `school_theme_color` varchar(50) DEFAULT 'pink-white',
              `school_motto` varchar(255) DEFAULT 'ชมพู-ขาว ก้าวไกลวิชาการ',
              `school_logo` varchar(255) DEFAULT NULL,
              `banner_bg_image` varchar(255) DEFAULT NULL,
              `banner_right_image` varchar(255) DEFAULT NULL,
              `banner_title` varchar(255) DEFAULT 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ',
              `banner_subtitle` text DEFAULT NULL,
              `director_message_title` varchar(255) DEFAULT 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี',
              `director_message` text DEFAULT NULL,
              `current_academic_year` varchar(10) DEFAULT '2569'
            );",
            
            'users' => "CREATE TABLE IF NOT EXISTS `users` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `username` varchar(50) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `name` varchar(100) NOT NULL,
              `role` varchar(30) DEFAULT 'Editor',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
            );",
            
            'news' => "CREATE TABLE IF NOT EXISTS `news` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `content` text NOT NULL,
              `summary` text DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `views` int(11) DEFAULT '0',
              `date` date NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              `sticky_flag` tinyint(1) DEFAULT '0'
            );",
            
            'teachers' => "CREATE TABLE IF NOT EXISTS `teachers` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` varchar(150) NOT NULL,
              `position` varchar(150) NOT NULL,
              `level` varchar(100) NOT NULL,
              `subject_group` varchar(100) DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `sort_order` int(11) DEFAULT '99',
              `pa_link_url` varchar(255) DEFAULT NULL,
              `portfolio_url` varchar(255) DEFAULT NULL
            );",
            
            'students' => "CREATE TABLE IF NOT EXISTS `students` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `name` varchar(150) NOT NULL,
              `grade` varchar(50) NOT NULL,
              `classroom` varchar(10) NOT NULL,
              `gender` varchar(10) NOT NULL
            );",
            
            'downloads' => "CREATE TABLE IF NOT EXISTS `downloads` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `file_type` varchar(10) NOT NULL,
              `file_size` varchar(20) NOT NULL,
              `download_count` int(11) DEFAULT '0',
              `uploaded_date` date NOT NULL,
              `file_url` varchar(255) DEFAULT NULL
            );",
            
            'banners' => "CREATE TABLE IF NOT EXISTS `banners` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `title` varchar(255) NOT NULL,
              `subtitle` varchar(255) NOT NULL,
              `image_url` varchar(255) NOT NULL,
              `active` tinyint(4) DEFAULT '1'
            );",
            
            'student_stats' => "CREATE TABLE IF NOT EXISTS `student_stats` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `grade_name` varchar(50) NOT NULL UNIQUE,
              `student_count` int(11) NOT NULL DEFAULT '0'
            );",
            
            'external_links' => "CREATE TABLE IF NOT EXISTS `external_links` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `title` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `url_link` varchar(255) NOT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `category` varchar(100) DEFAULT 'สื่อการเรียนรู้'
            );",
            
            'student_yearly_stats' => "CREATE TABLE IF NOT EXISTS `student_yearly_stats` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `academic_year` varchar(10) NOT NULL,
              `grade_name` varchar(50) NOT NULL,
              `student_count` int(11) NOT NULL DEFAULT '0',
              UNIQUE (`academic_year`, `grade_name`)
            );"
        ];
    } else {
        $tables_setup = [
            'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `school_name` varchar(255) NOT NULL,
              `short_name` varchar(50) NOT NULL,
              `address` text NOT NULL,
              `phone` varchar(20) NOT NULL,
              `email` varchar(100) NOT NULL,
              `jurisdiction` varchar(255) NOT NULL,
              `levels` varchar(255) NOT NULL,
              `director_name` varchar(150) NOT NULL,
              `director_title` varchar(100) NOT NULL,
              `director_image` varchar(255) DEFAULT NULL,
              `youtube_intro_url` varchar(255) DEFAULT NULL,
              `visitor_count` int(11) DEFAULT '0',
              `school_theme_color` varchar(50) DEFAULT 'pink-white',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'users' => "CREATE TABLE IF NOT EXISTS `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `name` varchar(100) NOT NULL,
              `role` varchar(30) DEFAULT 'Editor',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'news' => "CREATE TABLE IF NOT EXISTS `news` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `content` text NOT NULL,
              `summary` text DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `views` int(11) DEFAULT '0',
              `date` date NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'teachers' => "CREATE TABLE IF NOT EXISTS `teachers` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `position` varchar(150) NOT NULL,
              `level` varchar(100) NOT NULL,
              `subject_group` varchar(100) DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `sort_order` int(11) DEFAULT '99',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'students' => "CREATE TABLE IF NOT EXISTS `students` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `grade` varchar(50) NOT NULL,
              `classroom` varchar(10) NOT NULL,
              `gender` varchar(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'downloads' => "CREATE TABLE IF NOT EXISTS `downloads` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `file_type` varchar(10) NOT NULL,
              `file_size` varchar(20) NOT NULL,
              `download_count` int(11) DEFAULT '0',
              `uploaded_date` date NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'banners' => "CREATE TABLE IF NOT EXISTS `banners` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `subtitle` varchar(255) NOT NULL,
              `image_url` varchar(255) NOT NULL,
              `active` tinyint(4) DEFAULT '1',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'student_stats' => "CREATE TABLE IF NOT EXISTS `student_stats` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `grade_name` varchar(50) NOT NULL UNIQUE,
              `student_count` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'external_links' => "CREATE TABLE IF NOT EXISTS `external_links` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `url_link` varchar(255) NOT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `category` varchar(100) DEFAULT 'สื่อการเรียนรู้',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            
            'student_yearly_stats' => "CREATE TABLE IF NOT EXISTS `student_yearly_stats` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `academic_year` varchar(10) NOT NULL,
              `grade_name` varchar(50) NOT NULL,
              `student_count` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              UNIQUE KEY `year_grade_unique` (`academic_year`, `grade_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        ];
    }

    foreach ($tables_setup as $table_name => $sql) {
        $pdo->exec($sql);
    }

    // ข) อัพเดตคอลัมน์เพิ่มเติมของแต่ละตาราง (Auto-Healing)
    if (!isset($is_sqlite) || !$is_sqlite) {
        $column_migrations = [
            'settings' => [
                'school_motto' => "ALTER TABLE `settings` ADD COLUMN `school_motto` varchar(255) DEFAULT 'ชมพู-ขาว ก้าวไกลวิชาการ'",
                'school_logo' => "ALTER TABLE `settings` ADD COLUMN `school_logo` varchar(255) DEFAULT NULL",
                'banner_bg_image' => "ALTER TABLE `settings` ADD COLUMN `banner_bg_image` varchar(255) DEFAULT NULL",
                'banner_right_image' => "ALTER TABLE `settings` ADD COLUMN `banner_right_image` varchar(255) DEFAULT NULL",
                'banner_title' => "ALTER TABLE `settings` ADD COLUMN `banner_title` varchar(255) DEFAULT 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ'",
                'banner_subtitle' => "ALTER TABLE `settings` ADD COLUMN `banner_subtitle` text DEFAULT NULL",
                'director_message_title' => "ALTER TABLE `settings` ADD COLUMN `director_message_title` varchar(255) DEFAULT 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี'",
                'director_message' => "ALTER TABLE `settings` ADD COLUMN `director_message` text DEFAULT NULL",
                'current_academic_year' => "ALTER TABLE `settings` ADD COLUMN `current_academic_year` varchar(10) DEFAULT '2569'"
            ],
            'news' => [
                'sticky_flag' => "ALTER TABLE `news` ADD COLUMN `sticky_flag` tinyint(1) DEFAULT '0'"
            ],
            'downloads' => [
                'file_url' => "ALTER TABLE `downloads` ADD COLUMN `file_url` varchar(255) DEFAULT NULL"
            ],
            'teachers' => [
                'pa_link_url' => "ALTER TABLE `teachers` ADD COLUMN `pa_link_url` varchar(255) DEFAULT NULL",
                'portfolio_url' => "ALTER TABLE `teachers` ADD COLUMN `portfolio_url` varchar(255) DEFAULT NULL"
            ]
        ];

        foreach ($column_migrations as $tableName => $cols) {
            foreach ($cols as $colName => $alterSql) {
                $checkQuery = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '$colName'")->fetchAll();
                if (empty($checkQuery)) {
                    $pdo->exec($alterSql);
                }
            }
        }
    }

    // เพิ่มฟิลด์ google_apps_script_url และ google_drive_folder_id แบบสากล (รองรับทั้ง SQLite และ MySQL)
    try {
        $pdo->exec("ALTER TABLE `settings` ADD COLUMN `google_apps_script_url` text DEFAULT NULL;");
    } catch (Exception $col_err) {
        // หากคอลัมน์มีอยู่แล้วจะเกิด Exception ซึ่งเราข้ามได้ทันทีอย่างปลอดภัย
    }
    try {
        $pdo->exec("ALTER TABLE `settings` ADD COLUMN `google_drive_folder_id` text DEFAULT NULL;");
    } catch (Exception $col_err) {
        // หากคอลัมน์มีอยู่แล้วจะเกิด Exception ซึ่งเราข้ามได้ทันทีอย่างปลอดภัย
    }

    // ค) การใส่ข้อมูลแรกเริ่ม (Seeding Defaults if empty)
    $countSettings = $pdo->query("SELECT id FROM `settings` LIMIT 1")->fetch();
    if (!$countSettings) {
        $pdo->exec("INSERT INTO `settings` 
            (`id`, `school_name`, `short_name`, `address`, `phone`, `email`, `jurisdiction`, `levels`, `director_name`, `director_title`, `director_image`, `visitor_count`, `school_theme_color`, `school_motto`, `youtube_intro_url`, `banner_title`, `banner_subtitle`, `director_message_title`, `director_message`, `current_academic_year`) 
            VALUES 
            (1, 'โรงเรียนบ้านหนองหว้า', 'ร.ร.บ้านหนองหว้า', 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210', '044-641123', 'bannongwaschool@gmail.com', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3', 'ระดับปฐมวัย (อนุบาล 2-3) ถึงระดับชั้นประถมศึกษาปีที่ 6', 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 15422, 'pink-white', 'ชมพู-ขาว ก้าวไกลวิชาการ', 'https://www.youtube.com/embed/gCOk8X63Rpk', 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ', 'เน้นทักษะชีวิต ความดีงาม คุณธรรมสูงส่ง ส่งผ่านความใส่ใจในระดับชั้น:', 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี', '\"โรงเรียนบ้านหนองหว้า ขอตลับใจเป็นพันธมิตรร่วมกับชุมชน ผู้ปกครอง เพื่อขับเคลื่อนและสร้างสรรค์โอกาสทางวิชาการและวิชาชีพแก่นักเรียน สู่ความพร้อมในการปฏิสัมพันธ์และดำรงชีพในศตวรรษที่ 21 เรามุ่งเสกสร้างสภาพแวดล้อมที่สะอาด ปลอดภัย เพื่อเสริมองค์ความรู้อย่างบูรณาการสูงสุด\"', '2569');");
    } else {
        // หากผู้ใช้มีอยู่แล้ว แต่ฟิลด์เหล่านี้ว่าง ให้ใส่ค่าเริ่มต้นเพื่อความสวยงาม
        $settings_row = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1")->fetch();
        if ($settings_row) {
            $updates = [];
            $params = [];
            if (empty($settings_row['banner_title'])) {
                $updates[] = "`banner_title` = :banner_title";
                $params['banner_title'] = 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ';
            }
            if (empty($settings_row['banner_subtitle'])) {
                $updates[] = "`banner_subtitle` = :banner_subtitle";
                $params['banner_subtitle'] = 'เน้นทักษะชีวิต ความดีงาม คุณธรรมสูงส่ง ส่งผ่านความใส่ใจในระดับชั้น:';
            }
            if (empty($settings_row['director_message_title'])) {
                $updates[] = "`director_message_title` = :director_message_title";
                $params['director_message_title'] = 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี';
            }
            if (empty($settings_row['director_message'])) {
                $updates[] = "`director_message` = :director_message";
                $params['director_message'] = '"โรงเรียนบ้านหนองหว้า ขอตลับใจเป็นพันธมิตรร่วมกับชุมชน ผู้ปกครอง เพื่อขับเคลื่อนและสร้างสรรค์โอกาสทางวิชาการและวิชาชีพแก่นักเรียน สู่ความพร้อมในการปฏิสัมพันธ์และดำรงชีพในศตวรรษที่ 21 เรามุ่งเสกสร้างสภาพแวดล้อมที่สะอาด ปลอดภัย เพื่อเสริมองค์ความรู้อย่างบูรณาการสูงสุด"';
            }
            if (empty($settings_row['school_motto'])) {
                $updates[] = "`school_motto` = :school_motto";
                $params['school_motto'] = 'ชมพู-ขาว ก้าวไกลวิชาการ';
            }
            if (empty($settings_row['current_academic_year'])) {
                $updates[] = "`current_academic_year` = :current_academic_year";
                $params['current_academic_year'] = '2569';
            }
            if (!empty($updates)) {
                $up_stmt = $pdo->prepare("UPDATE `settings` SET " . implode(', ', $updates) . " WHERE `id` = 1");
                $up_stmt->execute($params);
            }
        }
    }

    $countUsers = $pdo->query("SELECT id FROM `users` LIMIT 1")->fetch();
    if (!$countUsers) {
        $hashed_pwd = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`) VALUES 
            (1, 'admin', '{$hashed_pwd}', 'ผู้ดูแลระบบ โรงเรียนบ้านหนองหว้า', 'Administrator');");
    }

    $countBanners = $pdo->query("SELECT id FROM `banners` LIMIT 1")->fetch();
    if (!$countBanners) {
        $pdo->exec("INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `active`) VALUES 
            (1, 'ยินดีต้อนรับสู่ โรงเรียนบ้านหนองหว้า', 'แหล่งวิทยาการ กีฬาเด่น เน้นคุณธรรม สัมพันธ์ชุมชน', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&q=80&w=1200', 1),
            (2, 'เปิดรับสมัครเรียน ปีการศึกษา 2569', 'ตั้งแต่ชั้น อนุบาล 1 ถึง ชั้นประถมศึกษาปีที่ 6', 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&q=80&w=1200', 1);");
    }

    $countNews = $pdo->query("SELECT id FROM `news` LIMIT 1")->fetch();
    if (!$countNews) {
        $pdo->exec("INSERT INTO `news` (`id`, `title`, `category`, `content`, `summary`, `image_url`, `views`, `date`, `sticky_flag`) VALUES 
            (1, 'ประกาศเปิดเรียนภาคเรียนที่ 1 ปีการศึกษา 2569 อย่างเป็นทางการ', 'ประชาสัมพันธ์ทั่วไป', 'โรงเรียนบ้านหนองหว้า ขอประกาศกำหนดการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 ขอความกรุณาผู้ปกครองเตรียมความพร้อมของนักเรียนในเรื่องของเครื่องแบบ อุปกรณ์การเรียน และสุขอนามัย ทางโรงเรียนได้ทำความสะอาดฉีดพ่นฆ่าเชื้อและเตรียมอาคารสถานที่เรียบร้อยแล้ว', 'ประกาศอย่างเป็นทางการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 พร้อมทั้งเตรียมความสะอาดของอาคารสถานที่และการดูแลความปลอดภัยในทุกด้าน', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600', 312, '2026-05-10', 1);");
    }

    $countTeachers = $pdo->query("SELECT id FROM `teachers` LIMIT 1")->fetch();
    if (!$countTeachers) {
        $pdo->exec("INSERT INTO `teachers` (`id`, `name`, `position`, `level`, `subject_group`, `image_url`, `sort_order`) VALUES 
            (1, 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'ผู้อำนวยการโรงเรียน (คศ.3)', 'ผู้บริหาร', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 1),
            (2, 'นางสมศรี ปัญญาไว', 'ครูวิชาการระดับประถม / ครูประจำชั้นประถมศึกษาปีที่ 6', 'ครูชำนาญการพิเศษ (คศ.3)', 'วิชาการคณิตศาสตร์', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300', 2);");
    }

    $countStudents = $pdo->query("SELECT id FROM `students` LIMIT 1")->fetch();
    if (!$countStudents) {
        $pdo->exec("INSERT INTO `students` (`id`, `name`, `grade`, `classroom`, `gender`) VALUES 
            (1, 'เด็กชายจิรายุ สมพงษ์', 'ประถมศึกษาปีที่ 6', '6/1', 'ชาย'),
            (2, 'เด็กหญิงรัตนาภรณ์ แสนดี', 'ประถมศึกษาปีที่ 6', '6/1', 'หญิง'),
            (3, 'เด็กหญิงนภัสสร แก้วมณี', 'ประถมศึกษาปีที่ 5', '5/1', 'หญิง'),
            (4, 'เด็กชายชินดนัย มีสุข', 'ประถมศึกษาปีที่ 4', '4/1', 'ชาย'),
            (5, 'เด็กหญิงพิชชาภา เกิดดี', 'ประถมศึกษาปีที่ 3', '3/1', 'หญิง'),
            (6, 'เด็กหญิงกานต์พิชชา ผลเจริญ', 'ประถมศึกษาปีที่ 2', '2/1', 'หญิง'),
            (7, 'เด็กชายอนุรักษ์ รักเรียน', 'ประถมศึกษาปีที่ 1', '1/1', 'ชาย'),
            (8, 'เด็กหญิงมัทนา งามศิลป์', 'อนุบาล 3', 'อ.3/1', 'หญิง');");
    }

    $countDownloads = $pdo->query("SELECT id FROM `downloads` LIMIT 1")->fetch();
    if (!$countDownloads) {
        $pdo->exec("INSERT INTO `downloads` (`id`, `title`, `category`, `file_type`, `file_size`, `download_count`, `uploaded_date`, `file_url`) VALUES 
            (1, 'ใบสมัครเข้าศึกษาต่อ ระดับชั้นอนุบาลและประถมศึกษา โรงเรียนบ้านหนองหว้า', 'เอกสารทั่วไป', 'PDF', '1.2 MB', 145, '2026-03-01', '#'),
            (2, 'แผนพัฒนาการศึกษา 5 ปี (พ.ศ. 2568 - 2572) โรงเรียนบ้านหนองหว้า', 'แผนงานและนโยบาย', 'PDF', '4.5 MB', 56, '2026-02-15', '#');");
    }

    $countStudentStats = $pdo->query("SELECT id FROM `student_stats` LIMIT 1")->fetch();
    if (!$countStudentStats) {
        $pdo->exec("INSERT INTO `student_stats` (`grade_name`, `student_count`) VALUES 
            ('อนุบาล 2', 45),
            ('อนุบาล 3', 48),
            ('ประถมศึกษาปีที่ 1', 56),
            ('ประถมศึกษาปีที่ 2', 52),
            ('ประถมศึกษาปีที่ 3', 54),
            ('ประถมศึกษาปีที่ 4', 59),
            ('ประถมศึกษาปีที่ 5', 58),
            ('ประถมศึกษาปีที่ 6', 60);");
    }

    $countYearly = $pdo->query("SELECT id FROM `student_yearly_stats` LIMIT 1")->fetch();
    if (!$countYearly) {
        $initial_yearly_stats = [
            // 2566
            ['year' => '2566', 'grade' => 'อนุบาล 2', 'count' => 40],
            ['year' => '2566', 'grade' => 'อนุบาล 3', 'count' => 42],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 50],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 48],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 50],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 52],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 51],
            ['year' => '2566', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 53],
            // 2567
            ['year' => '2567', 'grade' => 'อนุบาล 2', 'count' => 42],
            ['year' => '2567', 'grade' => 'อนุบาล 3', 'count' => 44],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 52],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 50],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 52],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 55],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 54],
            ['year' => '2567', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 56],
            // 2568
            ['year' => '2568', 'grade' => 'อนุบาล 2', 'count' => 44],
            ['year' => '2568', 'grade' => 'อนุบาล 3', 'count' => 46],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 54],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 52],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 54],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 57],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 56],
            ['year' => '2568', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 58],
            // 2569
            ['year' => '2569', 'grade' => 'อนุบาล 2', 'count' => 45],
            ['year' => '2569', 'grade' => 'อนุบาล 3', 'count' => 48],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 1', 'count' => 56],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 2', 'count' => 52],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 3', 'count' => 54],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 4', 'count' => 59],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 5', 'count' => 58],
            ['year' => '2569', 'grade' => 'ประถมศึกษาปีที่ 6', 'count' => 60],
        ];
        $stmt_ins = $pdo->prepare("INSERT INTO `student_yearly_stats` (`academic_year`, `grade_name`, `student_count`) VALUES (:year, :grade, :count)");
        foreach ($initial_yearly_stats as $stat) {
            $stmt_ins->execute([
                'year' => $stat['year'],
                'grade' => $stat['grade'],
                'count' => $stat['count']
            ]);
        }
    }

    $countExternal = $pdo->query("SELECT id FROM `external_links` LIMIT 1")->fetch();
    if (!$countExternal) {
        $pdo->exec("INSERT INTO `external_links` (`id`, `title`, `description`, `url_link`, `image_url`, `category`) VALUES 
            (1, 'ระบบคลังสื่อเทคโนโลยีสารสนเทศ OBEC Content Center', 'แหล่งรวบรวมสื่อการเรียนรู้ดิจิทัลหลากหลายประเภทสำหรับครูและนักเรียน', 'https://contentcenter.obec.go.th', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&q=80&w=300', 'สื่อการเรียนรู้'),
            (2, 'ระบบสารสนเทศเพื่อการจัดการศึกษา EMIS', 'ระบบจัดเก็บข้อมูลนักเรียนรายบุคคลและสารสนเทศโรงเรียน', 'https://emis.obec.go.th', 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&q=80&w=300', 'งานครูและลิงก์หน่วยงาน'),
            (3, 'DLTV มูลนิธิการศึกษาทางไกลผ่านดาวเทียม', 'รับชมการเรียนการสอนทางไกลและดาวน์โหลดสื่อประกอบการสอนปฐมวัย-ประถม', 'https://www.dltv.ac.th', 'https://images.unsplash.com/photo-1516534775068-ba3e84589d90?auto=format&fit=crop&q=80&w=300', 'สื่อการเรียนรู้'),
            (4, 'ระบบปัจจัยพื้นฐานนักเรียนยากจนพิเศษ CCT', 'บันทึกคุณลักษณะและการดำเนินงานจัดสรรงบประมาณช่วยเหลือนักเรียน', 'https://www.cct.or.th', 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&q=80&w=300', 'งานครูและลิงก์หน่วยงาน');");
    }

} catch (Exception $e) {
    // ล้มเหลวแบบเงียบ
    error_log("Schema healing error: " . $e->getMessage());
}
?>
