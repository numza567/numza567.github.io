<?php
// *****************************************************************
// * 1. PHP BACKEND: ส่วนสำหรับการดึงข้อมูล (READ) *
// *****************************************************************

// กำหนดเส้นทางไฟล์ news.txt (สมมติว่า admin.php อยู่ในโฟลเดอร์ลึก 15 ชั้น)
$news_file_path = __DIR__ . '/../../../../../../../../../../../news/news.txt';
$articles = [];
$error_message = '';

if (file_exists($news_file_path)) {
    // อ่านเนื้อหาทั้งหมด
    $file_content = file_get_contents($news_file_path);
    
    // แยกบทความด้วย ---
    $raw_articles = array_filter(array_map('trim', explode('---', $file_content)));
    
    foreach ($raw_articles as $article_text) {
        $lines = array_filter(array_map('trim', explode("\n", $article_text)));
        if (count($lines) >= 2) {
            $title = str_replace('#', '', array_shift($lines));
            $meta = str_replace('##', '', array_shift($lines));
            $content = implode("\n", $lines);
            
            // แยก ผู้เขียน และ วันที่
            $author = 'ไม่ระบุ';
            $date = 'ไม่ระบุ';
            if (preg_match('/ผู้เขียน:\s*([^|]+)/', $meta, $matches)) {
                $author = trim($matches[1]);
            }
            if (preg_match('/วันที่:\s*([^|]+)/', $meta, $matches)) {
                $date = trim($matches[1]);
            }
            
            $articles[] = [
                'title' => trim($title),
                'meta' => trim($meta),
                'author' => $author,
                'date' => $date,
                'content' => trim($content),
                'raw' => trim($article_text) // เก็บข้อความดิบไว้สำหรับฟอร์มแก้ไข
            ];
        }
    }
} else {
    $error_message = '❌ ไม่พบไฟล์ news/news.txt หรือเส้นทางผิดพลาด: ' . $news_file_path;
}

