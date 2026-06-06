<?php
/**
 * 📂 ส่วนการจัดการคลังเอกสารและแผนงานดาวน์โหลด (Downloads & Documents Panel)
 * ดำเนินการเพิ่มเอกสารจัดซื้อ จัดจ้าง แผนงานประจำปี ประกันคุณภาพ อัปโหลดไฟล์ ค้นหา และควบคุมไฟล์ดาวน์โหลด
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

// ค้นหาเพื่อโหลดคุณสมบัติสำหรับแก้ไขเอกสารคลัง
$edit_doc_item = null;
if (isset($_GET['edit_doc'])) {
    $edit_doc_id = intval($_GET['edit_doc']);
    $stmt_edit_doc = $pdo->prepare("SELECT * FROM `downloads` WHERE `id` = :id");
    $stmt_edit_doc->execute(['id' => $edit_doc_id]);
    $edit_doc_item = $stmt_edit_doc->fetch();
}

// ดำเนินการดึงเอกสารทั้งหมดมาแสดงในเครือข่ายตาราง
$downloads_list = $pdo->query("SELECT * FROM `downloads` ORDER BY `id` DESC")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- ด้านซ้าย: การเขียนเพิ่มเอกสาร/แก้เอกสาร (5/12) -->
    <div class="lg:col-span-5 space-y-6">
        
        <?php if ($edit_doc_item): ?>
        <!-- ฟอร์มแก้ไขเอกสาร -->
        <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 border border-slate-800 shadow space-y-4">
            <div class="flex items-center justify-between border-b border-indigo-900/50 pb-3">
                <h4 class="font-heading font-black text-xs sm:text-sm text-pink-300">✏️ แก้ไขสเปคเอกสารดาวน์โหลด (#<?php echo $edit_doc_item['id']; ?>)</h4>
                <a href="admin.php?tab=downloads" class="text-[10px] bg-indigo-900 border border-indigo-800 hover:bg-indigo-850 p-1.5 rounded text-white font-bold">ยกเลิก</a>
            </div>
            <form action="admin.php?tab=downloads" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                <input type="hidden" name="edit_doc_submit" value="1">
                <input type="hidden" name="doc_id" value="<?php echo $edit_doc_item['id']; ?>">

                <div class="space-y-1">
                    <label class="block">ชื่อเรียกจัดซื้อประมูล/เอกสารประกาศดาวน์โหลด</label>
                    <input type="text" name="doc_title" required value="<?php echo htmlspecialchars($edit_doc_item['title']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">ประเภทหมวดหมู่เอกสาร</label>
                    <select name="doc_category" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs font-bold text-white focus:ring-1 focus:ring-school-pink outline-none">
                        <option value="เอกสารทั่วไป" <?php echo ($edit_doc_item['category'] == 'เอกสารทั่วไป') ? 'selected' : ''; ?>>เอกสารทั่วไป</option>
                        <option value="แผนงานและนโยบาย" <?php echo ($edit_doc_item['category'] == 'แผนงานและนโยบาย') ? 'selected' : ''; ?>>แผนงานและนโยบาย</option>
                        <option value="ประกันคุณภาพ" <?php echo ($edit_doc_item['category'] == 'ประกันคุณภาพ') ? 'selected' : ''; ?>>ประกันคุณภาพ</option>
                        <option value="เอกสารครู" <?php echo ($edit_doc_item['category'] == 'เอกสารครู') ? 'selected' : ''; ?>>เอกสารครู</option>
                    </select>
                </div>

                <div class="space-y-2 border-t border-indigo-900 pt-2">
                    <label class="block font-bold text-indigo-200">1. อัปโหลดไฟล์เอกสารใหม่ขึ้นสู่ระบบ Server</label>
                    <input type="file" name="doc_file" class="w-full text-slate-400 text-[11px] mb-2 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:bg-indigo-900 file:text-indigo-200">
                </div>

                <div class="border-t border-indigo-900 pt-2 space-y-2 text-[10px]">
                    <label class="block text-slate-400">หรือระบุลิงก์จัดวางและปรับข้อมูลเองทางด้านล่าง:</label>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="space-y-1">
                            <label class="block font-bold text-indigo-200">ประเภทสกุล</label>
                            <input type="text" name="doc_type" value="<?php echo htmlspecialchars($edit_doc_item['file_type']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-2 text-white">
                        </div>
                        <div class="space-y-1">
                            <label class="block font-bold text-indigo-200">ขนาดไฟล์ (เช่น 1.2 MB)</label>
                            <input type="text" name="doc_size" value="<?php echo htmlspecialchars($edit_doc_item['file_size']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-2 text-white">
                        </div>
                    </div>
                    <div class="space-y-1 text-xs">
                        <label class="block font-bold text-indigo-200">ที่อยู่ดาวน์โหลดตรง (URL หรือระบุ # ไว้)</label>
                        <input type="text" name="doc_url" value="<?php echo htmlspecialchars($edit_doc_item['file_url']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-2 text-white">
                    </div>
                </div>

                <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded text-xs shadow">
                    💾 บันทึกแก้ไขคู่มือและไฟล์สิทธิดาวน์โหลด
                </button>
            </form>
        </div>
        <?php else: ?>
        <!-- ฟอร์มเพิ่มเอกสารจดทะเบียนใหม่ -->
        <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
            <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                📂 ประกาศเผยแพร่เอกสารดาวน์โหลด เพิ่มเติม
            </h4>
            
            <form action="admin.php?tab=downloads" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                <input type="hidden" name="add_doc" value="done">

                <div class="space-y-1">
                    <label class="block">ชื่อเรียกจัดซื้อประมูล/เอกสารแจ้งจัดประชาสัมพันธ์</label>
                    <input type="text" name="doc_title" required placeholder="พิมพ์ข้อความชื่อเรียกลายละเอียดชุดเอกสาร..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">ประเภทหมวดหมู่เอกสารคลัง</label>
                    <select name="doc_category" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                        <option value="เอกสารทั่วไป">เอกสารทั่วไป</option>
                        <option value="แผนงานและนโยบาย">แผนงานและนโยบาย</option>
                        <option value="ประกันคุณภาพ">ประกันคุณภาพ</option>
                        <option value="เอกสารครู">เอกสารครู</option>
                    </select>
                </div>

                <div class="space-y-2 border-t border-pink-100/35 pt-2">
                    <label class="block text-slate-800 font-bold">1. อัปโหลดตัวไฟล์จริงขึ้นจัดเก็บทางเซิร์ฟเวอร์</label>
                    <input type="file" name="doc_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                </div>

                <div class="border-t border-pink-100/35 pt-2 space-y-2 text-[10px]">
                    <label class="block text-slate-400">หรือระบุค่าระบุมูลและที่อยู่ดาวน์โหลดเอง:</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs">ประเภทไฟล์</label>
                            <select name="doc_type" class="w-full rounded-xl border border-pink-100 p-1.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                                <option value="PDF">PDF</option>
                                <option value="WORD">WORD</option>
                                <option value="EXCEL">EXCEL</option>
                                <option value="ZIP">ZIP</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs">ขนาดพื้นที่ไฟล์ (เช่น 1.2 MB)</label>
                            <input type="text" name="doc_size" value="1.2 MB" class="w-full rounded-xl border border-pink-100 p-1.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                        </div>
                    </div>

                    <div class="space-y-1 text-xs">
                        <label class="block">ลิงค์จัดเก็บไฟล์ (URL หรือใส่เครื่องหมาย # ค้ำไว้)</label>
                        <input type="text" name="doc_url" value="#" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3 rounded-2xl transition shadow text-xs">
                    📁 บันทึกข้อมูลเข้าสู่ศูนย์ดาวน์โหลดประหยัด
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- ด้านขวา: แสดงตารางรายชื่อเอกสารดาวน์โหลดเอกสาร (7/12) -->
    <div class="lg:col-span-7 space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h4 class="font-heading font-black text-sm text-slate-800">
                    📂 แฟ้มคลังรายการเอกสารจัดซื้อ จัดจ้างและดาวน์โหลดหลัก
                </h4>
                <span class="text-xs font-bold text-school-pink font-mono bg-pink-50 px-2 py-1 rounded-full">ทั้งหมด: <?php echo count($downloads_list); ?> แฟ้ม</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                            <th class="p-3">ชื่อสารเรียกประกวดราคา / ทรัพย์สิน</th>
                            <th class="p-3">หมวดประเภท</th>
                            <th class="p-3">นามสกลไฟล์</th>
                            <th class="p-3 text-right">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php if (empty($downloads_list)): ?>
                            <tr><td colspan="4" class="p-5 text-center text-slate-450 font-bold">ยังไม่ได้รับเอกสารหรือสารสนเทศดาวน์โหลดใดๆ ในฐานข้อมูล</td></tr>
                        <?php else: ?>
                            <?php foreach ($downloads_list as $dl): ?>
                            <tr>
                                <td class="p-3 text-slate-900 font-bold">
                                    <div class="line-clamp-2 max-w-xs text-xs"><?php echo htmlspecialchars($dl['title']); ?></div>
                                    <div class="text-[9px] text-slate-400 mt-1 font-mono">📅 วันที่: <?php echo thaiDate($dl['uploaded_date'] ?? ''); ?> | ขนาด: <?php echo htmlspecialchars($dl['file_size']); ?></div>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[9px] bg-slate-100 text-slate-600 font-bold">
                                        <?php echo htmlspecialchars($dl['category']); ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="font-black text-[9px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 uppercase">
                                        <?php echo htmlspecialchars($dl['file_type']); ?>
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    <a href="admin.php?tab=downloads&edit_doc=<?php echo $dl['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">✏️ แก้ไข</a>
                                    <a href="admin.php?tab=downloads&action=delete_doc&id=<?php echo $dl['id']; ?>" onclick="return confirm('ยืนยันลบรายละเอียดไฟล์นี้ใช่ไหม? การแก้ไขจะไม่ย้อนกลับใดๆ')" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">🗑️ ลบ</a>
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
