<?php
/**
 * หน้าแรก (Homepage) - เว็บไซต์ทางการโรงเรียนบ้านหนองหว้า
 * พัฒนาในธีม "ชมพู-ขาว" (Pink-White) เรียบหรู อ่อนโยน ทันสมัย
 * ดึงข้อมูลสดจากระบบฐานข้อมูล MySQL และรองรับการทำงานอัตโนมัติ 100%
 */

require_once 'db_connect.php';

// นำเข้าข้อมูลการตั้งค่าโรงเรียน
try {
    $settingsStmt = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1");
    $settings = $settingsStmt->fetch();
    
    // บันทึกจำนวนผู้เข้าชม (+1 ยอดเข้าชมหน้าแรก)
    $pdo->exec("UPDATE `settings` SET `visitor_count` = `visitor_count` + 1 WHERE `id` = 1");
    $settings['visitor_count']++; // ปรับปรุงค่าในเมมโมรี่เพื่อนำไปแสดงทันที
} catch (Exception $e) {
    // กรณีฉุกเฉินถ้ายังไม่เคยรันฐานข้อมูล
    $settings = [
        'school_name' => 'โรงเรียนบ้านหนองหว้า',
        'short_name' => 'ร.ร.บ้านหนองหว้า',
        'school_motto' => 'ชมพู-ขาว ก้าวไกลวิชาการ',
        'address' => 'หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210',
        'phone' => '044-641123',
        'email' => 'bannongwaschool@gmail.com',
        'jurisdiction' => 'สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3',
        'levels' => 'ระดับปฐมวัย (อนุบาล 2-3) ถึงระดับชั้นประถมศึกษาปีที่ 6',
        'director_name' => 'นายอำนวย ยอดครูใหญ่',
        'director_title' => 'ผู้อำนวยการโรงเรียนบ้านหนองหว้า',
        'director_image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300',
        'visitor_count' => 15423,
        'school_theme_color' => 'pink-white',
        'youtube_intro_url' => 'https://www.youtube.com/embed/gCOk8X63Rpk'
    ];
}

// 1. ดึงแบนเนอร์ประชาสัมพันธ์
$banners = [];
try {
    $banners = $pdo->query("SELECT * FROM `banners` WHERE `active` = 1 ORDER BY `id` ASC")->fetchAll();
} catch (Exception $e) {}

// 2. ดึงหมวดหมู่ข่าวสารที่มีข้อมูลอยู่
$news_categories = ['ประชาสัมพันธ์ทั่วไป', 'ข่าวกิจกรรม', 'ประชุมและวิชาการ', 'ผลงานครูและนักเรียน'];

// หมวดหมู่ข่าวสารที่เลือก
$selected_category = isset($_GET['cat']) ? cleanInput($_GET['cat']) : 'ทั้งหมด';

// โหลดข่าวประชาสัมพันธ์ตามหมวดหมู่ พร้อมจำกัดแค่ 2 แถว (หน้าละ 6 รายการ สำหรับจอคอมเกริด 3 คอลัมน์)
try {
    if ($selected_category === 'ทั้งหมด' || !in_array($selected_category, $news_categories)) {
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM `news`");
        $total_news_matching = intval($count_stmt->fetchColumn());
    } else {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `category` = :cat");
        $count_stmt->execute(['cat' => $selected_category]);
        $total_news_matching = intval($count_stmt->fetchColumn());
    }

    $news_per_page = 6; // 2 แถว (แถวละ 3 คอลัมน์)
    $total_news_pages = ceil($total_news_matching / $news_per_page);
    $news_page = isset($_GET['npage']) ? max(1, intval($_GET['npage'])) : 1;
    if ($news_page > $total_news_pages && $total_news_pages > 0) {
        $news_page = $total_news_pages;
    }
    $offset = ($news_page - 1) * $news_per_page;

    if ($selected_category === 'ทั้งหมด' || !in_array($selected_category, $news_categories)) {
        $news_stmt = $pdo->prepare("SELECT * FROM `news` ORDER BY `sticky_flag` DESC, `date` DESC, `id` DESC LIMIT :limit OFFSET :offset");
    } else {
        $news_stmt = $pdo->prepare("SELECT * FROM `news` WHERE `category` = :cat ORDER BY `sticky_flag` DESC, `date` DESC, `id` DESC LIMIT :limit OFFSET :offset");
        $news_stmt->bindValue(':cat', $selected_category, PDO::PARAM_STR);
    }
    $news_stmt->bindValue(':limit', $news_per_page, PDO::PARAM_INT);
    $news_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $news_stmt->execute();
    $news_list = $news_stmt->fetchAll();
} catch (Exception $e) {
    $news_list = [];
    $total_news_matching = 0;
    $total_news_pages = 1;
    $news_page = 1;
}

// 3. ดึงทำเนียบข้าราชการครู
$teacher_menu_filters = [
    'ทั้งหมด' => 'ทั้งหมด',
    'ผู้บริหาร' => 'ผู้บริหาร',
    'บุคลากร' => 'บุคลากร'
];
$teacher_label_map = [
    'ผู้บริหาร' => 'ผู้บริหาร',
    'กลุ่มสาระการเรียนรู้วิทยาศาสตร์' => 'วิทยาศาสตร์',
    'กลุ่มสาระการเรียนรู้คณิตศาสตร์' => 'คณิตศาสตร์',
    'กลุ่มสาระการเรียนรู้ศิลปะ' => 'ศิลปะ',
    'กลุ่มสาระการเรียนรู้ภาษาไทย' => 'ภาษาไทย',
    'กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ' => 'ภาษาต่างประเทศ',
    'กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนา และวัฒนธรรม' => 'สังคมศึกษาฯ',
    'กลุ่มสาระการเรียนรู้การงานอาชีพและเทคโนโลยี' => 'การงานอาชีพฯ',
    'กลุ่มสาระการเรียนรู้สุขศึกษาและพลศึกษา' => 'สุขศึกษาฯ',
    'ปฐมวัย' => 'ปฐมวัย',
    'งานสอนทั่วไป' => 'งานสอนทั่วไป'
];
$selected_group = isset($_GET['group']) ? cleanInput($_GET['group']) : 'ทั้งหมด';
if (!array_key_exists($selected_group, $teacher_menu_filters)) {
    $selected_group = 'ทั้งหมด';
}

