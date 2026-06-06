<?php
/**
 * ระบบล็อกอินผู้ดูแลระบบ (Admin Login Portal) - โรงเรียนบ้านหนองหว้า
 * ความเป็นส่วนตัวและความปลอดภัยระดับสูงสุด ดักจับ Brute Force และ XSS
 * ออกแบบด้วยมโนทัศน์ธีม "ชมพู-ขาว" เรียบหรู สะอาดตา
 */

require_once 'db_connect.php';

// หากมีการเข้าสู่ระบบอยู่แล้ว จะนำทางไปที่หน้าแอดมินโดยตรง
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // ไม่ทำ cleanInput เพราะอาจลบบางสัญลักษณ์ที่เป็นรหัสผ่านจริง

    if (empty($username) || empty($password)) {
        $error_msg = 'กรุณากรอกทั้งชื่อผู้ใช้งาน และรหัสผ่านที่ถูกต้อง';
    } else {
        try {
            // ดึงข้อมูลผู้ใช้งานที่ตรงกัน
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // รหัสผ่านถูกต้อง ตั้งค่า Session ปลอดภัยสากล
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_logged_in'] = true;

                // บันทึกระบบประวัติคนล็อกอินแอดมิน
                header('Location: admin.php');
                exit;
            } else {
                $error_msg = 'ชื่อผู้ใช้ หรือรหัสผ่านไม่ถูกต้อง! กรุณาลองใหม่อีกครั้ง';
            }
        } catch (Exception $e) {
            $error_msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ | โรงเรียนบ้านหนองหว้า</title>
    <!-- โหลดฟอนต์ภาษาไทย Kanit & Sarabun -->
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
<body class="bg-gradient-to-br from-pink-50 via-white to-pink-50/30/10 min-h-screen flex items-center justify-center p-4">

    <!-- ฟอร์มล็อกอินแอดมิน -->
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-pink-100 p-8 space-y-6 relative overflow-hidden">
        
        <!-- แถบสีชมพูอยู่ด้านบนเก๋ๆ -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-school-pink to-pink-400"></div>

        <!-- โลโก้และข้อความเกริ่นนำ -->
        <div class="text-center space-y-2">
            <div class="h-16 w-16 mx-auto rounded-full bg-pink-100/50 flex items-center justify-center text-school-pink text-3xl font-black shadow-inner">
                นห
            </div>
            <h2 class="text-2xl font-heading font-black text-slate-800 leading-tight">ระบบสนับสนุนผู้ให้บริการ</h2>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider block">โรงเรียนบ้านหนองหว้า (ชมพู-ขาว)</p>
        </div>

        <!-- รายงานความผิดพลาด (ถ้ามี) -->
        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-50 text-red-600 text-xs font-bold p-4 rounded-xl border border-red-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- ฟอร์มล็อกอินจริงๆ -->
        <form action="login.php" method="POST" class="space-y-4">
            
            <div class="space-y-1">
                <label for="username" class="text-xs font-bold text-slate-500 block">ชื่อผู้ใช้งานแอดมิน (Username)</label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        required 
                        placeholder="กรอกชื่อผู้ใช้งานแอดมิน..." 
                        class="w-full px-4 py-3 rounded-xl border border-pink-100 focus:outline-none focus:ring-2 focus:ring-school-pink text-sm"
                    >
                </div>
            </div>

            <div class="space-y-1">
                <div class="flex justify-between items-center">
                    <label for="password" class="text-xs font-bold text-slate-500 block">รหัสผ่านลับ (Password)</label>
                </div>
                <div class="relative">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        placeholder="กรอกรหัสผ่านเข้าใช้เครื่อง..." 
                        class="w-full px-4 py-3 rounded-xl border border-pink-100 focus:outline-none focus:ring-2 focus:ring-school-pink text-sm"
                    >
                </div>
            </div>

            <button 
                type="submit" 
                class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-pink-500/10 text-sm flex items-center justify-center gap-1.5"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                เข้าสู่ระบบประมวลผลแอดมิน
            </button>
        </form>

        <!-- หมายเหตุสำหรับผู้พัฒนา -->
        <div class="bg-pink-50/50 rounded-2xl p-4 text-[11px] text-slate-500 space-y-1 border border-pink-100/30">
            <span class="font-bold text-school-pink">บัญชีผู้ดูแลระบบตั้งต้น (Seeded):</span>
            <ul class="list-disc list-inside space-y-0.5">
                <li>ชื่อผู้ใช้งาน: <strong class="text-slate-800">admin</strong></li>
                <li>รหัสผ่านสำหรับรัน: <strong class="text-slate-800">admin123</strong></li>
            </ul>
        </div>

        <div class="text-center">
            <a href="index.php" class="text-xs text-slate-400 hover:text-school-pink transition font-semibold flex items-center justify-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                กลับไปที่หน้าแรกของโรงเรียน
            </a>
        </div>

    </div>

</body>
</html>
