<?php
/**
 * บันทึกผลการปฏิบัติงาน (PA) และ แฟ้มสะสมผลงาน (Portfolio)
 * สำหรับคณะครูโรงเรียนบ้านหนองหว้า
 * ดึงข้อมูลจริงจากระบบฐานข้อมูล แสดงผลอย่างสง่างามในธีม ชมพู-ขาว
 */

require_once 'db_connect.php';

// นำเข้าข้อมูลการตั้งค่าโรงเรียน
try {
    $settingsStmt = $pdo->query("SELECT * FROM `settings` WHERE `id` = 1");
    $settings = $settingsStmt->fetch();
} catch (Exception $e) {
    $settings = [
        'school_name' => 'โรงเรียนบ้านหนองหว้า',
        'short_name' => 'ร.ร.บ้านหนองหว้า',
        'school_motto' => 'ชมพู-ขาว ก้าวไกลวิชาการ',
        'school_logo' => ''
    ];
}

// ดึงรายละเอียดบุคลากรครูทั้งหมด
try {
    $teachers_stmt = $pdo->query("SELECT * FROM `teachers` ORDER BY `sort_order` ASC, `id` ASC");
    $teachers_list = $teachers_stmt->fetchAll();
} catch (Exception $e) {
    $teachers_list = [];
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกผลการปฏิบัติงาน (PA) & Portfolio | <?php echo htmlspecialchars($settings['school_name']); ?></title>
    <!-- โหลดฟอนต์ภาษาไทย Kanit & Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800;900&family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- โหลด Tailwind CSS -->
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
                                DEFAULT: '#ffffff',
                                soft: '#fff1f2',
                            }
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-pink-50/40 via-white to-pink-50/20 text-slate-700 min-h-screen flex flex-col justify-between font-sans">

    <!-- HEADER / NAVIGATION HUB -->
    <header class="bg-white/95 backdrop-blur sticky top-0 z-40 border-b border-pink-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <?php if (!empty($settings['school_logo'])): ?>
                    <img src="<?php echo htmlspecialchars($settings['school_logo']); ?>" alt="School Logo" class="h-12 w-12 object-contain rounded-full shadow-md group-hover:scale-105 transition-all" referrerPolicy="no-referrer">
                <?php else: ?>
                    <div class="h-12 w-12 rounded-full bg-gradient-to-tr from-school-pink to-pink-300 flex items-center justify-center text-white font-black text-xl shadow-md group-hover:scale-105 transition-all">
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
            
            <a href="index.php" class="inline-flex items-center gap-1 bg-school-pink hover:bg-school-pink-dark text-white text-xs px-4 py-2 rounded-xl shadow-md font-bold transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                กลับหน้าแรก
            </a>
        </div>
    </header>

    <!-- MAIN BODY CONTENT -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-1 w-full">
        
        <!-- BACK BUTTON & TRAIL -->
        <a href="index.php" class="inline-flex items-center gap-2 text-xs font-bold text-school-pink hover:text-school-pink-dark transition mb-6 bg-pink-100/50 hover:bg-pink-100 px-4 py-2 rounded-xl border border-pink-200/40">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            กลับสู่หน้าหลักเว็บไซต์โรงเรียน
        </a>

        <!-- PA STATS BANNER -->
        <div class="bg-gradient-to-r from-school-pink/15 via-pink-100/30 to-white border border-pink-200/50 rounded-3xl p-6 sm:p-10 mb-12 shadow-sm space-y-4 relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-pink-300 opacity-10 rounded-full blur-2xl"></div>
            <div class="text-school-pink text-xs font-black uppercase tracking-widest flex items-center gap-1.5">
                <span class="w-2 h-4 bg-school-pink rounded-full"></span>
                Performance Agreement (PA) Reports
            </div>
            
            <h2 class="text-2.5xl sm:text-4xl font-heading font-black text-slate-900 leading-tight">
                รายงานข้อตกลงในการพัฒนางาน (PA) & Portfolio
            </h2>
            <p class="text-sm max-w-3xl text-slate-600 font-light leading-relaxed">
                ระบบสืบค้นข้อตกลงและผลการประเมินการพัฒนางานตามหลักเกณฑ์วิทยฐานะใหม่ (PA) ของคณะครูและบุคลากรทางการศึกษา 
                โรงเรียนบ้านหนองหว้า เพื่อสนับสนุนการวัดผลการสอนที่มุ่งผลลัพธ์ผู้เรียนและการประกันคุณภาพการศึกษาตามนโยบายกระทรวงศึกษาธิการ
            </p>
        </div>

        <!-- PORTFOLIO GRID -->
        <section class="space-y-6">
            <div class="border-b border-pink-100 pb-3 flex justify-between items-end">
                <h3 class="text-xl font-heading font-extrabold text-slate-800">
                    บัญชีรายชื่อข้าราชการครูผู้จัดทำข้อตกลง (PA)
                </h3>
                <span class="text-xs text-slate-400 font-semibold bg-white border border-slate-100 px-3 py-1 rounded-full shadow-sm">
                    ทั้งหมด <?php echo count($teachers_list); ?> ท่าน
                </span>
            </div>

            <?php if (empty($teachers_list)): ?>
                <div class="bg-white rounded-3xl p-16 text-center text-slate-400 border border-slate-100 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-pink-300 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    ไม่พบข้อมูลประวัติบุคลากรครูในระบบปัจจุบัน
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($teachers_list as $teacher): ?>
                        <div class="bg-white rounded-3xl border border-pink-100/50 hover:border-school-pink/25 overflow-hidden shadow-sm hover:shadow-md transition duration-300 flex flex-col justify-between">
                            
                            <!-- Teacher Profile Header -->
                            <div class="p-6 pb-4 space-y-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 rounded-2xl overflow-hidden border-2 border-pink-100 shadow-md shrink-0">
                                        <img src="<?php echo htmlspecialchars($teacher['image_url'] ?? 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=300'); ?>" alt="Teacher" class="w-full h-full object-cover">
                                    </div>
                                    <div class="space-y-1">
                                        <span class="inline-block bg-pink-50 text-school-pink text-[9px] font-black px-2 py-0.5 rounded-full uppercase">
                                            <?php echo htmlspecialchars($teacher['subject_group'] ?? 'กลุ่มสาระการเรียนรู้'); ?>
                                        </span>
                                        <h4 class="font-heading font-extrabold text-slate-800 text-base leading-snug">
                                            <?php echo htmlspecialchars($teacher['name']); ?>
                                        </h4>
                                        <p class="text-[11px] text-slate-400 font-medium">
                                            <?php echo htmlspecialchars($teacher['position']); ?>
                                        </p>
                                        <p class="text-[10px] text-school-pink font-bold">
                                            <?php echo htmlspecialchars($teacher['level']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Link Action Buttons -->
                            <div class="px-6 pb-6 pt-2 bg-slate-50/50 border-t border-slate-100 space-y-2 mt-auto">
                                
                                <!-- 1. PA Report Link -->
                                <?php if (!empty($teacher['pa_link_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($teacher['pa_link_url']); ?>" target="_blank" class="w-full bg-school-pink hover:bg-school-pink-dark text-white text-xs py-2.5 px-4 rounded-xl shadow-sm text-center font-bold flex items-center justify-center gap-2 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        ชมข้อตกลงในการพัฒนางาน (PA)
                                    </a>
                                <?php else: ?>
                                    <div class="w-full bg-slate-100 text-slate-400 text-xs py-2.5 px-4 rounded-xl text-center font-bold flex items-center justify-center gap-2 border border-dashed border-slate-200 cursor-not-allowed select-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        ยังไม่ได้อัพโหลดข้อมูลรายงาน PA
                                    </div>
                                <?php endif; ?>

                                <!-- 2. Portfolio / Academic URLs -->
                                <?php if (!empty($teacher['portfolio_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($teacher['portfolio_url']); ?>" target="_blank" class="w-full bg-white hover:bg-pink-50 text-slate-700 hover:text-school-pink text-xs py-2.5 px-4 rounded-xl text-center font-bold border border-slate-200 hover:border-school-pink/30 flex items-center justify-center gap-2 transition shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                        ส่องแฟ้มสะสมงาน & ผลงานวิชาการ
                                    </a>
                                <?php else: ?>
                                    <div class="w-full bg-slate-100 text-slate-400 text-xs py-2.5 px-4 rounded-xl text-center font-bold flex items-center justify-center gap-2 border border-dashed border-slate-200 cursor-not-allowed select-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        ยังไม่ได้เชื่อมโยงลิงก์ Portfolio
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-slate-400 font-sans border-t-4 border-school-pink pt-10 pb-8 mt-16 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-3">
            <p class="font-heading font-black text-white text-sm">
                <?php echo htmlspecialchars($settings['school_name']); ?>
            </p>
            <p class="text-[11px] font-light max-w-xl mx-auto">
                <?php echo htmlspecialchars($settings['address']); ?><br>
                เบอร์โทรศัพท์: <?php echo htmlspecialchars($settings['phone']); ?> | อีเมล: <?php echo htmlspecialchars($settings['email']); ?>
            </p>
            <div class="pt-4 border-t border-slate-800 text-[10px] text-slate-500">
                &copy; 2026 <?php echo htmlspecialchars($settings['school_name']); ?>. All Rights Reserved. ระบบบริหารจัดการสำหรับสถานศึกษาศึกษาต้นแบบ
            </div>
        </div>
    </footer>

</body>
</html>