try {
    // ดึงจำนวนครูทั้งหมดในระบบก่อนเพื่อใช้ใน STAT PACK ให้แม่นยำครบถ้วนจริง
    $total_teachers_stmt = $pdo->query("SELECT COUNT(*) FROM `teachers`");
    $total_teachers_count = intval($total_teachers_stmt->fetchColumn());
} catch (Exception $e) {
    $total_teachers_count = 0;
}

try {
    if ($selected_group === 'ทั้งหมด') {
        $teachers_stmt = $pdo->query("SELECT * FROM `teachers` ORDER BY `sort_order` ASC, `id` ASC");
    } elseif ($selected_group === 'ผู้บริหาร') {
        $teachers_stmt = $pdo->query("SELECT * FROM `teachers` WHERE `subject_group` = 'ผู้บริหาร' ORDER BY `sort_order` ASC, `id` ASC");
    } else { // 'บุคลากร' - แสดงภาพคุณครูและบุคลากรทั้งหมดที่ไม่ใช่ผู้บริหาร
        $teachers_stmt = $pdo->query("SELECT * FROM `teachers` WHERE `subject_group` != 'ผู้บริหาร' ORDER BY `sort_order` ASC, `id` ASC");
    }
    $teachers_list = $teachers_stmt->fetchAll();
} catch (Exception $e) {
    $teachers_list = [];
}

// 4. ดึงสถิตินักเรียนรายปี/รายห้อง และเอกสารดาวน์โหลด
$active_current_year = $settings['current_academic_year'] ?? '2569';
$downloads_list = [];
$students_list = [];
$external_links_list = [];
$student_stats = [];
$yearly_comparison = [];

try {
    $downloads_list = $pdo->query("SELECT * FROM `downloads` ORDER BY `id` DESC")->fetchAll();
} catch (Exception $e) {
    $downloads_list = [];
}

try {
    $students_list = $pdo->query("SELECT * FROM `students` ORDER BY `id` ASC")->fetchAll();
} catch (Exception $e) {
    $students_list = [];
}

try {
    $external_links_list = $pdo->query("SELECT * FROM `external_links` ORDER BY `id` ASC")->fetchAll();
} catch (Exception $e) {
    $external_links_list = [];
}

try {
    // ดึงสถิตินักเรียนของปีการศึกษาปัจจุบันที่กำหนดในระบบ
    $yearly_stmt = $pdo->prepare("SELECT * FROM `student_yearly_stats` WHERE `academic_year` = :year ORDER BY `id` ASC");
    $yearly_stmt->execute(['year' => $active_current_year]);
    $student_stats = $yearly_stmt->fetchAll();
    
    // หากไม่พบข้อมูลสถิติปีการศึกษาปัจจุบันล่าสุด ให้ทำการดึงจากตารางเดิม student_stats เป็นเซฟฟอลล์แบ็ค
    if (empty($student_stats)) {
        $student_stats = $pdo->query("SELECT * FROM `student_stats` ORDER BY `id` ASC")->fetchAll();
    }
} catch (Exception $e) {
    try {
        $student_stats = $pdo->query("SELECT * FROM `student_stats` ORDER BY `id` ASC")->fetchAll();
    } catch (Exception $ex) {
        $student_stats = [];
    }
}

try {
    // ดึงสรุปยอดรวมประจำแต่ละปีการศึกษาเพื่อนำส่งทำแผนภูมิเปรียบเทียบนักเรียนรายปีการศึกษา
    $yearly_comp_stmt = $pdo->query("SELECT `academic_year`, SUM(`student_count`) as total_count FROM `student_yearly_stats` GROUP BY `academic_year` ORDER BY `academic_year` ASC");
    $yearly_comparison = $yearly_comp_stmt->fetchAll();
} catch (Exception $e) {
    $yearly_comparison = [];
}

// หากตารางไม่มีข้อมูลหรือเกิดข้อผิดพลาด ให้กำหนดค่าเริ่มต้น
if (empty($student_stats)) {
    $student_stats = [
        ['grade_name' => 'อนุบาล 2', 'student_count' => 45],
        ['grade_name' => 'อนุบาล 3', 'student_count' => 48],
        ['grade_name' => 'ประถมศึกษาปีที่ 1', 'student_count' => 56],
        ['grade_name' => 'ประถมศึกษาปีที่ 2', 'student_count' => 52],
        ['grade_name' => 'ประถมศึกษาปีที่ 3', 'student_count' => 54],
        ['grade_name' => 'ประถมศึกษาปีที่ 4', 'student_count' => 59],
        ['grade_name' => 'ประถมศึกษาปีที่ 5', 'student_count' => 58],
        ['grade_name' => 'ประถมศึกษาปีที่ 6', 'student_count' => 60]
    ];
}

// คำนวณยอดรวมนักเรียนทั้งหมดเพื่อให้ระบบแสดงค่าสถิติสัมพันธ์ตรงจริง
$total_students_count = 0;
foreach ($student_stats as $st) {
    $total_students_count += intval($st['student_count']);
}
$boy_count_computed = round($total_students_count * 0.49);
$girl_count_computed = $total_students_count - $boy_count_computed;

