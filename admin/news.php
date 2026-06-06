<?php
/**
 * 📰 ส่วนการจัดการข่าวสารและกิจกรรมประชาสัมพันธ์ (News & PR Panel)
 * ดำเนินการเพิ่มข่าว ส่งรูปข่าวปะกอบ คลังข่าวประชาสัมพันธ์ ปักหมุด และลบข่าวสาร
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

// โหลดข้อมูลสำหรับกรณีการแก้ไขข่าวสารรายไอดี
$edit_news_item = null;
if (isset($_GET['edit_news'])) {
    $edit_id = intval($_GET['edit_news']);
    $stmt_edit = $pdo->prepare("SELECT * FROM `news` WHERE `id` = :id");
    $stmt_edit->execute(['id' => $edit_id]);
    $edit_news_item = $stmt_edit->fetch();
}

// นับข่าวสารและดึงข้อมูลข่าวสารทั้งหมดมาแสดงในตาราง
$news_list = $pdo->query("SELECT * FROM `news` ORDER BY `sticky_flag` DESC, `date` DESC, `id` DESC")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- ด้านซ้าย: ฟอร์ม เขียน/แก้ไขข่าวประชาสัมพันธ์ (5/12) -->
    <div class="lg:col-span-5 space-y-6">
        
        <?php if ($edit_news_item): ?>
        <!-- ฟอร์ม: ดำเนินการแก้ไขข่าวสารประชาสัมพันธ์ที่มีในแฟ้มคลัง -->
        <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 sm:p-8 border border-indigo-900 shadow-md space-y-5">
            <div class="border-b border-indigo-900 pb-3 flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-heading font-black text-pink-300 flex items-center gap-1.5 leading-none">
                        ✏️ แก้ไขข่าวสารประชาสัมพันธ์ (#<?php echo $edit_news_item['id']; ?>)
                    </h3>
                    <p class="text-[9px] text-indigo-300 mt-1 uppercase">แก้ไขสาส์นเนื้อความประชาสัมพันธ์ฉบับล่าสุด</p>
                </div>
                <a href="admin.php?tab=news" class="bg-indigo-900 text-white hover:bg-slate-800 text-[10px] font-bold px-2.5 py-1.5 rounded-lg border border-indigo-800">ยกเลิก</a>
            </div>

            <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                <input type="hidden" name="edit_news_submit" value="1">
                <input type="hidden" name="news_id" value="<?php echo $edit_news_item['id']; ?>">

                <div class="space-y-1">
                    <label class="block text-indigo-200">หัวข้อข่าวประชาสัมพันธ์</label>
                    <input type="text" name="news_title" required value="<?php echo htmlspecialchars($edit_news_item['title']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block text-indigo-200">หมวดหมู่รายงานข่าวสาร</label>
                    <select name="news_category" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs font-bold text-white focus:ring-1 focus:ring-school-pink outline-none">
                        <option value="ประชาสัมพันธ์ทั่วไป" <?php echo ($edit_news_item['category'] === 'ประชาสัมพันธ์ทั่วไป') ? 'selected' : ''; ?>>ประชาสัมพันธ์ทั่วไป</option>
                        <option value="ข่าวกิจกรรม" <?php echo ($edit_news_item['category'] === 'ข่าวกิจกรรม') ? 'selected' : ''; ?>>ข่าวกิจกรรม</option>
                        <option value="ประชุมและวิชาการ" <?php echo ($edit_news_item['category'] === 'ประชุมและวิชาการ') ? 'selected' : ''; ?>>ประชุมและวิชาการ</option>
                        <option value="ผลงานครูและนักเรียน" <?php echo ($edit_news_item['category'] === 'ผลงานครูและนักเรียน') ? 'selected' : ''; ?>>ผลงานครูและนักเรียน</option>
                    </select>
                </div>

                <div class="space-y-1 pb-1">
                    <label class="block text-indigo-250 font-bold mb-1">รูปหน้าปกข่าวสาร</label>
                    <input type="file" name="news_image_file" accept="image/*" class="w-full text-[10px] text-slate-400 mb-1.5 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-900 file:text-indigo-200">
                    <input type="text" name="news_image" value="<?php echo htmlspecialchars($edit_news_item['image_url']); ?>" placeholder="หรือระบุ URL รูปตรง..." class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block text-indigo-250">คำอธิบายย่อยโดยย่อ สำหรับแสดงบนหน้าหลัก (Summary)</label>
                    <input type="text" name="news_summary" value="<?php echo htmlspecialchars($edit_news_item['summary']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block text-indigo-250 font-medium">เนื้อหาข่าวสารแบบละเอียดฉบับเต็ม</label>
                    <textarea name="news_content" required rows="6" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none"><?php echo htmlspecialchars($edit_news_item['content']); ?></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="news_sticky" id="edit_news_sticky" value="1" <?php echo ($edit_news_item['sticky_flag'] == 1) ? 'checked' : ''; ?> class="h-4 w-4 bg-indigo-900 rounded border-indigo-800 text-school-pink">
                    <label for="edit_news_sticky" class="text-[11px] font-bold text-slate-300 select-none">📌 ปักหมุดข่าวชิ้นนี้ไว้ด้านบนสุดเป็นอันดับแรก</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3 rounded-xl transition shadow text-xs">
                        💾 บันทึกและปรับแก้ไขสารประชาสัมพันธ์
                    </button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <!-- ฟอร์ม: ป้อนจดหมายประกาศข่าวประชาสัมพันธ์ชิ้นใหม่ -->
        <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-heading font-black text-slate-800 flex items-center gap-1.5 leading-none">
                    <span class="p-2 bg-pink-100 text-school-pink rounded-xl">📰</span>
                    เขียนข่าวประชาสัมพันธ์ชิ้นใหม่
                </h3>
                <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">พิมพ์เรื่องราวและภาพกิจกรรมเพื่ออัปลงระบบหน้าเว็บหลัก</p>
            </div>

            <form action="admin.php?tab=news" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                <input type="hidden" name="add_news" value="done">

                <div class="space-y-1">
                    <label class="block">หัวข้อข่าวประชาสัมพันธ์</label>
                    <input type="text" name="news_title" required placeholder="พิมพ์หัวข้อข่าวเด่น..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">หมวดหมู่รายงานข่าวสาร</label>
                    <select name="news_category" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                        <option value="ประชาสัมพันธ์ทั่วไป">ประชาสัมพันธ์ทั่วไป</option>
                        <option value="ข่าวกิจกรรม">ข่าวกิจกรรม</option>
                        <option value="ประชุมและวิชาการ">ประชุมและวิชาการ</option>
                        <option value="ผลงานครูและนักเรียน">ผลงานครูและนักเรียน</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-slate-800 font-bold">อัปโหลดภาพประกอบ/ภาพหน้าปก (Image Cover)</label>
                    <input type="file" name="news_image_file" accept="image/*" class="w-full text-[10px] text-slate-500 mb-1.5 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                    <input type="text" name="news_image" placeholder="หรือสอดที่อยู่รูปภาพตรง (URL)..." class="w-full rounded-xl border border-pink-100 p-2 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">คำเกริ่นย่อเนื้อเรื่องข่าวด้านหน้า (Summary)</label>
                    <input type="text" name="news_summary" placeholder="ระบุข้อความ 1-2 บรรทัด เช่น ภาพประมวลผลการจัดเตรียม..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                </div>

                <div class="space-y-1">
                    <label class="block">เนื้อข่าวสารแบบละเอียดเต็มฉบับ</label>
                    <textarea name="news_content" required rows="5" placeholder="พิมพ์บรรยายหัวข้อและเนื้อเรื่อง..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="news_sticky" id="add_news_sticky" value="1" class="h-4 w-4 bg-white rounded border-pink-100 text-school-pink">
                    <label for="add_news_sticky" class="text-[11px] font-bold text-slate-700 select-none">📌 ปักหมุดข่าวชิ้นนี้ไว้ด้านบนสุดเป็นอันดับแรก</label>
                </div>

                <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-3.5 rounded-2xl transition shadow text-xs">
                    🚀 บันทึกและเผยแพร่ออกสู่หน้าแรก
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <!-- ด้านขวา: แสดงตารางรายการข่าวประชาสัมพันธ์ทั้งหมดที่มีอยู่ในระบบ (7/12) -->
    <div class="lg:col-span-7 space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h4 class="font-heading font-black text-sm text-slate-800">
                    📰 สารบบข้อมูลข่าวประชาสัมพันธ์ ประชาคมโรงเรียน
                </h4>
                <span class="text-xs font-bold text-school-pink font-mono bg-pink-50 px-2 py-1 rounded-full">ทั้งหมด: <?php echo count($news_list); ?> ชิ้น</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                            <th class="p-3 w-16">หน้าปก</th>
                            <th class="p-3">หัวเรื่องประกาศ / รายละเอียด</th>
                            <th class="p-3 w-28">ประเภทหมวด</th>
                            <th class="p-3 text-right w-36">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php if (empty($news_list)): ?>
                            <tr><td colspan="4" class="p-5 text-center text-slate-450 font-bold">ยังไม่มีข้อมูลข่าวสารประกาศในระบบขณะนี้</td></tr>
                        <?php else: ?>
                            <?php foreach ($news_list as $nw): ?>
                            <tr>
                                <td class="p-3">
                                    <div class="w-12 h-12 rounded-lg bg-cover bg-center border border-slate-100 shadow-sm" style="background-image: url('<?php echo htmlspecialchars($nw['image_url']); ?>');"></div>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 line-clamp-1 max-w-xs text-xs">
                                        <?php if($nw['sticky_flag']): ?><span class="text-rose-600 font-extrabold mr-1">📌 [ปักหมุด]</span><?php endif; ?>
                                        <?php echo htmlspecialchars($nw['title']); ?>
                                    </div>
                                    <div class="text-[10px] text-slate-450 line-clamp-1 mt-0.5"><?php echo htmlspecialchars($nw['summary']); ?></div>
                                    <div class="text-[9px] text-slate-400 mt-1 font-mono">📅 โพสต์เมื่อ: <?php echo thaiDate($nw['date']); ?></div>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[9px] bg-pink-50 text-school-pink font-extrabold uppercase">
                                        <?php echo htmlspecialchars($nw['category']); ?>
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                    <a href="admin.php?tab=news&edit_news=<?php echo $nw['id']; ?>" class="inline-block bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">✏️ แก้ไข</a>
                                    <a href="admin.php?tab=news&action=delete_news&id=<?php echo $nw['id']; ?>" onclick="return confirm('ยืนยันลบข่าวสารชุดนี้ใช่หรือไม่? การเปลี่ยนแปลงจะไม่สามารถแก้ไขกลับคืนได้')" class="inline-block bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-bold text-[10px]">🗑️ ลบ</a>
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
