<?php
/**
 * ⚙️ ส่วนการตั้งค่าทั่วไปขององค์กร (General Settings Panel)
 * ใช้จัดการข้อมูลพื้นฐานโรงเรียน สื่อโลโก้ แบนเนอร์ และสาส์นผู้บริหาร
 */
if (!defined('DB_HOST')) {
    exit('No direct script access allowed');
}
?>
<div class="bg-white rounded-3xl p-6 sm:p-8 border border-pink-50 shadow-sm space-y-6">
    <div class="border-b border-slate-100 pb-4">
        <h3 class="text-lg font-heading font-black text-slate-800 flex items-center gap-2 leading-none">
            <span class="p-2 bg-pink-100 text-school-pink rounded-xl">
                ⚙️
            </span>
            ตั้งค่าแก้ไข ข้อมูลทั่วไปของสถานศึกษา
        </h3>
        <p class="text-[10px] text-slate-400 font-semibold uppercase mt-2">แก้ไขข้อมูลสถานศึกษาและบันทึกข้อมูลเพื่อนำไปแสดงผลบนหน้าต่างเว็บไซต์</p>
    </div>

    <form action="admin.php?tab=general" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold text-slate-600">
        <input type="hidden" name="update_settings" value="done">

        <div class="space-y-1">
            <label class="block">ชื่อโรงเรียนอย่างเป็นทางการ</label>
            <input type="text" name="school_name" required value="<?php echo htmlspecialchars($settings['school_name']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1">
            <label class="block">ชื่อย่อสำหรับระบบย่อ</label>
            <input type="text" name="short_name" required value="<?php echo htmlspecialchars($settings['short_name']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1 sm:col-span-2">
            <label class="block">คำขวัญประจำโรงเรียน (School Motto)</label>
            <input type="text" name="school_motto" required value="<?php echo htmlspecialchars($settings['school_motto'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <!-- ข้อความแบนเนอร์หลักและสโลแกนในใจ -->
        <div class="space-y-1 sm:col-span-2">
            <label class="block text-slate-700 font-bold">ข้อความแบนเนอร์หลัก (พาดหัวเด่นหน้าแรก)</label>
            <input type="text" name="banner_title" value="<?php echo htmlspecialchars($settings['banner_title'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-semibold focus:ring-1 focus:ring-school-pink outline-none">
        </div>
        <div class="space-y-1 sm:col-span-2">
            <label class="block text-slate-700 font-bold">คำอธิบายย่อยใต้ข้อความแบนเนอร์หลัก (Subtitle)</label>
            <input type="text" name="banner_subtitle" value="<?php echo htmlspecialchars($settings['banner_subtitle'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <!-- สารและกล่องข้อความจากผู้บริหาร -->
        <div class="sm:col-span-2 border-t border-slate-100 pt-4 mt-2 space-y-4">
            <label class="block text-slate-800 font-black text-xs sm:text-xs">สารจากผู้บริหารโรงเรียน</label>
            <div class="space-y-3 p-4 bg-pink-50/20 rounded-2xl border border-pink-100/40">
                <div class="space-y-1">
                    <label class="block text-slate-600 font-bold">หัวข้อสาส์นผู้บริหาร (Director Message Title)</label>
                    <input type="text" name="director_message_title" value="<?php echo htmlspecialchars($settings['director_message_title'] ?? ''); ?>" class="w-full bg-white rounded-xl border border-pink-100 p-2.5 text-xs outline-none focus:ring-1 focus:ring-school-pink">
                </div>
                <div class="space-y-1">
                    <label class="block text-slate-600 font-bold">เนื้อความสารจากผู้บริหารฉบับเต็ม</label>
                    <textarea name="director_message" rows="5" class="w-full bg-white rounded-xl border border-pink-100 p-2.5 text-xs outline-none focus:ring-1 focus:ring-school-pink" placeholder="พิมพ์สาส์นจากผู้บริหารที่นี่..."><?php echo htmlspecialchars($settings['director_message'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="space-y-1 sm:col-span-2">
            <label class="block">ที่ตั้งและสถานที่ตั้งอย่างละเอียด</label>
            <textarea name="address" required rows="2" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none"><?php echo htmlspecialchars($settings['address']); ?></textarea>
        </div>

        <div class="space-y-1">
            <label class="block">เบอร์โทรศัพท์ติดต่อ</label>
            <input type="text" name="phone" required value="<?php echo htmlspecialchars($settings['phone']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1">
            <label class="block">อีเมลติดต่อ</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($settings['email']); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1">
            <label class="block">สังกัดและเขตพื้นที่การศึกษา</label>
            <input type="text" name="jurisdiction" required value="<?php echo htmlspecialchars($settings['jurisdiction'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1">
            <label class="block">ระดับการสอนที่เปิดสอน</label>
            <input type="text" name="levels" required value="<?php echo htmlspecialchars($settings['levels'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1">
            <label class="block">ชื่อ-นามสกุล ผู้อำนวยการโรงเรียน</label>
            <input type="text" name="director_name" required value="<?php echo htmlspecialchars($settings['director_name'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1">
            <label class="block">ตำแหน่งทางราชการของผู้อำนวยการ</label>
            <input type="text" name="director_title" required value="<?php echo htmlspecialchars($settings['director_title'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1 sm:col-span-2">
            <label class="block">ลิงก์ภาพถ่ายผู้อำนวยการโรงเรียน (URL)</label>
            <input type="text" name="director_image" value="<?php echo htmlspecialchars($settings['director_image'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <div class="space-y-1 sm:col-span-2">
            <label class="block">วิดีโอแนะนำโรงเรียนบน YouTube (ลิงก์แบบ Embed URL เช่น https://www.youtube.com/embed/...)</label>
            <input type="text" name="youtube_intro_url" value="<?php echo htmlspecialchars($settings['youtube_intro_url'] ?? ''); ?>" class="w-full rounded-xl border border-pink-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-school-pink outline-none">
        </div>

        <!-- แถบเชื่อมโยง Google Apps Script (GAS) เพื่อส่งไฟล์เข้า Google Drive -->
        <div class="sm:col-span-2 border-t border-slate-100 pt-6 mt-4 space-y-4">
            <div class="bg-blue-50/50 border border-blue-100 rounded-3xl p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-2 border-b border-blue-100/60 pb-3">
                    <span class="p-2 bg-blue-100 text-blue-600 rounded-xl leading-none font-sans text-sm">☁️</span>
                    <div>
                        <h4 class="font-heading font-black text-xs sm:text-xs text-slate-800 leading-tight">เชื่อมต่อคลาวด์อัปโหลดเก็บไฟล์บน Google Drive (แก้ปัญหาอัปโหลดฟิลด์จำกัด)</h4>
                        <p class="text-[9px] text-slate-400 font-medium">เปิดทำงานในกรณีที่โฮสติ้งเซิร์ฟเวอร์จำกัดขนาดอัปโหลดรูปภาพ สื่อประกอบ หรือรายงานไฟล์ต่างๆ</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-slate-700 font-bold text-xs">🔗 Google Apps Script Web App URL ของโรงเรียน</label>
                        <input type="url" name="google_apps_script_url" value="<?php echo htmlspecialchars($settings['google_apps_script_url'] ?? ''); ?>" placeholder="https://script.google.com/macros/s/.../exec" class="w-full bg-white rounded-xl border border-blue-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-blue-400 outline-none">
                        <p class="text-[9px] text-slate-500 font-medium">💡 ปล่อยว่างไว้หากต้องการสลับกลับไปใช้อัปโหลดเก็บเข้าโฟลเดอร์เซิร์ฟเวอร์โลคอลตามปกติ</p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-slate-700 font-bold text-xs">📁 Google Drive Folder ID (ไอดีโฟลเดอร์ปลายทาง - ตัวเลือกเสริม)</label>
                        <input type="text" name="google_drive_folder_id" value="<?php echo htmlspecialchars($settings['google_drive_folder_id'] ?? ''); ?>" placeholder="1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456" class="w-full bg-white rounded-xl border border-blue-100 p-2.5 text-xs font-medium focus:ring-1 focus:ring-blue-400 outline-none">
                        <p class="text-[9px] text-slate-500 font-medium">💡 นำ ID จากกึ่งกลาง URL โฟลเดอร์ใน Google Drive มาใส่ หากเว้นว่างไว้ระบบจะตั้งโฟลเดอร์อัตโนมัติ</p>
                    </div>
                </div>

                <div class="space-y-2 pt-1">
                    <span class="block text-slate-700 font-bold text-[10px] sm:text-xs">📋 รหัสสคริปต์ Google Apps Script (GAS) สำหรับนำไปใช้สร้างหรือระบุโฟลเดอร์:</span >
                    <p class="text-[10px] text-slate-500 bg-white/60 p-3 rounded-xl border border-blue-100/40 leading-relaxed font-normal">
                        <strong>แนะนำการตั้งค่าแบบละเอียดและง่าย:</strong><br>
                        1. ไปที่ <a href="https://script.google.com" target="_blank" class="text-blue-600 underline hover:text-blue-700">Google Apps Script (คลิกเปิด)</a> ด้วยบัญชีจีเมลโรงเรียน<br>
                        2. คลิกปุ่ม <strong>โครงการใหม่ (New Project)</strong> แล้วลบโค้ดเก่าออก จากนั้นนำรหัสสคริปต์ด้านล่างนี้วางทั้งหมดแทนที่<br>
                        3. คลิกปุ่ม <strong>การทำให้ใช้งานได้ (Deploy)</strong> &gt; <strong>การใช้งานใหม่ (New Deployment)</strong><br>
                        4. เลือกฟันเฟืองประเภทเป็น <strong>เว็บแอป (Web App)</strong><br>
                        5. ตั้งค่าชื่อคำอธิบาย และกำหนดผู้มีสิทธิ์เข้าถึง (Who has access) ให้เลือกเป็น <strong>"ทุกคน" (Anyone)</strong> และผู้ดำเนินการเว็บแอปเป็น <strong>"ฉัน" (Me)</strong> จากนั้นกด Deploy<br>
                        6. คัดลอกลิงก์ Web App URL ที่ได้ นำมาแปะลงในช่องด้านบนนี้แล้วกดบันทึกโรงเรียน
                    </p>
                    
                    <div class="relative">
                        <textarea readonly id="gas_code_box" rows="10" class="w-full bg-slate-900 text-teal-400 font-mono p-4 rounded-xl text-[10px] outline-none border border-slate-800 leading-relaxed cursor-text select-all" onclick="this.select();" placeholder="คลิกเพื่อคัดลอกรูปสคริปต์ทั้งหมด..."><?php echo htmlspecialchars('function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var filename = data.filename;
    var mimeType = data.mimeType;
    var base64Data = data.base64;
    var folderId = data.folderId; // รับ ID ของโฟลเดอร์ปลายทางจากเว็บแอปโรงเรียน
    
    var decoded = Utilities.base64Decode(base64Data);
    var blob = Utilities.newBlob(decoded, mimeType, filename);
    
    var folder;
    
    // 1. ตรวจสอบว่าผู้ใช้คลิกกำหนด Folder ID เฉพาะมาหรือไม่
    if (folderId && folderId.trim() !== "") {
      try {
        folder = DriveApp.getFolderById(folderId.trim());
      } catch (fErr) {
        // หากเกิดข้อผิดพลาดในการตรวจสอบไอดี ให้พึ่งพาการสร้าง/ค้นหาด้วยชื่อโฟลเดอร์ต่อไป
      }
    }
    
    // 2. หากไม่ได้กำหนด Folder ID หรือไม่พบโฟลเดอร์ ให้สลับมาหาสร้างจากชื่อโฟลเดอร์ดั้งเดิม
    if (!folder) {
      var folderName = "โรงเรียนบ้านหนองหว้า_Uploads";
      var folders = DriveApp.getFoldersByName(folderName);
      
      if (folders.hasNext()) {
        folder = folders.next();
      } else {
        folder = DriveApp.createFolder(folderName);
      }
    }
    
    // ปลดล็อกแชร์สิทธิ์แบบสมบูรณ์ให้ทุกคนที่รับภาพเห็นกราฟิก
    folder.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    
    var file = folder.createFile(blob);
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    
    var fileId = file.getId();
    
    // ใช้สัญญานระนาบ direct-link ของ Google ในการเรนเดอร์ภาพเข้า tag img แน่นหนา ไม่หลุด
    var displayUrl = "";
    var isImage = mimeType.indexOf("image/") !== -1;
    
    if (isImage) {
      displayUrl = "https://lh3.googleusercontent.com/d/" + fileId;
    } else {
      displayUrl = "https://drive.google.com/uc?export=download&id=" + fileId;
    }
    
    return ContentService.createTextOutput(JSON.stringify({
      status: "success",
      url: displayUrl,
      fileId: fileId
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      status: "error",
      message: err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}'); ?></textarea>
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('gas_code_box').value); alert('คัดลอกรหัสสคริปต์ GAS เรียบร้อยแล้ว!');" class="absolute bottom-3 right-3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-3 rounded-lg text-[9px] transition">
                            📋 คลิกคัดลอกโค้ด
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ส่วนสื่ออัปโหลดกราฟิก ตรา และ แบนเนอร์ -->
        <div class="sm:col-span-2 border-t border-slate-100 pt-6 mt-2 space-y-4">
            <h4 class="font-heading font-black text-sm text-slate-800 flex items-center justify-between gap-1.5 flex-wrap">
                <span>🏞️ สื่อตราเครื่องหมายและแบนเนอร์แสดงผลหลัก</span>
                <span class="text-[9px] bg-amber-50 text-amber-700 px-3 py-1 rounded-lg border border-amber-200">
                    ⚙️ ขีดจำกัดเซิร์ฟเวอร์สูงสุด (PHP Limit): <strong><?php echo ini_get('upload_max_filesize'); ?></strong>
                </span>
            </h4>
            
            <!-- ป้ายช่วยให้ความรู้แนะนำขนาดและการแก้ไข -->
            <div class="text-[10px] text-slate-500 bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-1.5 leading-relaxed font-semibold">
                <p class="text-slate-700 font-bold flex items-center gap-1">💡 คู่มือการอัปโหลดไฟล์ภาพประกอบระบบ:</p>
                <ul class="list-disc pl-4 space-y-0.5">
                    <li><strong>ขนาดไฟล์แนะนำ:</strong> ควรรักษาระดับไฟล์ให้อยู่ระหว่าง <span class="text-pink-600">50 KB ถึง 1 MB</span> เพื่อความเร็วและสอดรับกับข้อกำหนดโฮสติ้งสูงสุด</li>
                    <li><strong>สัดส่วนที่เหมาะสม:</strong> โลโก้ (สี่เหลี่ยมจัตุรัส 1:1), หน้าแบนเนอร์ซ้าย (สี่เหลี่ยมผืนผ้า 4:3), ภาพและแบนเนอร์พื้นหลัง (ขนาดกว้าง 16:9 เช่น 1200x675px)</li>
                    <li>หากเลือกอัปโหลดไฟล์แล้วติดขัดปัญหาทางเทคนิค ท่านสามารถเขียน "ลิงก์ภาพภาพตรงภายนอก" ใส่ช่อง URL เพิ่มความสะดวกในการสลับข้อมูลได้ทันที</li>
                </ul>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- ตราสัญลักษณ์ / โลโก้ -->
                <div class="bg-pink-50/10 border border-pink-100/60 rounded-2xl p-4 space-y-3">
                    <span class="block text-slate-700 font-bold">1. ตราสัญลักษณ์ / โลโก้โรงเรียน</span>
                    <?php if (!empty($settings['school_logo'])): ?>
                        <img src="<?php echo htmlspecialchars($settings['school_logo']); ?>" class="w-16 h-16 object-contain mx-auto bg-white p-1 rounded-xl shadow-sm border border-pink-100">
                    <?php endif; ?>
                    <div class="space-y-2">
                        <input type="file" name="school_logo_file" accept="image/*" class="w-full text-[10px] text-slate-550 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                        <p class="text-[9px] text-slate-400 font-medium">รองรับ JPG, PNG, GIF (สูงสุด <?php echo ini_get('upload_max_filesize'); ?>)</p>
                        <input type="text" name="school_logo_url" value="<?php echo htmlspecialchars($settings['school_logo']); ?>" placeholder="หรือระบุเป็น URL ภาพตรง..." class="w-full rounded-lg bg-white border border-pink-100 p-1.5 text-[10px]">
                    </div>
                </div>

                <!-- แบนเนอร์พื้นหลังหลัก -->
                <div class="bg-pink-50/10 border border-pink-100/60 rounded-2xl p-4 space-y-3">
                    <span class="block text-slate-700 font-bold">2. ภาพแบนเนอร์พื้นหลังหลัก</span>
                    <?php if (!empty($settings['banner_bg_image'])): ?>
                        <div class="w-full h-16 bg-cover bg-center rounded-lg shadow-sm border border-pink-100" style="background-image: url('<?php echo htmlspecialchars($settings['banner_bg_image']); ?>');"></div>
                    <?php endif; ?>
                    <div class="space-y-2">
                        <input type="file" name="banner_bg_file" accept="image/*" class="w-full text-[10px] text-slate-550 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                        <p class="text-[9px] text-slate-400 font-medium">รองรับ JPG, PNG (สูงสุด <?php echo ini_get('upload_max_filesize'); ?>)</p>
                        <input type="text" name="banner_bg_url" value="<?php echo htmlspecialchars($settings['banner_bg_image']); ?>" placeholder="หรือระบุเป็น URL ภาพตรง..." class="w-full rounded-lg bg-white border border-pink-100 p-1.5 text-[10px]">
                    </div>
                </div>

                <!-- ภาพหน้าโบกแต่งแบนเนอร์ด้านซ้าย -->
                <div class="bg-pink-50/10 border border-pink-100/60 rounded-2xl p-4 space-y-3">
                    <span class="block text-slate-700 font-bold">3. กราฟิก/ภาพแต่งหน้าแบนเนอร์ซ้าย</span>
                    <?php if (!empty($settings['banner_right_image'])): ?>
                        <img src="<?php echo htmlspecialchars($settings['banner_right_image']); ?>" class="w-20 h-16 object-contain mx-auto bg-white p-1 rounded-lg border border-pink-100">
                    <?php endif; ?>
                    <div class="space-y-2">
                        <input type="file" name="banner_right_file" accept="image/*" class="w-full text-[10px] text-slate-550 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-pink-50 file:text-school-pink hover:file:bg-pink-100">
                        <p class="text-[9px] text-slate-400 font-medium">รองรับ JPG, PNG (สูงสุด <?php echo ini_get('upload_max_filesize'); ?>)</p>
                        <input type="text" name="banner_right_url" value="<?php echo htmlspecialchars($settings['banner_right_image'] ?? ''); ?>" placeholder="หรือระบุเป็น URL ภาพตรง..." class="w-full rounded-lg bg-white border border-pink-100 p-1.5 text-[10px]">
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:col-span-2 pt-4">
            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-950 text-white font-black py-3.5 rounded-2xl transition shadow text-sm">
                💾 บันทึกข้อมูลตั้งค่าทั่วไปของสถานศึกษา
            </button>
        </div>
    </form>

    <!-- บล็อกระบบเครื่องมือกู้คืนข้อมูลเริ่มต้นสำหรับผู้ดูแลระบบ -->
    <div class="mt-12 bg-pink-50/20 border border-pink-100 rounded-3xl p-6 space-y-4">
        <div class="flex items-center gap-2 border-b border-pink-100 pb-2">
            <span class="text-lg">⚙️</span>
            <span class="font-heading font-black text-xs sm:text-sm text-slate-850">เครื่องมือสารสนเทศสำหรับป้อนตัวอย่าง (Developer & Administrator Tools)</span>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed font-semibold">
            หากคุณรู้สึกว่าแถบตาราง เช่น <strong class="text-slate-700">รายชื่อนักเรียน สถิติการศึกษา</strong> หรือ <strong class="text-slate-700">สื่อคลังงานครู</strong> ดูว่างเปล่าหรือต้องการฟื้นฟูระบบ คุณสามารถกดคลิกปุ่มด้านล่างเพื่อทำการเคลียร์ตารางและป้อนข้อมูลสถิติตัวอย่างทั้งหมดของโรงเรียนบ้านหนองหว้ากลับมาได้ทันทีอย่างรวดเร็ว
        </p>
        <form action="admin.php?tab=general" method="POST" onsubmit="return confirm('⚠️ คำเตือน! การทำงานนี้จะลบข้อมูลอื่นๆ ในตารางทั้งหมด และกู้คืนกลับมาเป็นข้อมูลประวัติโรงเรียนเริ่มต้นตามคู่มือแบบดั้งเดิมเซ็ตอัพ คุณต้องการดำเนินการต่อหรือไม่?');">
            <input type="hidden" name="reset_database_defaults" value="1">
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-black px-6 py-3.5 rounded-2xl transition shadow-md text-xs inline-flex items-center gap-2 cursor-pointer">
                🔄 รีเซ็ตกู้คืนข้อมูลตัวอย่างพื้นฐานโรงเรียนคืนสู่ตารางทั้งหมด (Restore Defaults)
            </button>
        </form>
    </div>
</div>
