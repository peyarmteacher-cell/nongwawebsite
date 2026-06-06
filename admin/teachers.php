<?php
/**
 * 🧑‍🏫 ส่วนการจัดการทำเนียบครูและบุคลากรทางการศึกษา (Teachers Panel)
 * ดำเนินการเพิ่มประวัติครู อัปโหลดรูปครู แฟ้มสะสมผลงาน ลิงก์รายงานข้อตกลง PA และการจัดเรียงระดับตำแหน่ง
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

// โหลดรายละเอียดครูที่ร้องขอแก้ไขประวัติ
$edit_teacher_item = null;
if (isset($_GET['edit_teacher'])) {
    $edit_teacher_id = intval($_GET['edit_teacher']);
    $stmt_edit_teacher = $pdo->prepare("SELECT * FROM `teachers` WHERE `id` = :id");
    $stmt_edit_teacher->execute(['id' => $edit_teacher_id]);
    $edit_teacher_item = $stmt_edit_teacher->fetch();
}

// คลี่ดึงประวัติครูทั้งหมดจากฐานข้อมูลเรียงตามลำดับหน้าโฮมเพจ
$teachers_list = $pdo->query("SELECT * FROM `teachers` ORDER BY `sort_order` ASC, `id` ASC")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- ด้านซ้าย: การเพิ่มคุณครูใหม่ / แก้ไขรายละเอียดครูปริศนา (5/12) -->
    <div class="lg:col-span-5 space-y-6">
        
        <?php if ($edit_teacher_item): ?>
        <!-- ฟอร์มแก้ไขประวัติคณะครูและบุคลากร -->
        <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 border border-slate-900 shadow space-y-4">
            <div class="flex items-center justify-between border-b border-indigo-850 pb-3">
                <h4 class="font-heading font-black text-xs sm:text-sm text-pink-300">✏️ แก้ไขข้อมูลประวัติอาจารย์/บุคลากร (#<?php echo $edit_teacher_item['id']; ?>)</h4>
                <a href="admin.php?tab=teachers" class="text-[10px] bg-indigo-900 border border-indigo-800 hover:bg-slate-800 p-1.5 rounded text-white font-bold">ยกเลิก</a>
            </div>
            <form action="admin.php?tab=teachers" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                <input type="hidden" name="edit_teacher_submit" value="1">
                <input type="hidden" name="teacher_id" value="<?php echo $edit_teacher_item['id']; ?>">

                <div class="space-y-1">
                    <label class="block text-indigo-200">ชื่อ-นามสกุลข้าราชการครู / ผู้บริหาร</label>
                    <input type="text" name="teacher_name" required value="<?php echo htmlspecialchars($edit_teacher_item['name']); ?>" class="w-full bg-indigo-900 border border-indigo-850 p-2.5 rounded-xl text-white outline-none focus:ring-1 focus:ring-school-pink">
                </div>
                
                <div class="space-y-1">
                    <label class="block text-indigo-200">ตำแหน่งหน้าที่และภาระงานสอน</label>
                    <input type="text" name="teacher_position" required value="<?php echo htmlspecialchars($edit_teacher_item['position']); ?>" class="w-full bg-indigo-900 border border-indigo-850 p-2.5 rounded-xl text-white outline-none focus:ring-1 focus:ring-school-pink">
                </div>
                
                <div class="space-y-1">
                    <label class="block text-indigo-200">วิทยฐานะและการประเมิน</label>
                    <select name="teacher_level" class="w-full bg-indigo-900 border border-indigo-850 text-white rounded-xl p-2.5 font-bold outline-none focus:ring-1 focus:ring-school-pink">
                        <option value="ผู้อำนวยการโรงเรียน (คศ.3)" <?php echo ($edit_teacher_item['level'] == 'ผู้อำนวยการโรงเรียน (คศ.3)') ? 'selected' : ''; ?>>ผู้อำนวยการโรงเรียน (คศ.3)</option>
                        <option value="ครูชำนาญการพิเศษ (คศ.3)" <?php echo ($edit_teacher_item['level'] == 'ครูชำนาญการพิเศษ (คศ.3)') ? 'selected' : ''; ?>>ครูชำนาญการพิเศษ (คศ.3)</option>
                        <option value="ครูชำนาญการ (คศ.2)" <?php echo ($edit_teacher_item['level'] == 'ครูชำนาญการ (คศ.2)') ? 'selected' : ''; ?>>ครูชำนาญการ (คศ.2)</option>
                        <option value="ครู คศ.1" <?php echo ($edit_teacher_item['level'] == 'ครู คศ.1') ? 'selected' : ''; ?>>ครู คศ.1</option>
                        <option value="ครูผู้ช่วย" <?php echo ($edit_teacher_item['level'] == 'ครูผู้ช่วย') ? 'selected' : ''; ?>>ครูผู้ช่วย</option>
                        <option value="พนักงานราชการ" <?php echo ($edit_teacher_item['level'] == 'พนักงานราชการ') ? 'selected' : ''; ?>>พนักงานราชการ</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-indigo-200">กลุ่มสาระวิชาการหลัก</label>
                        <select name="teacher_group" class="w-full bg-indigo-900 border border-indigo-850 text-white rounded-xl p-2.5 outline-none focus:ring-1 focus:ring-school-pink">
                            <option value="ผู้บริหาร" <?php echo ($edit_teacher_item['subject_group'] == 'ผู้บริหาร') ? 'selected' : ''; ?>>ผู้บริหาร</option>
                            <option value="วิชาการคณิตศาสตร์" <?php echo ($edit_teacher_item['subject_group'] == 'วิชาการคณิตศาสตร์') ? 'selected' : ''; ?>>คณิตศาสตร์</option>
                            <option value="วิทยาศาสตร์" <?php echo ($edit_teacher_item['subject_group'] == 'วิทยาศาสตร์') ? 'selected' : ''; ?>>วิทยาศาสตร์</option>
                            <option value="ภาษาไทย" <?php echo ($edit_teacher_item['subject_group'] == 'ภาษาไทย') ? 'selected' : ''; ?>>ภาษาไทย</option>
                            <option value="ระดับปฐมวัย" <?php echo ($edit_teacher_item['subject_group'] == 'ระดับปฐมวัย') ? 'selected' : ''; ?>>ปฐมวัย</option>
                            <option value="สุขศึกษาและพลศึกษา" <?php echo ($edit_teacher_item['subject_group'] == 'สุขศึกษาและพลศึกษา') ? 'selected' : ''; ?>>สุขศึกษาและพลศึกษา</option>
                            <option value="งานสอนทั่วไป" <?php echo ($edit_teacher_item['subject_group'] == 'งานสอนทั่วไป') ? 'selected' : ''; ?>>งานสอนทั่วไป</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-indigo-200">ลำดับจัดเรียงหน้าเว็บ</label>
                        <input type="number" name="teacher_order" value="<?php echo intval($edit_teacher_item['sort_order']); ?>" class="w-full bg-indigo-900 border border-indigo-850 p-2.5 rounded-xl text-white outline-none">
                    </div>
                </div>

                <!-- การทำอัพโหลดรูปถ่ายครูประจำการ -->
                <div class="p-3 bg-indigo-900 rounded-2xl border border-indigo-850 space-y-2 text-[10px]">
                    <label class="block font-bold text-indigo-200">1. รูปประจำตัวราชการครู (สำหรับหน้าทำเนียบ)</label>
                    <input type="file" name="teacher_image_file" accept="image/*" class="w-full text-[10px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-indigo-950 file:text-indigo-200">
                    <input type="text" name="teacher_image" value="<?php echo htmlspecialchars($edit_teacher_item['image_url']); ?>" placeholder="หรือระบุ URL รูปตรงภายนอก..." class="w-full bg-indigo-950 border border-indigo-900 p-2 rounded-lg text-white">
                </div>

                <!-- การตั้งค่า รายงานข้อตกลง PA ด้านใน และ แฟ้ม Portfolio -->
                <div class="p-3 bg-indigo-900 rounded-2xl border border-indigo-850 space-y-2 text-[10px]">
                    <label class="block font-bold text-indigo-200">2. รายงานผลข้อตกลง PA (Performance Agreement)</label>
                    <input type="file" name="teacher_pa_file" accept=".pdf,.doc,.docx,.zip" class="w-full text-[10px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-indigo-950 file:text-indigo-200">
                    <input type="text" name="teacher_pa_url" value="<?php echo htmlspecialchars($edit_teacher_item['pa_link_url'] ?? ''); ?>" placeholder="หรือวางลิงก์เก็บไฟล์เอกสาร PA (เช่น Google Drive)..." class="w-full bg-indigo-950 border border-indigo-900 p-2 rounded-lg text-white">
                </div>

                <div class="p-3 bg-indigo-900 rounded-2xl border border-indigo-850 space-y-2 text-[10px]">
                    <label class="block font-bold text-indigo-200">3. ลิงก์เก็บประพัทธ์ / ผลงานวิชาการ (Portfolio URL)</label>
                    <input type="file" name="teacher_portfolio_file" accept=".pdf,.doc,.docx,.zip,image/*" class="w-full text-[10px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-indigo-950 file:text-indigo-200">
                    <input type="text" name="teacher_portfolio_url" value="<?php echo htmlspecialchars($edit_teacher_item['portfolio_url'] ?? ''); ?>" placeholder="หรือลิ้งเชื่อมหน้าแฟ้มสะสมผลงาน..." class="w-full bg-indigo-950 border border-indigo-900 p-2 rounded-lg text-white">
                </div>

                <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                    💾 บันทึกและปรับปรุงประวัติตำแหน่งผลงานครู
                </button>
            </form>
        </div>
        <?php else: ?>
        <!-- ฟอร์มลงทะเบียนประวัติครูใหม่เข้าไปในตาราง -->
        <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
            <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                🧑‍🏫 เพิ่มรายข้าราชการครูและบุคลากรสถิติใหม่
            </h4>

            <form action="admin.php?tab=teachers" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                <input type="hidden" name="add_teacher" value="done">

                <div class="space-y-1">
                    <label class="block">ชื่อ-นามสกุลจริงข้าราชการครู</label>
                    <input type="text" name="teacher_name" required placeholder="เช่น นางพรวรา สวารีย์..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">ตำแหน่ง (หน้าที่สอน / งานรับผิดชอบ)</label>
                    <input type="text" name="teacher_position" required placeholder="เช่น ครูวิทยบริการ / สอนประจำชั้น..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">วิทยฐานะ</label>
                    <select name="teacher_level" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-black bg-white focus:ring-1 focus:ring-school-pink outline-none">
                        <option value="ผู้อำนวยการโรงเรียน (คศ.3)">ผู้อำนวยการโรงเรียน (คศ.3)</option>
                        <option value="ครูชำนาญการพิเศษ (คศ.3)">ครูชำนาญการพิเศษ (คศ.3)</option>
                        <option value="ครูชำนาญการ (คศ.2)">ครูชำนาญการ (คศ.2)</option>
                        <option value="ครู คศ.1">ครู คศ.1</option>
                        <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                        <option value="พนักงานราชการ" selected>พนักงานราชการ</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block">กลุ่มสาระการเรียนรู้</label>
                        <select name="teacher_group" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                            <option value="ผู้บริหาร">ผู้บริหาร</option>
                            <option value="วิชาการคณิตศาสตร์">วิชาการคณิตศาสตร์</option>
                            <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                            <option value="ภาษาไทย">ภาษาไทย</option>
                            <option value="ระดับปฐมวัย">ระดับปฐมวัย</option>
                            <option value="สุขศึกษาและพลศึกษา">สุขศึกษาและพลศึกษา</option>
                            <option value="งานสอนทั่วไป" selected>งานสอนทั่วไป</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block">ลำดับการจัดแถวแสดงบนเว็บ</label>
                        <input type="number" name="teacher_order" value="9" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none text-center">
                    </div>
                </div>

                <!-- การอัพพอร์ตไฟล์รูป  -->
                <div class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100/50 space-y-1.5 text-[10px]">
                    <label class="block font-black text-slate-800">1. รูปถ่ายประกอบวิชาชีพครู</label>
                    <input type="file" name="teacher_image_file" accept="image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                    <input type="text" name="teacher_image" placeholder="หรือสอดที่อยู่เป็น URL ตรง..." class="w-full rounded-lg border border-pink-100 p-1.5 text-xs outline-none">
                </div>

                <!-- ระบบ PA แแฝงข้อตกลง -->
                <div class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100/50 space-y-1.5 text-[10px]">
                    <label class="block font-black text-slate-800">2. รายงานข้อตกลง PA (Performance Agreement)</label>
                    <input type="file" name="teacher_pa_file" accept=".pdf,.doc,.docx,.zip" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                    <input type="text" name="teacher_pa_url" placeholder="หรือสอดที่อยู่ลิงก์รายงานผลการทำงาน..." class="w-full rounded-lg border border-pink-100 p-1.5 text-xs outline-none">
                </div>

                <div class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100/50 space-y-1.5 text-[10px]">
                    <label class="block font-black text-slate-800">3. แฟ้มผลงานวิชาการ (Portfolio Link / PDF)</label>
                    <input type="file" name="teacher_portfolio_file" accept=".pdf,.doc,.docx,.zip,image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                    <input type="text" name="teacher_portfolio_url" placeholder="หรือลิงก์เชื่อมแฟ้มเก็บเอกสารผลงานครู..." class="w-full rounded-lg border border-pink-100 p-1.5 text-xs outline-none">
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-2.5 rounded-xl transition shadow text-xs">
                    🧑‍🏫 บันทึกสถาปนาประวัติและข้อตกลงครู
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- ด้านขวา: ตารางคณะครู (7/12) -->
    <div class="lg:col-span-7 space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h4 class="font-heading font-black text-sm text-slate-800">
                    🧑‍🏫 สารสารบบคณะข้าราชการครูทำเนียบทั้งหมด
                </h4>
                <span class="text-xs font-bold text-school-pink font-mono bg-pink-50 px-2 py-1 rounded-full">ทั้งหมด: <?php echo count($teachers_list); ?> ท่าน</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                            <th class="p-3 w-16">รูปถ่าย</th>
                            <th class="p-3">ข้อมูลสังกัดกลุ่มสาระวิชาการ</th>
                            <th class="p-3 text-center w-16">จัดเรียง</th>
                            <th class="p-3 text-right">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php if (empty($teachers_list)): ?>
                            <tr><td colspan="4" class="p-5 text-center text-slate-450 font-bold">ยังไม่ข้ามมีข้อมูลทำเนียบคณะครูลงจัดตั้งในระบบ</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers_list as $tc): ?>
                            <tr>
                                <td class="p-3">
                                    <div class="w-10 h-10 rounded-full bg-cover bg-center border border-slate-200" style="background-image: url('<?php echo htmlspecialchars($tc['image_url']); ?>');"></div>
                                </td>
                                <td class="p-3">
                                    <div class="font-black text-slate-900 text-xs sm:text-sm"><?php echo htmlspecialchars($tc['name']); ?></div>
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5"><?php echo htmlspecialchars($tc['position']); ?></div>
                                    <div class="text-[9px] text-slate-400 font-bold mt-1.5 flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded bg-pink-50 text-school-pink font-bold"><?php echo htmlspecialchars($tc['level']); ?></span>
                                        <span class="text-slate-400">| สาระ: <?php echo htmlspecialchars($tc['subject_group']); ?></span>
                                    </div>
                                </td>
                                <td class="p-3 text-center font-bold text-slate-500"><?php echo intval($tc['sort_order']); ?></td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    <a href="admin.php?tab=teachers&edit_teacher=<?php echo $tc['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">✏️ แก้ไข</a>
                                    <a href="admin.php?tab=teachers&action=delete_teacher&id=<?php echo $tc['id']; ?>" onclick="return confirm('ยืนยันลบประวัติคุณครูท่านนี้และผลงานทิ้งใช่ไหม? ค่าสารสนเทศและแฟ้มงานจะว่างลงไปทันที')" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">🗑️ ลบ</a>
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
