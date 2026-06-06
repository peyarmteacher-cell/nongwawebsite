<?php
/**
 * 🛠️ ตัวอัพเดตและสร้างโครงสร้างตารางฐานข้อมูลอัตโนมัติ (Standalone Database Migration & Seeding tool)
 * โรงเรียนบ้านหนองหว้า (อัตลักษณ์ ชมพู-ขาว)
 * -------------------------------------------------------------------------
 * รันไฟล์นี้หนึ่งครั้ง (เช่น ดับเบิลคลิกหรือรันบนเบราว์เซอร์ http://localhost/db_migrate.php) 
 * เพื่อติดตั้งตาราง ยอดผู้บริหา ข่าวสาร สถิติ และบุคลากรทั้งหมดเข้ามาในระบบสด
 */

// 1. ดำเนินการเชื่องโยงการตั้งต่าฐานข้อมูลจาก db_connect.php โดยอัตโนมัติเพื่อความสะดวกและรวดเร็ว
if (file_exists('db_connect.php')) {
    require_once 'db_connect.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'schoolos_nongwa');
    define('DB_PASS', '8$p5GfJqgwlv3!Or');
    define('DB_NAME', 'schoolos_nongwa');
}

$message_log = [];
$success = true;

try {
    // พยายามเชื่อมต่อแบบระบุฐานข้อมูลโดยตรงก่อน (เนื่องจากเซิร์ฟเวอร์โรงเรียนส่วนใหญ่มักสร้างฐานข้อมูลมาให้เรียบร้อยแล้ว)
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $message_log[] = "📡 เชื่อมต่อฐานข้อมูล <strong>" . DB_NAME . "</strong> บนเซิร์ฟเวอร์โรงเรียนสำเร็จ";
    } catch (PDOException $db_err) {
        // หากเชื่อมไม่สำเร็จเนื่องจากยังไม่มีฐานข้อมูล จึงจะทำการสร้างใหม่
        $temp_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $temp_pdo = new PDO($temp_dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $message_log[] = "✅ ตรวจสอบและจัดตั้งฐานข้อมูล <strong>" . DB_NAME . "</strong> เรียบร้อยแล้ว";
        $temp_pdo = null;

        // เชื่อมต่อสู่ฐานข้อมูลใหม่อีกครั้ง
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    // โครงสร้างคำสั่งสร้างตารางหลัก
    $tables = [
        'settings' => "
            CREATE TABLE IF NOT EXISTS `settings` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'users' => "
            CREATE TABLE IF NOT EXISTS `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `name` varchar(100) NOT NULL,
              `role` varchar(30) DEFAULT 'Editor',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'news' => "
            CREATE TABLE IF NOT EXISTS `news` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'teachers' => "
            CREATE TABLE IF NOT EXISTS `teachers` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `position` varchar(150) NOT NULL,
              `level` varchar(100) NOT NULL,
              `subject_group` varchar(100) DEFAULT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `sort_order` int(11) DEFAULT '99',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'students' => "
            CREATE TABLE IF NOT EXISTS `students` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(150) NOT NULL,
              `grade` varchar(50) NOT NULL,
              `classroom` varchar(10) NOT NULL,
              `gender` varchar(10) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'downloads' => "
            CREATE TABLE IF NOT EXISTS `downloads` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `category` varchar(100) NOT NULL,
              `file_type` varchar(10) NOT NULL,
              `file_size` varchar(20) NOT NULL,
              `download_count` int(11) DEFAULT '0',
              `uploaded_date` date NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'banners' => "
            CREATE TABLE IF NOT EXISTS `banners` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `subtitle` varchar(255) NOT NULL,
              `image_url` varchar(255) NOT NULL,
              `active` tinyint(4) DEFAULT '1',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'student_stats' => "
            CREATE TABLE IF NOT EXISTS `student_stats` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `grade_name` varchar(50) NOT NULL UNIQUE,
              `student_count` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        'external_links' => "
            CREATE TABLE IF NOT EXISTS `external_links` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `url_link` varchar(255) NOT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `category` varchar(100) DEFAULT 'สื่อการเรียนรู้',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
    ];

    // รันกระบวนการสร้างตาราง
    foreach ($tables as $name => $query) {
        $pdo->exec($query);
        $message_log[] = "⚙️ สร้างตารางหรือมีอยู่แล้ว: <strong>`{$name}`</strong>";
    }

    // มีตารางแล้ว อัปเดตคอลัมน์พิเศษเพิ่มเติม (Column Auto-Healing)
    $column_migrations = [
        'settings' => [
            'school_motto' => "ALTER TABLE `settings` ADD COLUMN `school_motto` varchar(255) DEFAULT 'ชมพู-ขาว ก้าวไกลวิชาการ' AFTER `school_theme_color`",
            'school_logo' => "ALTER TABLE `settings` ADD COLUMN `school_logo` varchar(255) DEFAULT NULL AFTER `school_motto`",
            'banner_bg_image' => "ALTER TABLE `settings` ADD COLUMN `banner_bg_image` varchar(255) DEFAULT NULL AFTER `school_logo`",
            'banner_right_image' => "ALTER TABLE `settings` ADD COLUMN `banner_right_image` varchar(255) DEFAULT NULL AFTER `banner_bg_image`"
        ],
        'news' => [
            'sticky_flag' => "ALTER TABLE `news` ADD COLUMN `sticky_flag` tinyint(1) DEFAULT '0' AFTER `views`"
        ],
        'downloads' => [
            'file_url' => "ALTER TABLE `downloads` ADD COLUMN `file_url` varchar(255) DEFAULT NULL AFTER `file_size`"
        ],
        'teachers' => [
            'pa_link_url' => "ALTER TABLE `teachers` ADD COLUMN `pa_link_url` varchar(255) DEFAULT NULL AFTER `image_url`",
            'portfolio_url' => "ALTER TABLE `teachers` ADD COLUMN `portfolio_url` varchar(255) DEFAULT NULL AFTER `pa_link_url`"
        ]
    ];

    foreach ($column_migrations as $tableName => $cols) {
        foreach ($cols as $colName => $alterSql) {
            $checkQuery = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '$colName'");
            if ($checkQuery->rowCount() == 0) {
                $pdo->exec($alterSql);
                $message_log[] = "🛠️ อัพเดตตาราง `{$tableName}` -> เพิ่มคอลัมน์ใหม่ <strong>`{$colName}`</strong> เติมเสร็จสรรพ";
            }
        }
    }

    // ใส่ข้อมูลแรกเริ่ม (Seeding)
    $countSettings = $pdo->query("SELECT id FROM `settings` LIMIT 1")->fetch();
    if (!$countSettings) {
        $pdo->exec("INSERT INTO `settings` 
            (`id`, `school_name`, `short_name`, `address`, `phone`, `email`, `jurisdiction`, `levels`, `director_name`, `director_title`, `director_image`, `visitor_count`, `school_theme_color`, `school_motto`, `youtube_intro_url`) 
            VALUES 
            (1, 'โรงเรียนบ้านหนองหว้า', 'ร.ร.บ้านหนองหว้า', 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210', '044-641123', 'bannongwaschool@gmail.com', 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3', 'ระดับปฐมวัย (อนุบาล 2-3) ถึงระดับชั้นประถมศึกษาปีที่ 6', 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 15422, 'pink-white', 'ชมพู-ขาว ก้าวไกลวิชาการ', 'https://www.youtube.com/embed/gCOk8X63Rpk');");
        $message_log[] = "✨ ข้อมูลจำลองพิกัดตั้งค่าโรงเรียน (Settings) โหลดสำเร็จ";
    }

    $countUsers = $pdo->query("SELECT id FROM `users` LIMIT 1")->fetch();
    if (!$countUsers) {
        // แอดมินหลัก รหัสใช้งานเริ่มต้น: username คือ admin, password คือ admin123 (Bcrypt)
        $hashed_pwd = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`) VALUES 
            (1, 'admin', '{$hashed_pwd}', 'ผู้ดูแลระบบ โรงเรียนบ้านหนองหว้า', 'Administrator');");
        $message_log[] = "🔐 บัญชีแอดมินแรกเริ่มสำเร็จใช้งาน (User: <strong>admin</strong> | Pass: <strong>admin123</strong>) ติดตั้งแล้ว";
    }

    $countBanners = $pdo->query("SELECT id FROM `banners` LIMIT 1")->fetch();
    if (!$countBanners) {
        $pdo->exec("INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_url`, `active`) VALUES 
            (1, 'ยินดีต้อนรับสู่ โรงเรียนบ้านหนองหว้า', 'แหล่งวิทยาการ กีฬาเด่น เน้นคุณธรรม สัมพันธ์ชุมชน', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&q=80&w=1200', 1),
            (2, 'เปิดรับสมัครเรียน ปีการศึกษา 2569', 'ตั้งแต่ชั้น อนุบาล 1 ถึง ชั้นประถมศึกษาปีที่ 6', 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&q=80&w=1200', 1);");
        $message_log[] = "🖼️ แบนเนอร์ภาพสะกดสะเพร่าโรงเรียนหน้าหลัก (Banners) ติดตั้งแล้ว";
    }

    $countNews = $pdo->query("SELECT id FROM `news` LIMIT 1")->fetch();
    if (!$countNews) {
        $pdo->exec("INSERT INTO `news` (`id`, `title`, `category`, `content`, `summary`, `image_url`, `views`, `date`, `sticky_flag`) VALUES 
            (1, 'ประกาศเปิดเรียนภาคเรียนที่ 1 ปีการศึกษา 2569 อย่างเป็นทางการ', 'ประชาสัมพันธ์ทั่วไป', 'โรงเรียนบ้านหนองหว้า ขอประกาศกำหนดการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 ขอความกรุณาผู้ปกครองเตรียมความพร้อมของนักเรียนในเรื่องของเครื่องแบบ อุปกรณ์การเรียน และสุขอนามัย ทางโรงเรียนได้ทำความสะอาดฉีดพ่นฆ่าเชื้อและเตรียมอาคารสถานที่เรียบร้อยแล้ว', 'ประกาศอย่างเป็นทางการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 พร้อมทั้งเตรียมความสะอาดของอาคารสถานที่และการดูแลความปลอดภัยในทุกด้าน', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600', 312, '2026-05-10', 1),
            (2, 'กิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 \"น้อมจิตวันทา บูชาพระคุณครู\"', 'ข่าวกิจกรรม', 'โรงเรียนบ้านหนองหว้า นำโดยคณะผู้บริหาร คณะครู และสภานักเรียน ได้ร่วมใจจัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 ณ หอประชุมโรงเรียน เพื่อร่วมส่งเสริมวัฒนธรรมอันดีงามและความกตัญญูกตเวทิตาต่อครูผู้ประสิทธิ์ประสาทวิชา โดยมีการประกวดพานไหว้ครูประเภทสวยงามและประเภทความคิดสร้างสรรค์ บรรยากาศเป็นไปด้วยความอบอุ่นและเป็นระเบียบเรียบร้อย', 'โรงเรียนบ้านหนองหว้า จัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 ณ หอประชุมโรงเรียน เพื่อแสดงความกตัญญูกตเวทิตา พร้อมทั้งประกวดพานไหว้ครูอันสวยงามเชิงสร้างสรรค์', 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=600', 185, '2026-06-02', 0),
            (3, 'การประชุมผู้ปกครองภาคทฤษฎีและแนวทางการเรียนร่วม ภาคเรียนที่ 1/2569', 'ประชุมและวิชาการ', 'เมื่อวันเสาร์ที่ผ่านมา ทางโรงเรียนจัดประชุมผู้ปกครองภาคเรียนที่ 1 ปีการศึกษา 2569 เพื่อชี้แจงนโยบายสิทธิประโยชน์เรียนฟรี 15 ปี เผยแพร่มาตรการความปลอดภัย และการร่วมมือกันพัฒนาทักษะอ่านออกเขียนได้ของนักเรียน โดยการประชุมประสบความสำเร็จและได้รับความร่วมมืออย่างดียิ่งจากผู้ปกครองทุกระดับชั้น', 'จัดประชุมผู้ปกครองภาคเรียนที่ 1/2569 เพื่อสร้างความเข้าใจต่อนโยบายโรงเรียน สิทธิประโยชน์เรียนฟรี และแนวทางประสานงานเพื่อช่วยเหลือดูแลพฤติกรรมการเรียนของนักเรียน', 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&q=80&w=600', 247, '2026-05-18', 0);");
        $message_log[] = "📰 ข่าวสตรีมแจ้งด่วนและประชาสัมพันธ์ (News) จัดจองพื้นที่แล้ว";
    }

    $countTeachers = $pdo->query("SELECT id FROM `teachers` LIMIT 1")->fetch();
    if (!$countTeachers) {
        $pdo->exec("INSERT INTO `teachers` (`id`, `name`, `position`, `level`, `subject_group`, `image_url`, `sort_order`) VALUES 
            (1, 'นายอำนวย ยอดครูใหญ่', 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า', 'ผู้อำนวยการโรงเรียน (คศ.3)', 'ผู้บริหาร', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300', 1),
            (2, 'นางสมศรี ปัญญาไว', 'ครูวิชาการระดับประถม / ครูประจำชั้นประถมศึกษาปีที่ 6', 'ครูชำนาญการพิเศษ (คศ.3)', 'วิชาการคณิตศาสตร์', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300', 2),
            (3, 'นายวิชาญ อักษรศิลป์', 'ครูพลศึกษาและไอที / ครูประจำชั้นประถมศึกษาปีที่ 5', 'ครูชำนาญการ (คศ.2)', 'สุขศึกษาและพลศึกษา', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=300', 3),
            (4, 'นางสาวดวงตา บุพผา', 'ครูภาษาไทยระดับต้น / ครูประจำชั้นประถมศึกษาปีที่ 1', 'ครูผู้ช่วย', 'ภาษาไทย', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=300', 4),
            (5, 'นางกานดา ใจซื่อ', 'ครูปฐมวัย / ครูประจำชั้นอนุบาล 3', 'ครูชำนาญการพิเศษ (คศ.3)', 'ระดับปฐมวัย', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=300', 5);");
        $message_log[] = "🧑‍🏫 ทำเนียบข้าราชการครูโรงเรียน (Teachers) เชื่อมต่อสมบรูณ์";
    }

    $countStudents = $pdo->query("SELECT id FROM `students` LIMIT 1")->fetch();
    if (!$countStudents) {
        $pdo->exec("INSERT INTO `students` (`id`, `name`, `grade`, `classroom`, `gender`) VALUES 
            (1, 'เด็กชายจิรายุ สมพงษ์', 'ประถมศึกษาปีที่ 6', '6/1', 'ชาย'),
            (2, 'เด็กหญิงรัตนาภรณ์ แสนดี', 'ประถมศึกษาปีที่ 6', '6/1', 'หญิง'),
            (3, 'เด็กชายวีรยุทธ สุขใจ', 'ประถมศึกษาปีที่ 5', '5/1', 'ชาย'),
            (4, 'เด็กหญิงกนกวรรณ เพียรธรรม', 'ประถมศึกษาปีที่ 1', '1/1', 'หญิง'),
            (5, 'เด็กชายอานนท์ บุรีรัมย์', 'อนุบาล 3', 'อ.3/1', 'ชาย');");
        $message_log[] = "📊 ยอดนักเรียนและสัดส่วนนักเรียน (Students Table) จัดตั้งพร้อม";
    }

    $countDownloads = $pdo->query("SELECT id FROM `downloads` LIMIT 1")->fetch();
    if (!$countDownloads) {
        $pdo->exec("INSERT INTO `downloads` (`id`, `title`, `category`, `file_type`, `file_size`, `download_count`, `uploaded_date`, `file_url`) VALUES 
            (1, 'ใบสมัครเข้าศึกษาต่อ ระดับชั้นอนุบาลและประถมศึกษา โรงเรียนบ้านหนองหว้า', 'เอกสารทั่วไป', 'PDF', '1.2 MB', 145, '2026-03-01', '#'),
            (2, 'แผนพัฒนาการศึกษา 5 ปี (พ.ศ. 2568 - 2572) โรงเรียนบ้านหนองหว้า', 'แผนงานและนโยบาย', 'PDF', '4.5 MB', 56, '2026-02-15', '#'),
            (3, 'รายงานการประเมินตนเองของสถานศึกษา SAR ปีการศึกษา 2568', 'ประกันคุณภาพ', 'PDF', '8.1 MB', 92, '2026-04-10', '#'),
            (4, 'ข้อตกลงในการพัฒนางาน PA สำหรับครูสายการสอน (ตัวอย่างไฟล์แก้ไขได้)', 'เอกสารครู', 'WORD', '520 KB', 231, '2026-05-02', '#');");
        $message_log[] = "📋 คลังดาวน์โหลดจัดตั้งคลังเอกสารโรงเรียน (Downloads Archive) โหลดแล้ว";
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
        $message_log[] = "📊 ยอดรายงานสถิติแต่ละชั้นปี (Student Stats) จัดทำตารางแล้ว";
    }

    $countExternalLinks = $pdo->query("SELECT id FROM `external_links` LIMIT 1")->fetch();
    if (!$countExternalLinks) {
        $pdo->exec("INSERT INTO `external_links` (`id`, `title`, `description`, `url_link`, `image_url`, `category`) VALUES 
            (1, 'ระบบคลังสื่อเทคโนโลยีสารสนเทศ OBEC Content Center', 'แหล่งรวบรวมสื่อการเรียนรู้ดิจิทัลหลากหลายประเภทสำหรับครูและนักเรียน', 'https://contentcenter.obec.go.th', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&q=80&w=300', 'สื่อการเรียนรู้'),
            (2, 'ระบบสารสนเทศเพื่อการจัดการศึกษา EMIS', 'ระบบจัดเก็บข้อมูลนักเรียนรายบุคคลและสารสนเทศโรงเรียน', 'https://emis.obec.go.th', 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&q=80&w=300', 'งานครูและลิงก์หน่วยงาน'),
            (3, 'DLTV มูลนิธิการศึกษาทางไกลผ่านดาวเทียม', 'รับชมการเรียนการสอนทางไกลและดาวน์โหลดสื่อประกอบการสอนปฐมวัย-ประถม', 'https://www.dltv.ac.th', 'https://images.unsplash.com/photo-1516534775068-ba3e84589d90?auto=format&fit=crop&q=80&w=300', 'สื่อการเรียนรู้'),
            (4, 'ระบบปัจจัยพื้นฐานนักเรียนยากจนพิเศษ CCT', 'บันทึกคุณลักษณะและการดำเนินงานจัดสรรงบประมาณช่วยเหลือนักเรียน', 'https://www.cct.or.th', 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&q=80&w=300', 'งานครูและลิงก์หน่วยงาน');");
        $message_log[] = "🔗 ลิงก์สื่อภายนอกและงานครูที่สำคัญของโรงเรียน (External Links Hub) ติดตั้งเสร็จสมบูรณ์";
    }

} catch (PDOException $e) {
    $success = false;
    $message_log[] = "❌ เกิดข้อผิดพลาดของฐานข้อมูล: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Migration Tools - โรงเรียนบ้านหนองหว้า</title>
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-pink-50 min-h-screen py-10 flex flex-col items-center justify-center">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl border border-pink-100 overflow-hidden text-gray-800">
        <!-- Header -->
        <div class="bg-gradient-to-r from-pink-500 to-rose-400 p-8 text-white text-center">
            <h1 class="text-2xl font-bold mb-2">🏫 โรงเรียนบ้านหนองหว้า ระบบอัพเดตตารางฐานข้อมูล</h1>
            <p class="text-sm opacity-90">Database Migration & Automatic Setup Module (Pink-White Theme)</p>
        </div>

        <!-- Contents -->
        <div class="p-8">
            <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2 border-pink-100">💻 รายงานประวัติพัฒนาโครงสร้างระบบ</h2>
            
            <div class="space-y-3 max-h-96 overflow-y-auto mb-6 bg-gray-50 p-4 rounded-xl border border-gray-100 font-mono text-sm leading-relaxed">
                <?php foreach ($message_log as $log): ?>
                    <p class="text-gray-700"><?php echo $log; ?></p>
                <?php endforeach; ?>
            </div>

            <!-- Operational Guide Result -->
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-xl flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-bold">เชื่อมต่อและสร้างโครงสร้างตารางจำลองสำเร็จ!</p>
                        <p class="text-xs opacity-75">พร้อมเข้าใช้งานแอดมินด้วยผู้ใช้ admin และรหัสคั่นแอดมิน admin123 ได้ทันที</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl flex items-center gap-3">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="font-bold">เกิดข้อผิดพลาดในการเชื่อมบัญชี!</p>
                        <p class="text-xs opacity-75">กรุณาตั้งค่า DB_USER, DB_PASS ในไฟล์ db_migrate.php และ db_connect.php ให้ตรงกับ Server จริง</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Links -->
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="index.php" class="flex-1 bg-pink-500 hover:bg-pink-600 text-white font-medium text-center py-3 px-4 rounded-xl transition duration-200 shadow-lg shadow-pink-500/10">
                    ไปยังหน้าแรกเว็บไซต์โรงเรียน
                </a>
                <a href="login.php" class="flex-1 bg-white hover:bg-gray-50 border border-pink-200 text-pink-600 font-medium text-center py-3 px-4 rounded-xl transition duration-200">
                    เข้าสู่ระบบแผงควบคุมหลังบ้าน
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 p-4 border-t border-gray-100 text-center text-xs text-gray-500">
            ระบบจัดสรรโดยอัตโนมัติ โรงเรียนบ้านหนองหว้า @บุรีรัมย์ เขต 3
        </div>
    </div>
</body>
</html>