// นับเพศนักเรียน
$boy_count = 0;
$girl_count = 0;
$grade_stats = [];
foreach ($students_list as $std) {
    if ($std['gender'] === 'ชาย') $boy_count++;
    else if ($std['gender'] === 'หญิง') $girl_count++;
    
    $g = $std['grade'];
    if (!isset($grade_stats[$g])) {
        $grade_stats[$g] = 0;
    }
    $grade_stats[$g]++;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $settings['school_name']; ?> | ยินดีต้อนรับสู่รั้วชมพู-ขาว</title>
    <!-- โหลดฟอนต์ภาษาไทยยอดนิยม Kanit & Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800;900&family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- โหลด Tailwind CSS ผ่าน Play CDN เพื่อการประยุกต์ใช้งานทันที ไม่ต้องมี Node build บน host จริง -->
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
                                DEFAULT: '#ec4899', /* ชมพูประจำโรงเรียน */
                                dark: '#be185d',
                            },
                            white: {
                                DEFAULT: '#ffffff', /* ขาวประจำโรงเรียน */
                                soft: '#fff1f2',
                            }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-headline {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen">

    <!-- 1. TOP HEADER BAR (ข้อมูลการติดต่อย่อระดับบนสุด) -->
    <div class="bg-gradient-to-r from-school-pink-dark via-school-pink to-pink-500 text-white text-xs py-2 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    สังกัด: <?php echo htmlspecialchars($settings['jurisdiction']); ?>
                </span>
            </div>
            <div class="flex items-center gap-4 font-semibold">
                <span>โทรศัพท์: <?php echo htmlspecialchars($settings['phone']); ?></span>
                <span>|</span>
                <span>อีเมล: <?php echo htmlspecialchars($settings['email']); ?></span>
            </div>
        </div>
    </div>

    <!-- 2. MAIN NAVIGATION HEADER -->
    <header class="bg-white/90 backdrop-blur sticky top-0 z-40 border-b border-pink-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <?php if (!empty($settings['school_logo'])): ?>
                    <img id="school_logo_main" src="<?php echo htmlspecialchars(fixGoogleDriveUrl($settings['school_logo'])); ?>" alt="School Logo" class="h-12 w-12 object-contain rounded-full shadow-md group-hover:scale-105 transition-all" referrerPolicy="no-referrer">
                <?php else: ?>
                    <div id="school_logo_placeholder" class="h-12 w-12 rounded-full bg-gradient-to-tr from-school-pink to-pink-300 flex items-center justify-center text-white font-black text-xl shadow-md group-hover:scale-105 transition-all">
                        นห
                    </div>
                <?php endif; ?>
                <div>
                     <h1 class="font-heading font-extrabold text-base sm:text-lg text-slate-900 leading-none group-hover:text-school-pink transition-colors">
                        <?php echo htmlspecialchars($settings['school_name']); ?>
                    </h1>
                    <p class="text-[9px] text-slate-500 font-medium tracking-wide uppercase mt-1">
                        สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)
                    </p>
                </div>
            </a>

            <!--เมนูหลัก-->
            <nav class="hidden lg:flex items-center gap-5 text-xs font-bold text-slate-700">
                <a href="#news" class="hover:text-school-pink transition">ข่าวประชาสัมพันธ์</a>
                <a href="#teachers" class="hover:text-school-pink transition">ทำเนียบครู</a>
                <a href="pa_reports.php" class="text-slate-800 hover:text-school-pink transition flex items-center gap-1 bg-pink-100/50 hover:bg-pink-100/80 px-3 py-1.5 rounded-lg border border-pink-200/50">
                    <span class="w-1.5 h-1.5 bg-school-pink rounded-full animate-ping"></span>
                    บันทึกผลการปฏิบัติงาน (PA)
                </a>
                <a href="#school-tools" class="hover:text-school-pink transition">สื่อและระบบงานครู</a>
                <a href="#stats" class="hover:text-school-pink transition">ข้อมูลสถิติ</a>
                <a href="#documents" class="hover:text-school-pink transition">คลังเอกสาร</a>
                <a href="login.php" class="bg-school-pink hover:bg-school-pink-dark text-white text-[11px] px-3.5 py-2 rounded-xl shadow-md font-bold transition flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                    ระบบแอดมิน
                </a>
            </nav>
        </div>
    </header>

    <!-- 3. HERO BANNER AREA -->
    <?php
    $banner_bg = !empty($settings['banner_bg_image']) ? $settings['banner_bg_image'] : (!empty($banners) ? $banners[0]['image_url'] : 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&q=80&w=1200');
    $banner_right = !empty($settings['banner_right_image']) ? $settings['banner_right_image'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&q=80&w=600';
    ?>
    <section class="relative bg-slate-950 overflow-hidden min-h-[500px] lg:min-h-[580px] flex items-center py-12 md:py-20 border-b border-pink-500/10">
        <!-- 1. ภาพพื้นหลังแบนเนอร์ฝั่งขวา (โครงสร้างอาคารโรงเรียน) -->
        <div class="absolute inset-y-0 right-0 w-full lg:w-1/2 z-0 pointer-events-none">
            <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($banner_bg)); ?>" alt="Banner Background Logo" class="w-full h-full object-cover opacity-15 lg:opacity-30 filter brightness-75 contrast-100" referrerPolicy="no-referrer">
            <!-- ไล่ระดับสีคู่ตรงข้ามเข้าหากึ่งกลางเพื่อให้ข้อความเด่นชัดกระชับขึ้น -->
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent pointer-events-none"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent pointer-events-none lg:hidden"></div>
        </div>

        <!-- 2. ภาพแบนเนอร์ไฮไลต์ฝั่งซ้าย (กลุ่มบุคลากร) คลายตัวเต็มความสูงกลมกลืนสะกดตา -->
        <div class="absolute inset-y-0 left-0 w-full lg:w-3/5 z-0 pointer-events-none overflow-hidden">
            <!-- รูปภาพสะท้อนแสงสว่างสดใส (ตามที่ลูกค้าต้องการสว่างกว่าพื้นหลังฝั่งขวา) -->
            <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($banner_right)); ?>" alt="Banner Highlight Background" class="w-full h-full object-cover lg:object-contain object-left-bottom opacity-75 lg:opacity-90 filter brightness-125 contrast-[1.05] saturate-[1.08]" referrerPolicy="no-referrer">
            
            <!-- เกรเดียนต์ลบขอบแข็งของภาพ (รวมถึงพื้นหลังสีดำของภาพต้นฉบับ) ด้วยโทนสี Slate-950 แท้ของเว็บไซต์เพื่อความนุ่มนวลสูงสุด -->
            <!-- ละลายขอบขวาเข้าหากึ่งกลาง -->
            <div class="absolute inset-y-0 right-0 w-1/3 lg:w-1/2 bg-gradient-to-r from-transparent via-slate-950/50 to-slate-950 pointer-events-none"></div>
            <!-- ละลายกลางภาพออกสู่ด้านขวาเข้มขึ้นเพื่อกันเสียงสะท้อน -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-slate-950/20 to-slate-950 pointer-events-none"></div>
            <!-- ละลายขอบล่างป้องขอบตัดตรง -->
            <div class="absolute inset-x-0 bottom-0 h-1/4 bg-gradient-to-t from-slate-950 to-transparent pointer-events-none"></div>
            <!-- ละลายขอบบนเชื่อมส่วนหัว -->
            <div class="absolute inset-x-0 top-0 h-1/5 bg-gradient-to-b from-slate-950 to-transparent pointer-events-none"></div>
            <!-- ละลายขอบซ้ายสุด -->
            <div class="absolute inset-y-0 left-0 w-1/12 bg-gradient-to-l from-transparent to-slate-950 pointer-events-none"></div>
        </div>

        <!-- แสงเรืองออร่าชมพูอมส้มหวานอุ่นเพิ่มมิมิติด้านล่างภาพเพื่อความพรีเมียม -->
        <div class="absolute left-1/4 top-1/2 -translate-y-1/2 -translate-x-1/2 w-[380px] h-[380px] bg-school-pink/10 rounded-full blur-[90px] pointer-events-none z-0"></div>
        
        <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full text-white">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                
                <!-- สเปเซอร์ฝั่งซ้าย: ขยับที่ว่างลดลงเพื่อให้ตัวอักษรขยับเข้าใกล้รูปภาพกลางแบนเนอร์ทางซ้ายมือได้อย่างกระชับสวยงามยิ่งขึ้น -->
                <div class="hidden lg:block lg:col-span-4 h-2"></div>
                
                <!-- ฝั่งขวา: ข้อความและปุ่มคำสั่งหลัก ขยับเข้ามาใกล้รูปภาพอย่างมีสไตล์พรีเมียมระดับสากล -->
                <div class="space-y-6 lg:col-span-8 flex flex-col items-center lg:items-start text-center lg:text-left transition-all duration-500">
                    <!-- เสริมเลเยอร์กระจกโปร่งแสง Glassmorphism ชนิดบางเบาเพื่อความประณีตระดับมืออาชีพ และรับประกันความอ่านง่าย 100% -->
                    <div class="lg:bg-slate-950/40 lg:backdrop-blur-[2px] lg:border lg:border-white/5 lg:p-6 lg:rounded-3xl lg:shadow-xl lg:-ml-8 space-y-6 w-full max-w-2xl">
                        <span class="inline-block bg-school-pink/15 text-pink-300 border border-school-pink/30 text-xs sm:text-sm md:text-base tracking-wider uppercase font-black px-5 py-2.5 rounded-full shadow-inner">
                            <?php echo htmlspecialchars($settings['banner_title'] ?? 'ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ'); ?>
                        </span>
                        
                        <h2 class="text-3.5xl sm:text-5.5xl font-heading font-black leading-tight text-white drop-shadow-md">
                            <?php echo htmlspecialchars($settings['school_name']); ?>
                        </h2>
                        
                        <p class="text-base text-slate-200 font-light leading-relaxed drop-shadow mx-auto lg:mx-0">
                            "<?php echo htmlspecialchars($settings['school_motto']); ?>"<br>
                            <span class="text-slate-300"><?php echo htmlspecialchars($settings['banner_subtitle'] ?? 'เน้นทักษะชีวิต ความดีงาม คุณธรรมสูงส่ง ส่งผ่านความใส่ใจในระดับชั้น:'); ?></span> 
                            <span class="text-white font-semibold underline decoration-pink-400"><?php echo htmlspecialchars($settings['levels']); ?></span>
                        </p>

                        <div class="flex flex-wrap gap-4 pt-2 justify-center lg:justify-start">
                            <a href="#news" class="bg-school-pink hover:bg-school-pink-dark text-white px-7 py-3.5 rounded-2xl font-bold shadow-lg shadow-pink-500/20 transition-all hover:translate-y-[-2px] flex items-center gap-2 text-sm z-30">
                                อ่านข่าวสารล่าสุด
                            </a>
                            <a href="#teachers" class="bg-white/10 hover:bg-white/15 text-white backdrop-blur px-7 py-3.5 rounded-2xl font-semibold border border-white/20 transition-all hover:translate-y-[-2px] text-sm z-30">
                                ทำเนียบข้าราชการครู
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 4. STAT PACK SENSOR (การแสดงผลสถิติที่สำคัญเชิงด่วน) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-30">
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-pink-100/50 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="flex items-center gap-4 p-2">
                <div class="p-4 bg-pink-50 rounded-2xl text-school-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">นักเรียนทั้งหมด</div>
                    <div class="text-2xl font-heading font-black text-slate-800"><?php echo htmlspecialchars($total_students_count); ?> คน</div>
                </div>
            </div>
            
            <div class="flex items-center gap-4 p-2">
                <div class="p-4 bg-pink-50 rounded-2xl text-school-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ครูและบุคลากร</div>
                    <div class="text-2xl font-heading font-black text-slate-800"><?php echo htmlspecialchars($total_teachers_count); ?> ท่าน</div>
                </div>
            </div>

            <div class="flex items-center gap-4 p-2">
                <div class="p-4 bg-pink-50 rounded-2xl text-school-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">คลังแผนงาน/เอกสาร</div>
                    <div class="text-2xl font-heading font-black text-slate-800"><?php echo count($downloads_list); ?> ชุด</div>
                </div>
            </div>

            <div class="flex items-center gap-4 p-2">
                <div class="p-4 bg-pink-50 rounded-2xl text-school-pink">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ยอดผู้เข้าชมเว็บ</div>
                    <div class="text-2xl font-heading font-black text-slate-800"><?php echo number_format($settings['visitor_count']); ?> ครั้ง</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. MAIN WEBSITE CONTENT BODY -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-16">

        <!-- 5.1 ผู้อำนวยการโรงเรียนสาสน์สวัสดี -->
        <section class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center bg-white rounded-3xl p-8 shadow-sm border border-pink-100/50">
            <div class="md:col-span-4 flex flex-col items-center">
                <div class="w-48 h-48 rounded-full overflow-hidden border-4 border-school-pink shadow-lg">
                    <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($settings['director_image'])); ?>" alt="ภาพผู้อำนวยการ" class="w-full h-full object-cover" referrerPolicy="no-referrer">
                </div>
                <h4 class="font-heading font-bold text-lg text-slate-800 mt-4 leading-none"><?php echo htmlspecialchars($settings['director_name']); ?></h4>
                <p class="text-xs text-school-pink font-bold uppercase tracking-wider mt-1"><?php echo htmlspecialchars($settings['director_title']); ?></p>
            </div>
            <div class="md:col-span-8 space-y-4">
                <div class="text-school-pink flex items-center gap-1.5 font-bold text-sm">
                    <span class="w-2.5 h-6 bg-school-pink rounded-full"></span>
                    สารจากผู้บริหารโรงเรียนบ้านหนองหว้า
                </div>
                <h3 class="text-2xl font-heading font-black text-slate-900 leading-tight">
                    <?php echo htmlspecialchars($settings['director_message_title'] ?? 'มุ่งมั่นเสริมนวัตกรรมการเรียนการสอน เชิดชูคุณธรรมความดี'); ?>
                </h3>
                <p class="text-sm font-light leading-relaxed text-slate-600 whitespace-pre-line">
                    <?php echo htmlspecialchars($settings['director_message'] ?? '"โรงเรียนบ้านหนองหว้า ขอตลับใจเป็นพันธมิตรร่วมกับชุมชน ผู้ปกครอง เพื่อขับเคลื่อนและสร้างสรรค์โอกาสทางวิชาการและวิชาชีพแก่นักเรียน สู่ความพร้อมในการปฏิสัมพันธ์และดำรงชีพในศตวรรษที่ 21 เรามุ่งเสกสร้างสภาพแวดล้อมที่สะอาด ปลอดภัย เพื่อเสริมองค์ความรู้อย่างบูรณาการสูงสุด"'); ?>
                </p>
                <!-- วิดีโอแนะนำโรงเรียนบ้านหนองหว้า (หากระบุ) -->
                <?php if (!empty($settings['youtube_intro_url'])): ?>
                    <div class="pt-2">
                        <a href="<?php echo htmlspecialchars($settings['youtube_intro_url']); ?>" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-school-pink hover:underline bg-pink-50 px-4 py-2.5 rounded-xl transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            คลิกชมวิดีโอแนะนำภาพกิจกรรมโรงเรียนประถมศึกษาต้นแบบ
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 5.2 ข่าวประชาสัมพันธ์และกิจกรรมล่าสุด (Dynamic Database News Center) -->
        <section id="news" class="space-y-6 scroll-mt-24">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <div>
                    <h3 class="text-3xl font-heading font-extrabold text-slate-900 leading-none">ข่าวประชาสัมพันธ์และกิจกรรมของโรงเรียน</h3>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">กองบรรณาธิการข่าวและประชาสัมพันธ์รอบรั้วชมพูขาว โรงเรียนบ้านหนองหว้า</p>
                </div>
                <!-- ตัวเลือกกรองข่าว -->
                <div class="flex flex-wrap gap-2">
                    <a href="index.php?cat=ทั้งหมด#news" class="px-4 py-2 rounded-xl text-xs font-bold transition <?php echo $selected_category === 'ทั้งหมด' ? 'bg-school-pink text-white shadow-md shadow-pink-500/10' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>">ทั้งหมด</a>
                    <?php foreach ($news_categories as $cat): ?>
                        <a href="index.php?cat=<?php echo urlencode($cat); ?>#news" class="px-4 py-2 rounded-xl text-xs font-bold transition <?php echo $selected_category === $cat ? 'bg-school-pink text-white shadow-md shadow-pink-500/10' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>">
                            <?php echo $cat; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- รายการข่าวสาร -->
            <?php if (empty($news_list)): ?>
                <div class="bg-white rounded-3xl p-12 text-center text-slate-400 border border-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                    ไม่พบข่าวประชาสัมพันธ์ในหมวดหมู่นี้
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($news_list as $news): ?>
                        <article class="bg-white rounded-2xl border border-pink-100/50 shadow-sm overflow-hidden group hover:shadow-md hover:border-school-pink/20 transition-all flex flex-col">
                            <div class="h-48 overflow-hidden relative">
                                <?php if (isset($news['sticky_flag']) && $news['sticky_flag'] == 1): ?>
                                    <span class="absolute top-3 left-3 bg-school-pink text-white text-[9px] font-extrabold px-3 py-1 rounded-full uppercase shadow">
                                        ข่าวสำคัญปักหมุด
                                    </span>
                                <?php endif; ?>
                                <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($news['image_url'] ?? 'https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600')); ?>" alt="ข่าวโรงเรียน" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" referrerPolicy="no-referrer">
                            </div>
                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div class="space-y-2">
                                    <span class="inline-block bg-pink-50 text-school-pink text-[10px] font-black px-2.5 py-0.5 rounded">
                                        <?php echo htmlspecialchars($news['category']); ?>
                                    </span>
                                    <h4 class="font-heading font-bold text-slate-800 line-clamp-2 leading-snug group-hover:text-school-pink transition-colors">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                    </h4>
                                    <p class="text-xs text-slate-500 font-light line-clamp-3">
                                        <?php echo htmlspecialchars($news['summary'] ?? mb_substr($news['content'], 0, 100) . '...'); ?>
                                    </p>
                                </div>
                                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-semibold">
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <?php echo thaiDate($news['date']); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <?php echo number_format($news['views']); ?> วิว
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- ปุ่มสลับหน้าข่าว (News Pagination) สำหรับหน้าบ้าน -->
                <?php if ($total_news_pages > 1): ?>
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-8 border-t border-slate-100/70 text-xs mt-6">
                        <div class="text-slate-400 font-bold">
                            ข่าวทั้งหมดในหมวดหมู่นี้: <?php echo htmlspecialchars($total_news_matching); ?> รายการ (หน้า <?php echo $news_page; ?> / <?php echo $total_news_pages; ?>)
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($news_page > 1): ?>
                                <a href="index.php?cat=<?php echo urlencode($selected_category); ?>&npage=<?php echo $news_page - 1; ?>#news" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 hover:text-school-pink hover:border-school-pink/20 font-bold rounded-xl transition shadow-sm">
                                    <span>ย้อนหลัง / ข้อมูลย้อนหลัง</span>
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2.5 bg-slate-50 border border-slate-100 text-slate-350 font-bold rounded-xl cursor-not-allowed">ข้อมูลย้อนหลัง</span>
                            <?php endif; ?>

                            <div class="hidden sm:flex items-center gap-1.5">
                                <?php for ($i = 1; $i <= $total_news_pages; $i++): ?>
                                    <?php if ($i == $news_page): ?>
                                        <span class="px-3.5 py-2 rounded-xl bg-school-pink text-white font-extrabold shadow-md shadow-pink-500/10"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="index.php?cat=<?php echo urlencode($selected_category); ?>&npage=<?php echo $i; ?>#news" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>

                            <?php if ($news_page < $total_news_pages): ?>
                                <a href="index.php?cat=<?php echo urlencode($selected_category); ?>&npage=<?php echo $news_page + 1; ?>#news" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 hover:text-school-pink hover:border-school-pink/20 font-bold rounded-xl transition shadow-sm">
                                    <span>หน้าถัดไป</span>
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2.5 bg-slate-50 border border-slate-100 text-slate-350 font-bold rounded-xl cursor-not-allowed">หน้าถัดไป</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- 5.3 ทำเนียบผู้บริหาร และข้าราชการครู (Database Teachers Roster) -->
        <section id="teachers" class="space-y-6 scroll-mt-24">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <div>
                    <h3 class="text-3xl font-heading font-extrabold text-slate-900 leading-none">ทำเนียบผู้บริหาร และข้าราชการครู</h3>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">บุคลากรผู้สืบสานการศึกษาและจิตวิญญาณแห่งความเป็นครู</p>
                </div>
                <!-- กรองฝ่ายงานครู -->
                <div class="flex flex-wrap gap-1.5 max-w-4xl">
                    <?php foreach ($teacher_menu_filters as $key => $val): ?>
                        <a href="index.php?group=<?php echo urlencode($key); ?>#teachers" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition <?php echo $selected_group === $key ? 'bg-school-pink text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>">
                            <?php echo $val; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <?php foreach ($teachers_list as $teacher): ?>
                    <div class="bg-white rounded-2xl p-4 border border-pink-50 relative overflow-hidden group hover:shadow-md transition duration-200 flex flex-col items-center text-center">
                        <div class="absolute top-0 left-0 w-full h-1.5 bg-school-pink"></div>
                        <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-pink-100 shadow-inner mb-4">
                            <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($teacher['image_url'] ?? 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=300')); ?>" alt="บุคลากรครู" class="w-full h-full object-cover group-hover:scale-105 transition-all" referrerPolicy="no-referrer">
                        </div>
                        <h5 class="text-xs font-bold text-slate-900 leading-tight mb-1">
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </h5>
                        <p class="text-[10px] text-slate-400 font-medium leading-tight mb-2">
                            <?php echo htmlspecialchars($teacher['level']); ?>
                        </p>
                        <span class="mt-auto inline-block bg-pink-50 text-school-pink text-[9px] font-extrabold px-2.5 py-1 rounded-full">
                            <?php 
                            $raw_grp = $teacher['subject_group'] ?? 'งานสอนทั่วไป';
                            echo htmlspecialchars(isset($teacher_label_map[$raw_grp]) ? $teacher_label_map[$raw_grp] : $raw_grp); 
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 5.4 ข้อมูลสถิตินักเรียน (Interactive Student Demographics & Yearly Comparison) -->
        <section id="stats" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-stretch scroll-mt-24">
            
            <!-- 2. รายละเอียดระดับชั้นและการกระจายตัวของประชากรประจำปีปัจจุบัน (lg:col-span-7) -->
            <div class="md:col-span-12 lg:col-span-7 bg-white rounded-3xl p-6 shadow-sm border border-pink-50 space-y-5 flex flex-col justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-heading font-black text-slate-900 leading-tight">สถิตินักเรียนรายระดับชั้นเรียน</h3>
                    <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase mt-1">ประจำปีการศึกษา <?php echo htmlspecialchars($active_current_year); ?> (รวม <?php echo number_format($total_students_count); ?> คน)</p>
                </div>

                <div class="space-y-3.5 pt-1">
                    <?php
                    foreach ($student_stats as $st):
                        $st_count = intval($st['student_count']);
                        $percent = $total_students_count > 0 ? round(($st_count / $total_students_count) * 100, 1) : 0;
                    ?>
                        <div class="space-y-1">
                            <div class="flex justify-between text-[11px] font-bold">
                                <span class="text-slate-700"><?php echo htmlspecialchars($st['grade_name']); ?></span>
                                <span class="text-school-pink font-extrabold"><?php echo htmlspecialchars($st_count); ?> คน (<?php echo $percent; ?>%)</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100/85 rounded-full overflow-hidden border border-slate-100/20">
                                <div class="bg-gradient-to-r from-pink-300 to-school-pink h-full rounded-full transition-all duration-300" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. แผนภูมิเปรียบเทียบจำนวนนักเรียนรายปีการศึกษา (lg:col-span-5) -->
            <div class="md:col-span-12 lg:col-span-5 bg-gradient-to-br from-indigo-950 to-slate-900 text-white rounded-3xl p-6 shadow-md border border-indigo-900/30 flex flex-col justify-between space-y-6 animate-fadeIn">
                <div>
                    <h3 class="text-base sm:text-lg font-heading font-black text-pink-300 leading-tight flex items-center gap-1.5">
                        📈 เปรียบเทียบประชากรนักเรียนรายปี
                    </h3>
                    <p class="text-[10px] text-indigo-200 font-semibold uppercase tracking-wider mt-1">อัตราการเปลี่ยนแปลงจำนวนนักเรียนรวมในแต่ละปีการศึกษา</p>
                </div>

                <div class="flex items-end justify-around gap-2 px-1 py-1 min-h-[180px] w-full">
                    <?php
                    if (!empty($yearly_comparison)):
                        $max_total = 1;
                        foreach ($yearly_comparison as $comp) {
                            if (intval($comp['total_count']) > $max_total) {
                                $max_total = intval($comp['total_count']);
                            }
                        }
                        foreach ($yearly_comparison as $comp):
                            $h_pct = round((intval($comp['total_count']) / $max_total) * 100);
                            $is_current = ($comp['academic_year'] === $active_current_year);
                    ?>
                        <div class="flex flex-col items-center gap-2 group cursor-pointer flex-1 animate-fadeIn">
                            <div class="text-[10px] font-black <?php echo $is_current ? 'text-pink-400 scale-105' : 'text-slate-300'; ?> group-hover:scale-110 transition duration-150">
                                <?php echo number_format($comp['total_count']); ?> คน
                            </div>
                            <div class="w-10 sm:w-12 bg-indigo-900/50 rounded-t-xl overflow-hidden relative border border-indigo-805/10 min-h-[15px] max-h-[170px]" style="height: <?php echo max(15, $h_pct * 1.3); ?>px">
                                <div class="absolute bottom-0 left-0 w-full rounded-t-xl transition-all duration-500 <?php echo $is_current ? 'bg-gradient-to-t from-school-pink to-pink-400 shadow-lg shadow-pink-500/20' : 'bg-slate-500/85 group-hover:bg-slate-400'; ?>" style="height: 100%"></div>
                            </div>
                            <div class="text-[9px] font-black uppercase text-center mt-1 <?php echo $is_current ? 'text-pink-300 font-bold' : 'text-indigo-200'; ?>">
                                ปี <?php echo htmlspecialchars($comp['academic_year']); ?>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="text-xs text-indigo-200 text-center py-10 w-full font-bold">
                            ไม่มีข้อมูลเปรียบเทียบในขณะนี้
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-indigo-900/40 border border-indigo-800/30 rounded-2xl p-3 text-center text-[10px] text-indigo-100 flex items-center justify-center gap-1.5 font-bold leading-normal">
                    <span>💡</span> พัฒนาการของโรงเรียนเป็นไปด้วยความมั่นคงและก้าวหน้าต่อเนื่อง
                </div>
            </div>
        </section>

        <!-- 5.4.5 แหล่งเรียนรู้และลิงก์ระบบงานภายนอก (Custom External Links Grid) -->
        <section id="school-tools" class="space-y-6 scroll-mt-24">
            <div>
                <h3 class="text-3xl font-heading font-extrabold text-slate-900 leading-none">แหล่งเรียนรู้และระบบงานบริการออนไลน์</h3>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">สื่อพัฒนานวัตกรรมการสอนและเครือข่ายความร่วมมือทางการศึกษา</p>
            </div>
            
            <?php if (empty($external_links_list)): ?>
                <div class="bg-white rounded-3xl p-8 text-center text-slate-400 border border-slate-100">
                    ยังไม่มีข้อมูลลิงก์ภายนอกเสริมการเรียนรู้
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($external_links_list as $link): ?>
                        <a href="<?php echo htmlspecialchars($link['url_link'] ?? $link['url'] ?? '#'); ?>" target="_blank" class="bg-white rounded-2xl p-5 border border-pink-50/50 shadow-sm hover:shadow-md hover:border-school-pink/20 transition-all flex gap-4 group">
                            <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 border border-slate-100 shadow-sm">
                                <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($link['image_url'] ?? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&q=80&w=200')); ?>" alt="<?php echo htmlspecialchars($link['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" referrerPolicy="no-referrer">
                            </div>
                            <div class="space-y-1 select-none">
                                <span class="inline-block bg-pink-50 text-school-pink text-[9px] font-black px-2 py-0.5 rounded uppercase">
                                    <?php echo htmlspecialchars($link['category'] ?? 'บริการออนไลน์'); ?>
                                </span>
                                <h4 class="font-heading font-bold text-slate-800 text-sm group-hover:text-school-pink transition-colors line-clamp-1">
                                    <?php echo htmlspecialchars($link['title']); ?>
                                </h4>
                                <p class="text-slate-400 text-[11px] font-medium leading-tight line-clamp-2">
                                    <?php echo htmlspecialchars($link['description'] ?? 'คลิกเข้าสู่แหล่งนวัตกรรมภายนอกโรงเรียนบ้านหนองหว้า'); ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- 5.5 ศูนย์คลังจัดซื้อจัดจ้างและแผนงานแผนพัฒนา (Downloads and Procurements DB) -->
        <section id="documents" class="space-y-6 scroll-mt-24">
            <div>
                <h3 class="text-3xl font-heading font-extrabold text-slate-900 leading-none">เอกสารและแผนงานของโรงเรียน</h3>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-2 block">คลังเอกสารราชการ แผนปฏิบัติการ และข่าวจัดซื้อจัดจ้าง โรงเรียนบ้านหนองหว้า</p>
            </div>

            <!-- ตารางคลังไฟล์ -->
            <div class="bg-white rounded-3xl overflow-hidden border border-pink-50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-pink-50/55 border-b border-pink-50 text-slate-700 text-xs font-bold uppercase tracking-wider">
                                <th class="p-5">ชื่อชื่อเอกสารและหมวดหมู่</th>
                                <th class="p-5">หมวดหมู่</th>
                                <th class="p-5 text-center">ประเภท</th>
                                <th class="p-5 text-center">ขนาดไฟล์</th>
                                <th class="p-5 text-center">ดาวน์โหลด</th>
                                <th class="p-5 text-center shrink-0">ดาวน์โหลด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <?php foreach ($downloads_list as $doc): ?>
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="p-5">
                                        <div class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($doc['title']); ?></div>
                                        <p class="text-[10px] text-slate-400 font-medium mt-1">อัพโหลดเมื่อ: <?php echo thaiDate($doc['uploaded_date']); ?></p>
                                    </td>
                                    <td class="p-5">
                                        <span class="bg-slate-100 text-slate-600 font-bold px-2.5 py-1 rounded">
                                            <?php echo htmlspecialchars($doc['category']); ?>
                                        </span>
                                    </td>
                                    <td class="p-5 text-center">
                                        <span class="inline-block text-[10px] font-extrabold text-white <?php echo $doc['file_type'] === 'PDF' ? 'bg-red-500' : 'bg-blue-500'; ?> px-2 py-0.5 rounded uppercase">
                                            <?php echo htmlspecialchars($doc['file_type']); ?>
                                        </span>
                                    </td>
                                    <td class="p-5 text-center font-mono text-slate-500">
                                        <?php echo htmlspecialchars($doc['file_size']); ?>
                                    </td>
                                    <td class="p-5 text-center font-bold text-slate-600">
                                        <?php echo number_format($doc['download_count']); ?> ครั้ง
                                    </td>
                                    <td class="p-5 text-center">
                                        <a href="download_file.php?id=<?php echo $doc['id']; ?>" class="inline-flex justify-center items-center bg-school-pink hover:bg-school-pink-dark text-white rounded-lg p-2 transition shadow shadow-pink-500/10">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- 6. BRIGHT VIBRANT FOOTER (อัตลักษณ์ชมพูขาว โดดเด่น สง่างาม) -->
    <footer class="bg-slate-900 text-slate-400 font-sans border-t-4 border-school-pink pt-16 pb-8 mt-16 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-12 mb-8">
            
            <!-- คอลัมน์ที่ 1: ชื่อโรงเรียน -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <?php if (!empty($settings['school_logo'])): ?>
                        <img src="<?php echo htmlspecialchars(fixGoogleDriveUrl($settings['school_logo'])); ?>" alt="School Footer Logo" class="h-10 w-10 object-contain rounded-full bg-white p-0.5 shadow" referrerPolicy="no-referrer">
                    <?php else: ?>
                        <div class="h-10 w-10 rounded-full bg-white flex items-center justify-center text-school-pink font-black text-sm">
                            นห
                        </div>
                    <?php endif; ?>
                    <h4 class="font-heading font-extrabold text-white text-base leading-none"><?php echo htmlspecialchars($settings['school_name']); ?></h4>
                </div>
                <p class="text-[11px] leading-relaxed font-light text-slate-400">
                    ความภาคภูมิใจในการวางรากฐานการศึกษา และสร้างเสริมภูมิปัญญาของลูกหลานชาวบุรีรัมย์ระดับชั้นอนุบาล 2 ถึง ชั้นประถมศึกษาปีที่ 6
                </p>
                <div class="flex gap-3 text-white text-xs">
                    <span class="bg-school-pink text-white px-3 py-1 rounded-full font-bold">ชมพู-ขาว ก้าวไกลวิชาการ</span>
                </div>
            </div>

            <!-- คอลัมน์ที่ 2: เมนูด่วน -->
            <div class="space-y-3">
                <h4 class="font-heading font-extrabold text-white text-sm">ส่วนต่างๆ ของเว็บไซต์</h4>
                <ul class="space-y-2 text-[11px] font-semibold text-slate-400">
                    <li><a href="#news" class="hover:text-white transition">ข่าวสารและกิจกรรม</a></li>
                    <li><a href="#teachers" class="hover:text-white transition">ทำเนียบข้าราชการครู</a></li>
                    <li><a href="#stats" class="hover:text-white transition">สัดส่วนสถิตินักเรียน</a></li>
                    <li><a href="#documents" class="hover:text-white transition">ศูนย์จัดซื้อจัดจ้างและเอกสารราชการ</a></li>
                </ul>
            </div>

            <!-- คอลัมน์ที่ 3: แผนที่สังกัด -->
            <div class="space-y-3">
                <h4 class="font-heading font-extrabold text-white text-sm">ข้อมูลสังกัดทางการ</h4>
                <p class="text-[11px] leading-relaxed font-light text-slate-400">
                    <?php echo htmlspecialchars($settings['jurisdiction']); ?><br>
                    สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.) กระทรวงศึกษาธิการ
                </p>
            </div>

            <!-- คอลัมน์ที่ 4: ข้อมูลการติดต่อ -->
            <div class="space-y-3">
                <h4 class="font-heading font-extrabold text-white text-sm">ข้อมูลติดต่อสอบถาม</h4>
                <p class="text-[11px] leading-relaxed font-light text-slate-400">
                    <?php echo htmlspecialchars($settings['address']); ?><br>
                    <strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($settings['phone']); ?><br>
                    <strong>อีเมลด่วน:</strong> <?php echo htmlspecialchars($settings['email']); ?>
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center text-[10px] text-slate-500 font-medium">
            <p>© 2026 <?php echo htmlspecialchars($settings['school_name']); ?> (Ban Nong Wa School). สงวนลิขสิทธิ์ความปลอดภัยและระบบข้อมูล</p>
            <p class="flex items-center gap-1.5 mt-2 md:mt-0 font-semibold text-school-pink bg-school-pink/5 px-2.5 py-1 rounded">
                <span class="w-1.5 h-1.5 rounded-full bg-school-pink animate-ping"></span>
                ออกแบบและเขียนโปรแกรมระบบด้วย PHP 8+ และ MySQL PDO Secure System 
            </p>
        </div>
    </footer>

</body>
</html>
