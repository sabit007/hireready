<?php
// Session removed for now — no DB connected yet
// TODO: Add session_start() and DB check when ready
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireReady — Find Jobs. Prove Skills. Get Hired.</title>
    <link rel="stylesheet" href="landingStyles.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════ -->
<nav class="navbar">
    <a href="index.php" class="nav-logo">HireReady</a>

    <ul class="nav-links">
        <li><a href="jobs.php">Jobs</a></li>
        <li><a href="courses.php">Courses</a></li>
        <li><a href="#how">How It Works</a></li>
    </ul>

    <div class="nav-buttons">
        <!-- Always show these until DB is connected -->
        <a href="register.php" class="btn-nav-signup">Sign Up</a>
        <a href="login.php"    class="btn-nav-login">Log In</a>

        <!-- TODO: Uncomment this when DB is connected
        <?php /*
        if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php" class="btn-nav-signup">Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn-nav-signup">Sign Up</a>
            <a href="login.php"    class="btn-nav-login">Log In</a>
        <?php endif;
        */ ?>
        -->
    </div>
</nav>


<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section class="hero">
    <h1 id="heroTitle"></h1>

    <p>
        A smarter job platform that tests your skills, improves your
        weaknesses, and helps you apply with confidence
    </p>

    <div class="hero-buttons">
        <!-- Always show these until DB is connected -->
        <a href="register.php" class="btn-signup">Sign Up</a>
        <a href="login.php"    class="btn-login">Log In</a>

        <!-- TODO: Uncomment this when DB is connected
        <?php /*
        if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php" class="btn-signup">Go to Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn-signup">Sign Up</a>
            <a href="login.php"    class="btn-login">Log In</a>
        <?php endif;
        */ ?>
        -->
    </div>
</section>


<!-- ══════════════════════════════════════════
     FEATURE CARDS
══════════════════════════════════════════ -->
<section class="cards-section">
    <div class="cards-grid">

        <!-- Card 1 — Teal -->
        <div class="feature-card card-teal">
            <h2 class="card-title">Explore<br>Opportunities</h2>
            <p class="card-desc">
                Browse jobs tailored<br>
                to your interests and<br>
                skills.
            </p>
            <div class="color-blocks">
                <div class="color-block b2"></div>
                <div class="color-block b3"></div>
                <div class="color-block b4"></div>
                <div class="color-block b5"></div>
                <div class="color-block b6"></div>
            </div>
        </div>

        <!-- Card 2 — Peach -->
        <div class="feature-card card-peach">
            <h2 class="card-title">Take Skill<br>Assessments</h2>
            <div class="color-blocks">
                <div class="color-block b1"></div>
                <div class="color-block b2"></div>
                <div class="color-block b3"></div>
                <div class="color-block b4"></div>
            </div>
            <p class="card-desc" style="margin-top: 56px;">
                Complete short quizzes<br>
                designed for each job<br>
                role.
            </p>
        </div>

        <!-- Card 3 — Blue -->
        <div class="feature-card card-blue">
            <h2 class="card-title">Learn &amp;<br>Improve</h2>
            <p class="card-desc">
                Get simple courses to<br>
                improve exactly where<br>
                you need help.
            </p>
            <div class="color-blocks">
                <div class="color-block b1"></div>
                <div class="color-block b2"></div>
                <div class="color-block b3"></div>
                <div class="color-block b4"></div>
            </div>
        </div>

        <!-- Card 4 — Green -->
        <div class="feature-card card-green">
            <h2 class="card-title">Apply with<br>Confidence</h2>
            <p class="card-desc">
                Send your CV when<br>
                you're ready and stand<br>
                out from the crowd.
            </p>
            <div class="color-blocks">
                <div class="color-block b1"></div>
                <div class="color-block b2"></div>
                <div class="color-block b3"></div>
                <div class="color-block b4"></div>
                <div class="color-block b5"></div>
            </div>
        </div>

    </div>
</section>


<!-- ══════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════ -->
<script>
// ── Hero title word-by-word animation ────────
(function() {
    const title = "Find Jobs. Prove Skills. Get Hired.";
    const words = title.split(" ");
    const el    = document.getElementById("heroTitle");

    words.forEach((word, i) => {
        const span = document.createElement("span");
        span.classList.add("word");
        span.textContent = word + (i < words.length - 1 ? "\u00A0" : "");
        span.style.animationDelay = (0.15 + i * 0.1) + "s";
        el.appendChild(span);
    });
})();


// ── Card scroll-in animation ──────────────────
(function() {
    const cards    = document.querySelectorAll(".feature-card");
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    cards.forEach(card => observer.observe(card));
})();


// ── Card tilt on mouse move ───────────────────
document.querySelectorAll(".feature-card").forEach(card => {
    card.addEventListener("mousemove", function(e) {
        const rect    = card.getBoundingClientRect();
        const x       = e.clientX - rect.left;
        const y       = e.clientY - rect.top;
        const centerX = rect.width  / 2;
        const centerY = rect.height / 2;
        const rotateX = ((y - centerY) / centerY) * -6;
        const rotateY = ((x - centerX) / centerX) *  6;

        card.style.transform =
            `translateY(-8px) scale(1.02)
             rotateX(${rotateX}deg)
             rotateY(${rotateY}deg)`;
        card.style.transition = "transform 0.1s ease";
    });

    card.addEventListener("mouseleave", function() {
        card.style.transform  = "translateY(0) scale(1) rotateX(0) rotateY(0)";
        card.style.transition = "transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)";
    });
});


// ── Color block stagger on card hover ────────
document.querySelectorAll(".feature-card").forEach(card => {
    const blocks = card.querySelectorAll(".color-block");

    card.addEventListener("mouseenter", () => {
        blocks.forEach((block, i) => {
            setTimeout(() => {
                block.style.transform  = "scale(1.2)";
                block.style.transition = "transform 0.2s ease";
            }, i * 40);
        });
    });

    card.addEventListener("mouseleave", () => {
        blocks.forEach(block => {
            block.style.transform  = "scale(1)";
            block.style.transition = "transform 0.3s ease";
        });
    });
});
</script>

</body>
</html>