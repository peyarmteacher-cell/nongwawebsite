<?php
/**
 * 🔑 ส่วนจัดการบัญชีผู้ดูแลระบบ / แอดมินโรงเรียน (Administrators Management)
 * รองรับการเพิ่ม, แก้ไขสิทธิ์, เปลี่ยนชื่อผู้ใช้, และเปลี่ยนรหัสผ่านเพื่อความปลอดภัยของโรงเรียน
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

// 1. ดำเนินการโหลดบัญชีทั้งหมด
try {
    $admins_stmt = $pdo->query("SELECT * FROM `users` ORDER BY `id` ASC");
    $admins_list = $admins_stmt->fetchAll();
} catch (Exception $e) {
    $admins_list = [];
}

// 2. ตรวจสอบการกดแก้ไขแอดมินคนใดคนหนึ่ง
$edit_admin_item = null;
if (isset($_GET['edit_admin'])) {
    $edit_id = intval($_GET['edit_admin']);
    try {
        $stmt_get = $pdo->prepare("SELECT * FROM `users` WHERE `id` = :id LIMIT 1");
        $stmt_get->execute(['id' => $edit_id]);
        $edit_admin_item = $stmt_get->fetch();
    } catch (Exception $e) {
        $edit_admin_item = null;
    }
}
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- ด้านซ้าย: ฟอร์มเพิ่ม/แก้ไขโปรไฟล์แอดมิน (5/12 คอลัมน์) -->
    <div class="lg:col-span-5 space-y-6">
        
        <?php if ($edit_admin_item): ?>
            <!-- ✏️ บล็อกแก้ข้อมูลผู้ดูแลระบบ -->
            <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 border border-indigo-900 shadow-md space-y-4">
                <div class="flex items-center justify-between border-b border-indigo-900/50 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">✏️</span>
                        <div>
                            <h4 class="font-heading font-black text-sm text-pink-300">แก้ไขบัญชีผู้ดูแลระบบ</h4>
                            <p class="text-[10px] text-slate-400">แก้ไขข้อมูลหรือสิทธิ์ความปลอดภัยของบัญชี</p>
                        </div>
                    </div>
                    <a href="admin.php?tab=admins" class="text-[10px] bg-indigo-900 border border-indigo-800 hover:bg-slate-800 px-3 py-1.5 rounded-xl text-white font-bold transition">ยกเลิก</a>
                </div>
                
                <form action="admin.php?tab=admins" method="POST" class="space-y-4 text-xs font-semibold text-slate-300">
                    <input type="hidden" name="edit_admin_submit" value="1">
                    <input type="hidden" name="admin_id" value="<?php echo $edit_admin_item['id']; ?>">

                    <div class="space-y-1">
                        <label class="block text-slate-400 font-bold uppercase tracking-wider">ชื่อผู้ใช้งาน (Username) <span class="text-red-400">*</span></label>
                        <input type="text" name="admin_username" required value="<?php echo htmlspecialchars($edit_admin_item['username']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-3 text-xs text-white focus:ring-2 focus:ring-school-pink outline-none">
                        <p class="text-[10px] text-slate-400 leading-normal">ใช้สำหรับเข้าสู่ระบบ (เช่น admin, teacher_nongwa)</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-400 font-bold uppercase tracking-wider">รหัสผ่านใหม่ (Password) <span class="text-slate-400 font-normal">(เว้นว่างไว้หากต้องการใช้รหัสเดิม)</span></label>
                        <input type="password" name="admin_password" placeholder="••••••••" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-3 text-xs text-white focus:ring-2 focus:ring-school-pink outline-none">
                        <p class="text-[10px] text-yellow-400">ระบุเฉพาะเมื่อต้องการอัปเดตเปลี่ยนรหัสผ่านใหม่เท่านั้น</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-400 font-bold uppercase tracking-wider">ชื่อจริง - นามสกุล หรือตำแหน่ง <span class="text-red-400">*</span></label>
                        <input type="text" name="admin_name" required value="<?php echo htmlspecialchars($edit_admin_item['name']); ?>" placeholder="เช่น นายวิทยา รักดี" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-3 text-xs text-white focus:ring-2 focus:ring-school-pink outline-none">
                        <p class="text-[10px] text-slate-400">ใช้ระบุชื่อผู้สร้างผลงานหรือแอดมินที่กำลังใช้งานระบบ</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-400 font-bold uppercase tracking-wider">ระดับสิทธิ์ (Role) <span class="text-red-400">*</span></label>
                        <select name="admin_role" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-3 text-xs text-white focus:ring-2 focus:ring-school-pink outline-none cursor-pointer">
                            <option value="Administrator" <?php echo ($edit_admin_item['role'] === 'Administrator') ? 'selected' : ''; ?>>Administrator (ผู้ดูแลระบบหลัก)</option>
                            <option value="Editor" <?php echo ($edit_admin_item['role'] === 'Editor') ? 'selected' : ''; ?>>Editor (ผู้บันทึกข่าวสาร/ข้อมูล)</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3 px-4 rounded-xl transition shadow-lg shadow-pink-500/20 text-xs flex items-center justify-center gap-2 mt-2">
                        💾 บันทึกการแก้ไขข้อมูลผู้ดูแล
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- ➕ บล็อกลงทะเบียนแอดมินท่านใหม่ -->
            <div class="bg-white rounded-3xl p-6 border border-pink-100 shadow-md space-y-4">
                <div class="border-b border-pink-50 pb-3">
                    <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-2">
                        <span class="text-xl">➕</span>
                        <div>
                            <span>ลงทะเบียนแอดมินท่านใหม่</span>
                            <span class="block text-[10px] font-normal text-slate-400">เพิ่มสิทธิ์ครูในการแก้ไขข้อมูลหลังบ้าน</span>
                        </div>
                    </h4>
                </div>

                <form action="admin.php?tab=admins" method="POST" class="space-y-4 text-xs font-semibold text-slate-500">
                    <input type="hidden" name="add_admin" value="1">

                    <div class="space-y-1">
                        <label class="block text-slate-500">ชื่อผู้ใช้งาน (Username) <span class="text-red-400">*</span></label>
                        <input type="text" name="admin_username" required placeholder="เช่นครูผู้ใช้ภาษาอังกฤษ (เช่น sumlee_p)" class="w-full rounded-xl border border-pink-100 p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-school-pink">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-500">รหัสลับในการล็อกอิน (Password) <span class="text-red-400">*</span></label>
                        <input type="password" name="admin_password" required placeholder="กำหนดรหัสผ่าน (อย่างน้อย 6 หลัก)..." class="w-full rounded-xl border border-pink-100 p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-school-pink">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-500">ชื่อจริง - นามสกุลจริง (ครู/เจ้าหน้าที่) <span class="text-red-400">*</span></label>
                        <input type="text" name="admin_name" required placeholder="ระบุ คำนำหน้าชื่อ ชื่อ-นามสกุลจริง" class="w-full rounded-xl border border-pink-100 p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-school-pink">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-slate-500">ระดับสิทธิ์ในการจัดการระบบ <span class="text-red-400">*</span></label>
                        <select name="admin_role" class="w-full rounded-xl border border-pink-100 p-3 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-school-pink cursor-pointer">
                            <option value="Administrator">Administrator (เข้าถึง ปลอมแปลง ตั้งค่าได้ครบถ้วน)</option>
                            <option value="Editor">Editor (สามารถเขียนข่าว อัปโหลดครู และรายงานวิชาการได้ปกติ)</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3 px-4 rounded-xl transition shadow-lg shadow-pink-500/15 text-xs flex items-center justify-center gap-1.5 mt-2">
                        🔑 ลงทะเบียนและให้สิทธิ์ผู้ดูแลระบบ
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- บล็อกคำแนะนำความปลอดภัย -->
        <div class="bg-amber-50 border border-amber-100 rounded-3xl p-5 text-slate-600 text-xs leading-relaxed space-y-2">
            <h5 class="font-heading font-black text-xs text-amber-850 flex items-center gap-1.5">
                <span>⚠️ ข้อปฏิบัติด้านความปลอดภัย</span>
            </h5>
            <ul class="list-disc list-inside space-y-1 text-[11px] text-amber-900 font-bold">
                <li>เมื่อระบบเปิดทำการใช้งานจริงแล้ว แนะนำให้แก้ไขรหัสผ่านของบัญชีแอดมินเริ่มต้น (<strong class="text-rose-700">admin</strong>) เพื่อสกัดการลักลอบเข้าระบบ</li>
                <li>ตั้งรหัสผ่านที่อย่างน้อยประกอบไปด้วยอักขระอักษรภาษาอังกฤษและตัวเลข</li>
                <li>สิทธิ์ <strong class="text-slate-800">Administrator</strong> สามารถดัดแปลงแก้ไขได้ทุกฟังก์ชันส่วนสิทธิ์ <strong class="text-slate-800">Editor</strong> มุ่งเน้นอัปโหลดข้อมูลข่าวสารเท่านั้น</li>
            </ul>
        </div>
        
    </div>

    <!-- ด้านขวา: ตารางรายชื่อบัญชีผู้ดูแลระบบทั้งหมดในระบบ (7/12 คอลัมน์) -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-3xl p-6 border border-pink-100 shadow-md space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5">
                        <span>👥 บัญชีผู้ดูแลหลังบ้านโรงเรียน</span>
                    </h4>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">ข้าราชการครูและบุคลากรผู้ได้รับพาสเวย์ดูแลระบบสารสนเทศ</p>
                </div>
                <span class="text-xs font-bold text-school-pink font-mono bg-pink-50 px-2.5 py-1 rounded-full whitespace-nowrap self-start sm:self-center">ทั้งหมด: <?php echo count($admins_list); ?> บัญชี</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-extrabold border-b border-slate-100 uppercase text-[10px] tracking-wider">
                            <th class="p-3">ID</th>
                            <th class="p-3">ชื่อใช้งาน (Username)</th>
                            <th class="p-3">ชื่อโปรไฟล์แอดมิน</th>
                            <th class="p-3 text-center">บทบาท (Role)</th>
                            <th class="p-3 text-right">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-bold text-slate-600">
                        <?php if (empty($admins_list)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">
                                    <p class="text-sm">❌ ไม่พบข้อมูลแอดมินในฐานข้อมูล</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins_list as $adm): ?>
                            <tr class="hover:bg-pink-50/20 transition-all">
                                <td class="p-3 text-slate-400 font-mono">
                                    #<?php echo $adm['id']; ?>
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 border border-slate-200/50 text-slate-700 font-mono text-[11px]">
                                        👤 <?php echo htmlspecialchars($adm['username']); ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-800">
                                        <?php echo htmlspecialchars($adm['name']); ?>
                                    </div>
                                    <div class="text-[9px] text-slate-400 font-normal">
                                        สร้างเมื่อ: <?php echo htmlspecialchars($adm['created_at']); ?>
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <?php if ($adm['role'] === 'Administrator' || $adm['id'] == 1): ?>
                                        <span class="inline-block bg-pink-100 text-school-pink text-[9px] font-extrabold px-2.5 py-1 rounded-full border border-pink-200/50">
                                            🛡️ Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block bg-teal-50 text-teal-700 text-[9px] font-extrabold px-2.5 py-1 rounded-full border border-teal-100">
                                            ✏️ Editor
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    <!-- ปุ่มแก้ไข -->
                                    <a href="admin.php?tab=admins&edit_admin=<?php echo $adm['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px] transition">✏️ แก้ไข</a>
                                    
                                    <!-- ปุ่มลบ จะแสดงปุ่มสำหรับลบบัญชีอื่น หากไม่ใช่ ID 1 และไม่ใช่บัญชีที่เปิดทำงานอยู่ -->
                                    <?php if ($adm['id'] == 1): ?>
                                        <span class="inline-block bg-slate-50 text-slate-400 text-[10px] px-2 py-1 rounded font-normal cursor-help" title="ผู้ดูแลระบบหลักเริ่มต้น ไม่สามารถนำออกได้">Super</span>
                                    <?php elseif ($adm['id'] == $_SESSION['admin_id']): ?>
                                        <span class="inline-block bg-slate-50 text-slate-400 text-[10px] px-2 py-1 rounded font-normal cursor-help" title="บัญชีของคุณที่กำลังใช้งานล็อกอินอยู่">ตัวเอง</span>
                                    <?php else: ?>
                                        <button onclick="if(confirm('คุณยืนยันที่ต้องการเพิกถอนและลบบัญชีผู้ดูแลระบบท่านนี้ใช่หรือไม่? แอดมินท่านนี้จะไม่สามารถเข้าใช้ระบบข่าวสารโรงเรียนได้อีก')) window.location.href='admin.php?tab=admins&action=delete_admin&id=<?php echo $adm['id']; ?>';" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px] transition cursor-pointer">🗑️ ลบ</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    
</div>
