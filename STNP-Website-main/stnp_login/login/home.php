<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>STNP Homepage</title>
<link rel="stylesheet" href="home.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <h1>STNP</h1>
    <input type="text" placeholder="Search something">
    <div class="settings-btn" id="settingsBtn">⚙️</div>
</div>

<!-- SETTINGS PANEL -->
<div class="settings-panel" id="settingsPanel">
    <h3>Settings</h3>

    <div class="theme-toggle">
        <label>Dark Mode</label>
        <input type="checkbox" id="modeSwitch">
    </div>

    <button id="profileBtn">View Profile</button>
    <button id="logoutBtn">Logout</button>
</div>

<!-- PAGE -->
<div class="layout">
    <div class="feed">

        <!-- CREATE POST -->
        <div class="create-post">
            <h3>Create a Post</h3>
            <textarea id="postText" placeholder="Write something..."></textarea>
            <input id="postImage" type="file" accept="image/*">
            <button id="postBtn">Post</button>
        </div>

        <!-- POSTS -->
        <div id="postFeed"></div>
    </div>
</div>

<script>
/* ===============================
   USER INFO
=============================== */
const USER_ID = <?php echo (int)$user_id; ?>;
const USERNAME = "<?php echo $username; ?>";
const USER_AVATAR =
    localStorage.getItem("avatar_" + USER_ID) || "default-avatar.png";

/* ===============================
   STORAGE
=============================== */
const POSTS_KEY = "global_posts";
let savedPosts = JSON.parse(localStorage.getItem(POSTS_KEY) || "[]");

/* ===============================
   LOAD POSTS
=============================== */
window.onload = () => {
    applyDarkMode();

    savedPosts.forEach(p => {
        if (!Array.isArray(p.comments)) p.comments = [];
        renderPost(p, false);
    });
};

/* ===============================
   CREATE POST
=============================== */
postBtn.onclick = async () => {
    const text = postText.value.trim();
    let imageData = "";

    if (!text && postImage.files.length === 0) {
        alert("Post must have text or image.");
        return;
    }

    if (postImage.files.length > 0) {
        imageData = await toBase64(postImage.files[0]);
    }

    const newPost = {
        id: Date.now(),
        user: USERNAME,
        avatar: USER_AVATAR,
        text,
        image: imageData,
        votes: 0,
        userVotes: {},
        created: Date.now(),
        comments: []
    };

    savedPosts.unshift(newPost);
    localStorage.setItem(POSTS_KEY, JSON.stringify(savedPosts));
    renderPost(newPost, true);

    postText.value = "";
    postImage.value = "";
};

function toBase64(file) {
    return new Promise(res => {
        const r = new FileReader();
        r.onload = () => res(r.result);
        r.readAsDataURL(file);
    });
}

/* ===============================
   TIME AGO
=============================== */
function timeAgo(t) {
    const d = Math.floor((Date.now() - t) / 1000);
    if (d < 60) return "Just now";
    const m = Math.floor(d / 60);
    if (m < 60) return `${m} minute${m === 1 ? "" : "s"} ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h} hour${h === 1 ? "" : "s"} ago`;
    const days = Math.floor(h / 24);
    return `${days} day${days === 1 ? "" : "s"} ago`;
}

