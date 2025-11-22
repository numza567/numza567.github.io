<?php
header('Content-Type: application/json');

// 1. กำหนดเส้นทางไฟล์ news.txt (อยู่ในโฟลเดอร์ news)
$news_file = __DIR__ . '/news/news.txt';

// 2. ตรวจสอบวิธีการร้องขอ (ต้องเป็น POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// 3. อ่านข้อมูล JSON ที่ส่งมาจาก JavaScript
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 4. ตรวจสอบข้อมูลที่จำเป็น
if (!isset($data['action']) || !isset($data['new_content'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit;
}

$action = $data['action'];
$new_article_data = $data['new_content'];

// 5. ดำเนินการตาม Action: อัปเดตไฟล์ news.txt
if ($action === 'create' || $action === 'update') {
    // 5.1 ในระบบที่เราใช้ไฟล์ .txt เราต้องดึงข้อมูลเดิมมา
    //    และทำการสร้างข้อมูลใหม่ทั้งหมดเพื่อเขียนทับ (นี่คือวิธีที่ง่ายที่สุด)
    
    // (*** หมายเหตุ: ในการอัปเดตบทความเดี่ยวๆ ที่ซับซ้อนกว่านี้ จะต้องเขียนโค้ด PHP
    //     เพื่อแยกบทความเดิมออกเป็น Array, แก้ไข Array ที่ตำแหน่งที่ต้องการ,
    //     แล้วค่อยนำกลับมาต่อกันใหม่ทั้งหมดเพื่อเขียนทับไฟล์)
    
    // 5.2 สำหรับโค้ดง่ายๆ นี้ เราสมมติว่า JS ส่งเนื้อหาใหม่ทั้งหมดมาให้เขียนทับ
    
    $success = file_put_contents($news_file, $new_article_data);
    
    if ($success !== false) {
        echo json_encode(['success' => true, 'message' => 'Blog content saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to write to file. Check file permissions (CHMOD).']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
?>
