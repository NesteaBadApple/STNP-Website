<?php
session_start();

// If not logged in → send back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Username from session (database)
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? "UnknownUser";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Profile - STNP</title>
<link rel="stylesheet" href="profile.css">
</head>

<body class="dark">

<!-- NAVBAR -->
<div class="navbar">
    <h1>STNP</h1>
    <div class="nav-right">
        <a href="home.php" class="back-home">⬅ Home</a>
        <button id="toggleMode" class="toggle-btn">🌙</button>
    </div>
</div>

<div class="profile-container">

    <!-- LEFT SIDEBAR -->
    <div class="profile-sidebar">

        <!-- AVATAR -->
        <img
            id="avatarImg"
            class="avatar"
            src="https://i.pinimg.com/736x/15/0f/a8/150fa8800b0a0d5633abc1d1c4db3d87.jpg"
            title="Click to change avatar"
            style="cursor:pointer;"
        >

        <input
            type="file"
            id="avatarInput"
            accept="image/*"
            style="display:none;"
        >

        <!-- Username -->
        <h2 id="profileName">u/<?php echo htmlspecialchars($username); ?></h2>

        <!-- BIO -->
        <textarea id="bioText" class="bio" placeholder="Write your bio..."></textarea>
        <button id="saveBio" class="btn">Save Bio</button>

        <!-- KARMA -->
        <h3 class="section-title">Canteen</h3>

        <div class="karma-bar">
            <div id="postKarmaBar" class="karma-fill" style="width: 10%"></div>
        </div>
        <p class="karma-label">Post upvotes: <span id="postKarma">0</span></p>

        <div class="karma-bar">
            <div id="commentKarmaBar" class="karma-fill" style="width: 5%"></div>
        </div>
        <p class="karma-label">Comment upvotes: <span id="commentKarma">0</span></p>

    </div>

    <!-- RIGHT CONTENT -->
    <div class="profile-posts">

        <!-- TABS -->
        <div class="tabs">
            <button class="tab active" data-tab="overview">Overview</button>
            <button class="tab" data-tab="posts">Posts</button>
            <button class="tab" data-tab="saved">Saved</button>
        </div>

        <!-- OVERVIEW -->
        <div id="tab-overview" class="tab-content active">
            <p>Welcome to your profile overview.</p>
        </div>

        <!-- POSTS -->
        <div id="tab-posts" class="tab-content">
            <h3>Your Posts</h3>
            <div id="userPosts" class="posts-list"></div>
        </div>

        <!-- SAVED -->
        <div id="tab-saved" class="tab-content">
            <h3>Saved</h3>
            <p>No saved items.</p>
        </div>

    </div>
</div>

<script>
/* -------------------------------
   USER INFO
--------------------------------*/
const USER_ID = <?php echo (int)$user_id; ?>;
const USERNAME = "<?php echo htmlspecialchars($username); ?>";

/* -------------------------------
   STORAGE KEYS
--------------------------------*/
const BIO_KEY = "bio_" + USER_ID;
const AVATAR_KEY = "avatar_" + USER_ID;
const ALL_POSTS_KEY = "global_posts";

/* -------------------------------
   AVATAR LOGIC (PERMANENT CIRCLE)
--------------------------------*/
const avatarImg = document.getElementById("avatarImg");
const avatarInput = document.getElementById("avatarInput");

// Load saved avatar
const savedAvatar = localStorage.getItem(AVATAR_KEY);
if (savedAvatar) {
    avatarImg.src = savedAvatar;
}

// Click avatar → upload
avatarImg.onclick = () => avatarInput.click();

// Crop image into permanent circle
function cropToCircle(imageSrc, callback) {
    const img = new Image();
    img.onload = () => {
        const size = Math.min(img.width, img.height);
        const canvas = document.createElement("canvas");
        canvas.width = size;
        canvas.height = size;

        const ctx = canvas.getContext("2d");

        // Circular mask
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
        ctx.closePath();
        ctx.clip();

        // Center crop
        const sx = (img.width - size) / 2;
        const sy = (img.height - size) / 2;

        ctx.drawImage(img, sx, sy, size, size, 0, 0, size, size);

        callback(canvas.toDataURL("image/png"));
    };
    img.src = imageSrc;
}

// Save avatar
avatarInput.onchange = () => {
    const file = avatarInput.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        cropToCircle(reader.result, (circleImage) => {
            avatarImg.src = circleImage;
            localStorage.setItem(AVATAR_KEY, circleImage);
        });
    };
    reader.readAsDataURL(file);
};

/* -------------------------------
   BIO LOGIC
--------------------------------*/
const bioText = document.getElementById("bioText");
bioText.value = localStorage.getItem(BIO_KEY) || "This is your bio.";

document.getElementById("saveBio").onclick = () => {
    localStorage.setItem(BIO_KEY, bioText.value);
};

/* -------------------------------
   LOAD USER POSTS
--------------------------------*/
const allPosts = JSON.parse(localStorage.getItem(ALL_POSTS_KEY) || "[]");
const userPosts = allPosts.filter(p => p.user === USERNAME);

const postsBox = document.getElementById("userPosts");

if (userPosts.length === 0) {
    postsBox.innerHTML = "<p>No posts yet.</p>";
} else {
    userPosts.forEach(p => {
        const div = document.createElement("div");
        div.className = "post-item";

        if (p.text) {
            const t = document.createElement("p");
            t.textContent = p.text;
            div.appendChild(t);
        }

        if (p.image) {
            const img = document.createElement("img");
            img.src = p.image;
            img.className = "post-image";
            img.style.width = "200px";
            img.style.borderRadius = "8px";
            img.style.marginTop = "10px";
            div.appendChild(img);
        }

        postsBox.appendChild(div);
    });
}

/* -------------------------------
   TABS
--------------------------------*/
const tabs = document.querySelectorAll(".tab");
const contents = document.querySelectorAll(".tab-content");

tabs.forEach(tab => {
    tab.onclick = () => {
        tabs.forEach(t => t.classList.remove("active"));
        contents.forEach(c => c.classList.remove("active"));

        tab.classList.add("active");
        document.getElementById("tab-" + tab.dataset.tab).classList.add("active");
    };
});

/* -------------------------------
   DARK MODE
--------------------------------*/
document.getElementById("toggleMode").onclick = () => {
    document.body.classList.toggle("light");
};
</script>

</body>
</html>