// แปลงข้อมูลบทความทั้งหมดเป็น JSON เพื่อให้ JavaScript ใช้งานได้
$articles_json = json_encode($articles, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Numza567</title>
    
    <style>
        /* 1. การประกาศ @font-face เพื่อโหลดฟอนต์ Itim Regular */
        @font-face {
            font-family: 'Itim';
            /* กลับไปยัง root folder เพื่อหา Itim-Regular.ttf */
            src: url('../../../../admin/Itim-Regular.ttf') format('truetype'); 
            font-weight: normal;
            font-style: normal;
        }

        /* CSS: สไตล์ Dark Mode สำหรับ Admin Panel */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Itim', cursive, sans-serif;
            background-color: #1a1a1a; 
            color: #E0E0E0;
            line-height: 1.6;
        }
        header { background-color: #2c2c2c; padding: 20px 40px; border-bottom: 3px solid #FF9800; }
        header h1 { color: #FFB74D; font-size: 2em; }
        main { padding: 40px; max-width: 1400px; margin: 0 auto; }
        h2 { color: #FFFFFF; margin-bottom: 20px; border-bottom: 1px solid #444; padding-bottom: 10px; }

        /* แถบควบคุมและปุ่ม */
        .controls { margin-bottom: 30px; padding: 20px; background-color: #2c2c2c; border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5); display: flex; gap: 15px; }
        .control-btn { padding: 10px 20px; background-color: #FF9800; color: #1a1a1a; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
        .control-btn:hover { background-color: #FB8C00; }
        .control-btn.delete-all { background-color: #D32F2F; color: white; }
        .control-btn.delete-all:hover { background-color: #C62828; }
        .action-btn { padding: 5px 10px; margin-right: 5px; border: none; border-radius: 3px; cursor: pointer; font-size: 0.9em; }
        .edit-btn { background-color: #1976D2; color: white; }
        .delete-btn { background-color: #D32F2F; color: white; }
        #post-list { width: 100%; border-collapse: collapse; margin-top: 20px; }
        #post-list th, #post-list td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #333; }
        #post-list th { background-color: #383838; color: #FFB74D; font-weight: bold; }
        #post-list tr:hover { background-color: #2c2c2c; }

        /* สไตล์สำหรับฟอร์มแก้ไขบทความ (Edit Form) */
        #edit-form-section {
            background-color: #2c2c2c;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #FF9800;
            display: none; /* ซ่อนฟอร์มไว้ก่อนในตอนแรก */
        }
        #edit-form-section label {
            display: block;
            margin-top: 15px;
            color: #FFB74D;
            font-weight: bold;
        }
        #edit-form-section input[type="text"], #edit-form-section textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            background-color: #383838;
            border: 1px solid #555;
            color: #E0E0E0;
            border-radius: 4px;
            resize: vertical;
        }
        #edit-form-section textarea {
            min-height: 200px;
            font-family: 'Itim', cursive, sans-serif;
        }
        #edit-form-section .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .save-btn { background-color: #4CAF50; color: white; }
        .cancel-btn { background-color: #666; color: white; }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            display: none; /* ซ่อนไว้ก่อน */
            justify-content: center;
            align-items: center;
            font-size: 1.5em;
            z-index: 1000;
        }
    </style>
</head>
<body>

    <div id="loading-overlay" class="loading-overlay">กำลังบันทึกข้อมูล...</div>

    <header>
        <h1>แดชบอร์ดผู้ดูแล | Numza567</h1>
    </header>

    <main>
        <section class="controls">
            <button class="control-btn" onclick="showEditForm('new')">📝 เขียนบทความใหม่</button>
            <button class="control-btn" onclick="simulateAction('ตั้งค่าเว็บไซต์')">⚙️ ตั้งค่าเว็บไซต์</button>
            <button class="control-btn delete-all" onclick="simulateAction('ล้างข้อมูลทั้งหมด', true)">🗑️ ลบข้อมูลทั้งหมด</button>
        </section>

        <section id="edit-form-section">
            <h2 id="form-title">แก้ไขบทความ: [ชื่อบทความ]</h2>
            <form id="article-form">
                <input type="hidden" id="edit-post-index">

                <label for="edit-title">หัวข้อบทความ (#)</label>
                <input type="text" id="edit-title" required>

                <label for="edit-meta">ข้อมูล Meta (## ผู้เขียน: ... | วันที่: ...)</label>
                <input type="text" id="edit-meta" required>

                <label for="edit-content">เนื้อหาบทความ (Body)</label>
                <textarea id="edit-content" required></textarea>

                <div class="form-actions">
                    <button type="button" class="control-btn save-btn" onclick="saveChanges()">✅ บันทึกการแก้ไข</button>
                    <button type="button" class="control-btn cancel-btn" onclick="hideEditForm()">✖️ ยกเลิก</button>
                </div>
            </form>
        </section>

        <h2>รายการบทความปัจจุบัน</h2>
        <table id="post-list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>หัวข้อบทความ</th>
                    <th>ผู้เขียน</th>
                    <th>วันที่</th>
                    <th>การดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($error_message): ?>
                    <tr><td colspan="5" style="color: red; text-align: center;"><?= $error_message ?></td></tr>
                <?php elseif (empty($articles)): ?>
                    <tr><td colspan="5" style="text-align: center; color: #FF9800;">ไม่มีบทความในระบบ</td></tr>
                <?php else: ?>
                    <?php foreach ($articles as $index => $article): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($article['title']) ?></td>
                            <td><?= htmlspecialchars($article['author']) ?></td>
                            <td><?= htmlspecialchars($article['date']) ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick="showEditForm(<?= $index ?>)">แก้ไข</button>
                                <button class="action-btn delete-btn" onclick="simulateAction('ลบบทความ: <?= htmlspecialchars($article['title']) ?>', true)">ลบ</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script>
        // *****************************************************************
        // * 2. JAVASCRIPT: จัดการข้อมูลและส่งไปยัง PHP *
        // *****************************************************************

        // PHP จะใส่ข้อมูลบทความทั้งหมดลงในตัวแปร JavaScript ตั้งแต่ต้น
        let allArticles = <?= $articles_json ?>; 
        
        // ฟังก์ชันจำลองการทำงานของปุ่มควบคุมอื่นๆ
        function simulateAction(actionName, isDangerous = false) {
            // โค้ดสำหรับ 'เขียนบทความใหม่' ถูกย้ายไปที่ showEditForm('new') ในปุ่มแล้ว
            if (isDangerous) {
                if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการ ${actionName} จริงๆ? การกระทำนี้ไม่สามารถย้อนกลับได้ (Mockup)`)) {
                    alert(`✅ จำลอง: ${actionName} สำเร็จ!`);
                }
            } else {
                alert(`✅ จำลอง: เปิดหน้าจอสำหรับ ${actionName} แล้ว!`);
            }
        }

        // ฟังก์ชันแสดงฟอร์มและดึงข้อมูลมาใส่
        function showEditForm(index) {
            const formSection = document.getElementById('edit-form-section');
            const formTitle = document.getElementById('form-title');
            const editTitle = document.getElementById('edit-title');
            const editMeta = document.getElementById('edit-meta');
            const editContent = document.getElementById('edit-content');
            const postIndex = document.getElementById('edit-post-index');
            
            if (index === 'new') {
                formTitle.textContent = 'เขียนบทความใหม่';
                editTitle.value = '';
                editMeta.value = 'ผู้เขียน: แอดมินบล็อก | วันที่: ' + new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
                editContent.value = 'พิมพ์เนื้อหาบทความใหม่ที่นี่...\n';
                postIndex.value = 'new';
            } else {
                const article = allArticles[index];
                if (!article) return;
                
                // ใช้ข้อมูลที่ PHP ดึงมาแสดง
                formTitle.textContent = `แก้ไขบทความ: ${article.title}`;
                editTitle.value = article.title;
                editMeta.value = article.meta;
                editContent.value = article.content;
                postIndex.value = index;
            }
            formSection.style.display = 'block'; 
            window.scrollTo(0, 0);
        }

        // ฟังก์ชันซ่อนฟอร์ม
        function hideEditForm() {
            document.getElementById('edit-form-section').style.display = 'none';
        }

        // ฟังก์ชันบันทึกการเปลี่ยนแปลง (ส่งข้อมูลไปยัง PHP Processor)
        function saveChanges() {
            const postIndex = document.getElementById('edit-post-index').value;
            const newTitle = document.getElementById('edit-title').value;
            const newMeta = document.getElementById('edit-meta').value;
            const newContent = document.getElementById('edit-content').value;
            
            if (!newTitle || !newMeta || !newContent) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            // 1. สร้างบทความใหม่ในรูปแบบ raw text
            const newArticleRaw = `# ${newTitle}\n## ${newMeta}\n\n${newContent}\n`;
            
            // 2. อัปเดตตัวแปร allArticles
            if (postIndex === 'new') {
                allArticles.unshift({ 
                    title: newTitle, 
                    meta: newMeta, 
                    content: newContent,
                    raw: newArticleRaw // เก็บ raw text
                }); 
            } else {
                allArticles[parseInt(postIndex)].title = newTitle;
                allArticles[parseInt(postIndex)].meta = newMeta;
                allArticles[parseInt(postIndex)].content = newContent;
                allArticles[parseInt(postIndex)].raw = newArticleRaw;
            }
            
            // 3. สร้างเนื้อหาใหม่ทั้งหมดที่จะเขียนทับ news.txt (คั่นด้วย ---\n\n)
            const newFileContent = allArticles.map(a => a.raw.trim()).join('\n\n---\n\n') + '\n';
            
            // 4. ข้อมูลที่จะส่งไปให้ PHP Processor
            const dataToSend = {
                action: postIndex === 'new' ? 'create' : 'update',
                new_content: newFileContent // ส่งเนื้อหาไฟล์ทั้งหมดไปเขียนทับ
            };

            // 5. ส่งข้อมูลไปยัง Backend
            document.getElementById('loading-overlay').style.display = 'flex'; // แสดงโหลดดิ้ง

            fetch('/save_blog_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            })
            .then(response => response.json())
            .then(result => {
                document.getElementById('loading-overlay').style.display = 'none'; // ซ่อนโหลดดิ้ง
                if (result.success) {
                    alert(`✅ บันทึกสำเร็จ: บทความ "${newTitle}" ถูกบันทึกจริงแล้ว!`);
                    hideEditForm();
                    // เนื่องจาก PHP ได้โหลดตารางตอนต้นแล้ว และ JS ได้อัปเดต allArticles 
                    // เราต้องทำการโหลดหน้าเว็บใหม่ หรือสร้างฟังก์ชัน renderTable() ใหม่
                    window.location.reload(); // โหลดหน้าเว็บใหม่เพื่อให้ PHP ดึงข้อมูลล่าสุดมาแสดง
                } else {
                    alert(`❌ บันทึกไม่สำเร็จ: ${result.message}`);
                }
            })
            .catch(error => {
                document.getElementById('loading-overlay').style.display = 'none';
                alert(`❌ ข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์! ตรวจสอบ save_blog_content.php`);
                console.error('Server Communication Error:', error);
            });
        }
    </script>
</body>
</html>