/* ===============================
   RENDER POST
=============================== */
function renderPost(data, prepend = true) {

    if (!Array.isArray(data.comments)) data.comments = [];

    const post = document.createElement("div");
    post.className = "post";

    post.innerHTML = `
        <div class="vote-box">
            <div class="upvote">▲</div>
            <div class="vote-count">${data.votes}</div>
            <div class="downvote">▼</div>
        </div>

        <div class="post-body">
            <div class="post-title">
                <img src="${data.avatar}" class="avatar">
                <span>u/${data.user}</span>
            </div>

            <div class="post-info" data-time="${data.created}">
                ${timeAgo(data.created)}
            </div>

            <div class="post-content">
                ${data.text ? `<p>${data.text}</p>` : ""}
                ${data.image ? `<img src="${data.image}" class="post-img">` : ""}
            </div>

            <div class="comments-section">
                <div class="comments-list"></div>

                <div class="add-comment">
                    <input type="text" class="comment-input" placeholder="Add a comment...">
                    <button class="comment-btn">Comment</button>
                </div>
            </div>
        </div>
    `;

    const commentsList = post.querySelector(".comments-list");

    data.comments.forEach(c => renderComment(c, commentsList));

    post.querySelector(".comment-btn").onclick = () => {
        const input = post.querySelector(".comment-input");
        const text = input.value.trim();
        if (!text) return;

        const newComment = {
            id: Date.now(),
            user: USERNAME,
            avatar: USER_AVATAR,
            text,
            created: Date.now()
        };

        const realPost = savedPosts.find(p => p.id === data.id);
        if (!Array.isArray(realPost.comments)) realPost.comments = [];

        realPost.comments.push(newComment);
        localStorage.setItem(POSTS_KEY, JSON.stringify(savedPosts));

        renderComment(newComment, commentsList);
        input.value = "";
    };

    const voteCount = post.querySelector(".vote-count");
    post.querySelector(".upvote").onclick = () => handleVote(data.id, 1, voteCount);
    post.querySelector(".downvote").onclick = () => handleVote(data.id, -1, voteCount);

    prepend ? postFeed.prepend(post) : postFeed.append(post);
}

/* ===============================
   RENDER COMMENT
=============================== */
function renderComment(comment, container) {
    const div = document.createElement("div");
    div.className = "comment";

    div.innerHTML = `
        <div class="comment-header">
            <img src="${comment.avatar}" class="avatar small">
            <strong>u/${comment.user}</strong>
            <span> · ${timeAgo(comment.created)}</span>
        </div>
        <p>${comment.text}</p>
    `;

    container.append(div);
}

/* ===============================
   VOTING
=============================== */
function handleVote(id, val, el) {
    const post = savedPosts.find(p => p.id === id);
    if (!post.userVotes) post.userVotes = {};

    const prev = post.userVotes[USER_ID] || 0;

    if (prev === val) {
        post.votes -= val;
        post.userVotes[USER_ID] = 0;
    } else if (prev !== 0) {
        post.votes += val * 2;
        post.userVotes[USER_ID] = val;
    } else {
        post.votes += val;
        post.userVotes[USER_ID] = val;
    }

    el.textContent = post.votes;
    localStorage.setItem(POSTS_KEY, JSON.stringify(savedPosts));
}

/* ===============================
   TIME UPDATE
=============================== */
setInterval(() => {
    document.querySelectorAll(".post-info").forEach(el => {
        el.textContent = timeAgo(el.dataset.time);
    });
}, 60000);

/* ===============================
   DARK MODE
=============================== */
function applyDarkMode() {
    const sw = document.getElementById("modeSwitch");
    if (localStorage.getItem("darkMode") === "on") {
        document.body.classList.add("dark");
        sw.checked = true;
    }

    sw.onchange = () => {
        document.body.classList.toggle("dark");
        localStorage.setItem("darkMode", sw.checked ? "on" : "off");
    };
}

/* ===============================
   SETTINGS
=============================== */
settingsBtn.onclick = () => {
    settingsPanel.style.display =
        settingsPanel.style.display === "block" ? "none" : "block";
};

profileBtn.onclick = () => location.href = "profile.php";
logoutBtn.onclick = () => location.href = "logout.php";

/* ===============================
   DRAG SETTINGS PANEL
=============================== */
let drag = false, ox = 0, oy = 0;

settingsPanel.onmousedown = e => {
    drag = true;
    ox = e.clientX - settingsPanel.offsetLeft;
    oy = e.clientY - settingsPanel.offsetTop;
};

document.onmousemove = e => {
    if (!drag) return;
    settingsPanel.style.left = (e.clientX - ox) + "px";
    settingsPanel.style.top = (e.clientY - oy) + "px";
};

document.onmouseup = () => drag = false;
</script>

</body>
</html>
