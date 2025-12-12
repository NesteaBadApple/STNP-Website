<?php
session_start();
require 'db.php';

$u = $_GET['u'] ?? null;
if (!$u) {
    echo "No user.";
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE username=:u LIMIT 1");
$stmt->execute([':u'=>$u]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "User not found.";
    exit;
}

$posts = $db->prepare("SELECT * FROM posts WHERE user_id=:id ORDER BY created_at DESC");
$posts->execute([':id'=>$user['id']]);
$posts = $posts->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profile - <?=htmlspecialchars($user['username'])?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="<?= ($_SESSION['dark_mode'] ?? 0) ? 'dark' : '' ?>">
<div style="max-width:900px;margin:20px auto;">
    <div class="card profile-header">
        <div class="avatar"><?= strtoupper(substr($user['username'] ?? $user['name'],0,2)) ?></div>
        <div>
            <h3>u/<?=htmlspecialchars($user['username'])?></h3>
            <p class="small"><?=htmlspecialchars($user['name'])?> • karma <?= (int)$user['karma'] ?></p>
        </div>
    </div>

    <div>
        <h4>Posts</h4>
        <?php foreach ($posts as $post): ?>
            <div class="card post">
                <div style="width:56px"></div>
                <div class="post-body">
                    <?php if ($post['title']): ?><h4><?=htmlspecialchars($post['title'])?></h4><?php endif; ?>
                    <?php if ($post['body']): ?><p><?=nl2br(htmlspecialchars($post['body']))?></p><?php endif; ?>
                    <?php if ($post['image']): ?><img src="<?=htmlspecialchars($post['image'])?>"><?php endif; ?>
                    <p class="small"><?=htmlspecialchars($post['created_at'])?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
