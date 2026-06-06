<?php
/**
 * สคริปต์บันทึกสถิติจำนวนการดาวน์โหลดเอกสาร (Document Download Counter & Redirect Tracker)
 * เมื่อผู้ใช้นอกทำการคลิกดาวน์โหลด ระบบจะบวกหนึ่งคราวสถิติเข้าสู่ MySQL แล้วส่งต่อไปยังที่อยู่ไฟล์จริง
 */

require_once 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        // ดึงที่อยู่ไฟล์จากตาราง downloads
        $stmt = $pdo->prepare("SELECT * FROM `downloads` WHERE `id` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch();

        if ($doc) {
            // บวกหนึ่งยอดดาวน์โหลดลงในตาราง downloads
            $update = $pdo->prepare("UPDATE `downloads` SET `download_count` = `download_count` + 1 WHERE `id` = :id");
            $update->execute(['id' => $id]);

            // ส่งต่อไปยังปลายทางไฟล์จริง (หากไม่มีให้ทดแทนเป็นลิงค์จำลองหรือส่งกลับหน้าคลังเอกสาร)
            $file_url = !empty($doc['file_url']) ? $doc['file_url'] : '#';
            
            if ($file_url === '#') {
                // หากไม่มีลิงค์จริง ให้ระบบจำลองสร้างเอกสารดาวน์โหลดเพื่อตอบรับบนเบราว์เซอร์
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . urlencode($doc['title']) . '.' . strtolower($doc['file_type']) . '"');
                echo "ไฟล์จำลองสำหรับการทดสอบระเบียบดาวน์โหลดระบบโรงเรียนบ้านหนองหว้า\n";
                echo "--------------------------------------------------------\n";
                echo "ชื่อเอกสาร: " . $doc['title'] . "\n";
                echo "หมวดหมู่ราชการ: " . $doc['category'] . "\n";
                echo "ขนาดวัดจริง: " . $doc['file_size'] . "\n";
                echo "ประเภทไฟล์จริง: " . $doc['file_type'] . "\n";
                echo "ระบบได้บันทึกยอดดาวน์โหลดของสถาบันเรียบร้อยแล้ว\n";
                exit;
            } else {
                header('Location: ' . $file_url);
                exit;
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

// ป้องกันหากมีความผิดพลาด ให้กลับไปที่หน้ารายชื่อเอกสารป้องปัดผิดพลาด
header('Location: index.php#documents');
exit;
?>
