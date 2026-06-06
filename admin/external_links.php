<?php
/**
 * 🔗 ส่วนจัดการสื่อเทคโนโลยีและระบบงานบริการออนไลน์ของคุณครู (Media & Work Systems Links Management)
 * รองรับการเพิ่ม, แก้ไข, ลบข้อมูลลิงก์ แหล่งเรียนรู้ สารสนเทศวิชาการ คอร์สศึกษา และเครื่องมือครู
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}

$success_link_alert = '';
$err_link_alert = '';

// ก. ดำเนินการลบข้อมูลลิงก์
if (isset($_GET['del_link']) && !empty($_GET['del_link'])) {
    $del_id = intval($_GET['del_link']);
    try {
        $del_stmt = $pdo->prepare("DELETE FROM `external_links` WHERE `id` = :id");
        $del_stmt->execute(['id' => $del_id]);
        $success_link_alert = '🗑️ ลบสื่อและระบบงานออนไลน์ เรียบร้อยแล้ว!';
    } catch (Exception $e) {
        $err_link_alert = 'เกิดข้อผิดพลาดในการลบลิงก์: ' . $e->getMessage();
    }
}

// ข. ดำเนินการเพิ่มข้อมูลสื่อ/ระบบงานใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link_btn'])) {
    $title = cleanInput($_POST['link_title'] ?? '');
    $description = cleanInput($_POST['link_desc'] ?? '');
    $url_link = cleanInput($_POST['link_url'] ?? '');
    $category = cleanInput($_POST['link_cat'] ?? 'สื่อการเรียนรู้');
    $image_url = cleanInput($_POST['link_image_url'] ?? '');

    // รองรับการอัปโหลดไฟล์ภาพ
    if (isset($_FILES['link_image_file']) && $_FILES['link_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['link_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) $image_url = $uploaded_image;
    }

    if (empty($image_url)) {
        $image_url = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&q=80&w=300';
    }

    if (empty($title) || empty($url_link)) {
        $err_link_alert = '❌ กรุณากรอกชื่อเรื่อง และ ที่อยู่ลิงก์เชื่อมโยงให้ครบถ้วน';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `external_links` (`title`, `description`, `url_link`, `image_url`, `category`) VALUES (:title, :desc, :url, :img, :cat)");
            $stmt->execute([
                'title' => $title,
                'desc' => $description,
                'url' => $url_link,
                'img' => $image_url,
                'cat' => $category
            ]);
            $success_link_alert = '🎉 เพิ่มข้อมูลสื่อและระบบงานครูเรียบร้อยสรรพ!';
        } catch (Exception $e) {
            $err_link_alert = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
        }
    }
}

// ค. ดำเนินการแก้ไขข้อมูลสื่อ/ระบบงานเดิม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_link_submit_btn'])) {
    $id = intval($_POST['link_id'] ?? 0);
    $title = cleanInput($_POST['link_title'] ?? '');
    $description = cleanInput($_POST['link_desc'] ?? '');
    $url_link = cleanInput($_POST['link_url'] ?? '');
    $category = cleanInput($_POST['link_cat'] ?? 'สื่อการเรียนรู้');
    $image_url = cleanInput($_POST['link_image_url'] ?? '');

    // รองรับการอัปโหลดไฟล์ภาพ
    if (isset($_FILES['link_image_file']) && $_FILES['link_image_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadFileToServer($_FILES['link_image_file'], 'jpg,jpeg,png,gif');
        if ($uploaded_image) $image_url = $uploaded_image;
    }

    if (empty($title) || empty($url_link)) {
        $err_link_alert = '❌ กรุณาระบุหัวชื่อเรื่องการอ้างอิง และลิงก์ปลายทางให้ถูกต้องครบถ้วน';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE `external_links` SET `title` = :title, `description` = :desc, `url_link` = :url, `image_url` = :img, `category` = :cat WHERE `id` = :id");
            $stmt->execute([
                'title' => $title,
                'desc' => $description,
                'url' => $url_link,
                'img' => $image_url,
                'cat' => $category,
                'id' => $id
            ]);
            $success_link_alert = '💾 แก้ไขอัปเดตข้อมูลสื่อและระบบครูเชื่อมโยงสำเร็จพรั่งพร้อม!';
        } catch (Exception $e) {
            $err_link_alert = 'ไม่สามารถแก้ไขข้อมูลสื่อ/ระบบงานได้: ' . $e->getMessage();
        }
    }
}

// ง. โหลดรายการลิงก์ทั้งหมด
$links_list = $pdo->query("SELECT * FROM `external_links` ORDER BY `id` DESC")->fetchAll();

// จ. ตรวจดูว่ามีการกดแก้ไขตัวไหนอยู่หรือไม่
$edit_link_item = null;
if (isset($_GET['edit_link'])) {
    $edit_link_id = intval($_GET['edit_link']);
    $stmt_edit = $pdo->prepare("SELECT * FROM `external_links` WHERE `id` = :id");
    $stmt_edit->execute(['id' => $edit_link_id]);
    $edit_link_item = $stmt_edit->fetch();
}
?>

<!-- แสดง Alerts แจ้งเตือนย่อยในแท็บ -->
<?php if (!empty($success_link_alert)): ?>
    <div class="bg-green-50 rounded-2xl p-4 text-green-700 text-xs font-bold border border-green-100 flex items-center gap-2 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <?php echo $success_link_alert; ?>
    </div>
<?php endif; ?>

<?php if (!empty($err_link_alert)): ?>
    <div class="bg-red-50 rounded-2xl p-4 text-red-600 text-xs font-bold border border-red-100 flex items-center gap-2 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        <?php echo $err_link_alert; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
    
    <!-- ด้านซ้าย: ฟอร์มเพิ่ม/แก้ไข (5/12) -->
    <div class="lg:col-span-5 space-y-6">
        
        <?php if ($edit_link_item): ?>
            <!-- ฟอร์มแก้ไขข้อมูลลิงก์ -->
            <div class="bg-indigo-950 text-slate-100 rounded-3xl p-6 border border-slate-800 shadow-md space-y-4">
                <div class="flex items-center justify-between border-b border-indigo-900/50 pb-3">
                    <h4 class="font-heading font-black text-xs sm:text-sm text-pink-300">✏️ แก้ไขสื่อ / ระบบงานครู (#<?php echo $edit_link_item['id']; ?>)</h4>
                    <a href="admin.php?tab=links" class="text-[10px] bg-indigo-900 border border-indigo-800 hover:bg-indigo-850 p-1.5 rounded text-white font-bold">ยกเลิก</a>
                </div>
                <form action="admin.php?tab=links" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-300">
                    <input type="hidden" name="edit_link_submit_btn" value="1">
                    <input type="hidden" name="link_id" value="<?php echo $edit_link_item['id']; ?>">

                    <div class="space-y-1">
                        <label class="block">หัวเรื่อง / ชื่อสื่อและระบบงาน</label>
                        <input type="text" name="link_title" required value="<?php echo htmlspecialchars($edit_link_item['title']); ?>" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="block">คำอธิบายเพิ่มเติมสั้นๆ</label>
                        <textarea name="link_desc" rows="2" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none"><?php echo htmlspecialchars($edit_link_item['description']); ?></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block">ลิงก์ URL ปลายทาง</label>
                        <input type="url" name="link_url" required value="<?php echo htmlspecialchars($edit_link_item['url_link']); ?>" placeholder="https://..." class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs text-white focus:ring-1 focus:ring-school-pink outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="block">ประเภทหมวดหมู่</label>
                        <select name="link_cat" class="w-full rounded-xl bg-indigo-900 border border-indigo-850 p-2.5 text-xs font-bold text-white focus:ring-1 focus:ring-school-pink outline-none">
                            <option value="สื่อการเรียนรู้" <?php echo ($edit_link_item['category'] == 'สื่อการเรียนรู้') ? 'selected' : ''; ?>>สื่อการเรียนรู้</option>
                            <option value="งานครูและลิงก์หน่วยงาน" <?php echo ($edit_link_item['category'] == 'งานครูและลิงก์หน่วยงาน') ? 'selected' : ''; ?>>งานครูและลิงก์หน่วยงาน</option>
                            <option value="บริการออนไลน์" <?php echo ($edit_link_item['category'] == 'บริการออนไลน์') ? 'selected' : ''; ?>>บริการออนไลน์</option>
                            <option value="แหล่งสืบค้นเรียนรู้" <?php echo ($edit_link_item['category'] == 'แหล่งสืบค้นเรียนรู้') ? 'selected' : ''; ?>>แหล่งสืบค้นเรียนรู้</option>
                        </select>
                    </div>

                    <div class="space-y-2 border-t border-indigo-900 pt-2">
                        <label class="block font-bold text-indigo-200">1. อัปโหลดภาพหน้าปกใหม่</label>
                        <input type="file" name="link_image_file" accept="image/*" class="w-full text-slate-400 text-[11px] mb-2 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:bg-indigo-900 file:text-indigo-200">
                    </div>

                    <div class="space-y-1 text-xs">
                        <label class="block text-indigo-200 font-bold">หรือระบุภาพหน้าปกผ่าน URL:</label>
                        <input type="text" name="link_image_url" value="<?php echo htmlspecialchars($edit_link_item['image_url']); ?>" class="w-full rounded bg-indigo-900 border border-indigo-850 p-2 text-white">
                    </div>

                    <button type="submit" class="w-full bg-school-pink hover:bg-school-pink-dark text-white font-black py-2.5 rounded text-xs shadow">
                        💾 บันทึกแก้ไขสื่อ / ระบบงานครู
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- ฟอร์มเพิ่มข้อมูลลิงก์ใหม่ -->
            <div class="bg-white rounded-3xl p-6 border border-pink-50 shadow-sm space-y-4">
                <h4 class="font-heading font-black text-sm text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-3">
                    🔗 เพิ่มลิงก์สื่อการเรียนรู้ หรือ ระบบงานครู
                </h4>
                
                <form action="admin.php?tab=links" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold text-slate-600">
                    <input type="hidden" name="add_link_btn" value="1">

                    <div class="space-y-1">
                        <label class="block">ชื่อเรียกแหล่งเรียนรู้ / งานระบบ</label>
                        <input type="text" name="link_title" required placeholder="เช่น OBEC Content Center, สารสนเทศ EMIS..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="block">คำอธิบายรายละเอียด</label>
                        <textarea name="link_desc" rows="2" placeholder="อธิบายสังเขปรายละเอียดแหล่งเรียนรู้นี้..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block">ที่อยู่ URL ปลายทาง (พิมพ์ https:// ด้วย)</label>
                        <input type="url" name="link_url" required placeholder="https://..." class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="block">ประเภทหมวดหมู่</label>
                        <select name="link_cat" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-bold bg-white focus:ring-1 focus:ring-school-pink outline-none">
                            <option value="สื่อการเรียนรู้">สื่อการเรียนรู้</option>
                            <option value="งานครูและลิงก์หน่วยงาน">งานครูและลิงก์หน่วยงาน</option>
                            <option value="บริการออนไลน์">บริการออนไลน์</option>
                            <option value="แหล่งสืบค้นเรียนรู้">แหล่งสืบค้นเรียนรู้</option>
                        </select>
                    </div>

                    <div class="space-y-2 border-t border-pink-100/35 pt-2">
                        <label class="block text-slate-800 font-bold">1. อัปโหลดภาพสัญลักษณ์/หน้าปก</label>
                        <input type="file" name="link_image_file" accept="image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                    </div>

                    <div class="space-y-1 text-xs">
                        <label class="block">หรือใส่ที่อยู่รูปภาพ URL แทนการอัปโหลด:</label>
                        <input type="text" name="link_image_url" placeholder="เช่น https://..." class="w-full rounded-xl border border-pink-100 p-2 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
                    </div>

                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3 rounded-2xl transition shadow text-xs">
                        🚀 เพิ่มลงระบบคลังสื่อเทคโนโลยี
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- ด้านขวา: แสดงตารางแสดงลิงก์ทั้งหมดที่มีอยู่ (7/12) -->
    <div class="lg:col-span-7 space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <h4 class="font-heading font-black text-sm text-slate-800 border-b border-slate-100 pb-3">
                🔗 รายการสื่อเทคโนโลยีและระบบงานบริการออนไลน์ ทั้งหมดในระบบ
            </h4>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-slate-600 font-bold">
                            <th class="p-3">หน้าปก</th>
                            <th class="p-3">หัวข้อ / คำอธิบาย</th>
                            <th class="p-3">หมวดหมู่</th>
                            <th class="p-3 text-right">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($links_list)): ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-400 font-bold">ไม่มีข้อมูลระบบงานออนไลน์ในระบบ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($links_list as $link): ?>
                                <tr class="hover:bg-slate-50/50 transition whitespace-nowrap">
                                    <td class="p-3 w-16 whitespace-normal">
                                        <div class="w-12 h-12 rounded-lg overflow-hidden border border-slate-100">
                                            <img src="<?php echo htmlspecialchars($link['image_url']); ?>" alt="Cover" class="w-full h-full object-cover shadow-sm" referrerPolicy="no-referrer">
                                        </div>
                                    </td>
                                    <td class="p-3 space-y-1 whitespace-normal max-w-xs sm:max-w-md">
                                        <div class="font-bold text-slate-800"><?php echo htmlspecialchars($link['title']); ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($link['description']); ?></div>
                                        <div class="text-[9px] font-mono text-indigo-500 break-all select-all"><?php echo htmlspecialchars($link['url_link']); ?></div>
                                    </td>
                                    <td class="p-3 whitespace-normal">
                                        <span class="inline-block px-2 py-0.5 bg-pink-50 text-school-pink font-extrabold text-[9px] rounded-md">
                                            <?php echo htmlspecialchars($link['category']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right space-x-1.5 align-middle select-none">
                                        <a href="admin.php?tab=links&edit_link=<?php echo $link['id']; ?>" class="text-[10px] uppercase font-bold text-slate-600 hover:text-indigo-600 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded transition border border-slate-100">แก้ไข</a>
                                        <a href="admin.php?tab=links&del_link=<?php echo $link['id']; ?>" onclick="return confirm('⚠️ แน่ใจหรือไม่ว่าต้องการลบลิงก์สื่อ/ระบบงานนี้ออกจากฐานข้อมูล?')" class="text-[10px] uppercase font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded transition border border-rose-100">ลบ</a>
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
