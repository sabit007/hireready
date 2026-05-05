/* ================================================
   jobsData is injected by PHP via json_encode()
   at the bottom of dashboard.php — no changes needed
   here when DB is connected
================================================ */

/* ================================================
   HELPERS
================================================ */
function getMatchClass(score) {
  if (score >= 80) return "match-high";
  if (score >= 60) return "match-medium";
  return "match-low";
}

function getMatchEmoji(score) {
  if (score >= 80) return "🔥";
  if (score >= 60) return "⚡";
  return "💡";
}

/* ================================================
     JOB MODAL — opens when "View Job" is clicked
  ================================================ */
function openJobModal(jobId) {
  // Find the job from the PHP-injected jobsData array
  const job = jobsData.find((j) => j.id === jobId);
  if (!job) return;

  // Build tags HTML
  const tagsHTML = job.tags
    .map((t) => `<span class="modal-tag">${t}</span>`)
    .join("");

  // Determine match color
  let matchColor = "#10b981"; // green
  if (job.match < 80) matchColor = "#f59e0b"; // amber
  if (job.match < 60) matchColor = "#ef4444"; // red

  document.getElementById("modalContent").innerHTML = `
      <div class="modal-job-header">
        <div class="modal-job-logo"
          style="background:${job.logo_bg}; color:${job.logo_color}">
          ${job.logo_emoji}
        </div>
        <div>
          <p class="modal-job-title">${job.title}</p>
          <p class="modal-job-company">
            ${job.company} &nbsp;·&nbsp; ${job.location}
          </p>
        </div>
      </div>
  
      <div class="modal-match">
        <span class="modal-match-score" style="color:${matchColor}">
          ${job.match}%
        </span>
        <div>
          <p style="font-weight:700; font-size:14px;">Match Score</p>
          <p class="modal-match-label">This job fits your current profile</p>
        </div>
      </div>
  
      <div class="modal-section">
        <h5>About the Role</h5>
        <p>${job.full_desc}</p>
      </div>
  
      <div class="modal-section">
        <h5>Details</h5>
        <p>
          <strong>Type:</strong> ${job.type}
          &nbsp;|&nbsp;
          <strong>Salary:</strong> ${job.salary}
        </p>
      </div>
  
      <div class="modal-section">
        <h5>Skills Required</h5>
        <div class="modal-tags">${tagsHTML}</div>
      </div>
  
      <button class="modal-apply-btn" onclick="goToQuiz(${job.id}, '${job.title}', '${job.company}')">
        Apply Now &nbsp;<i class="fas fa-arrow-right"></i>
      </button>
    `;

  document.getElementById("modalOverlay").classList.add("open");
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("modalOverlay").classList.remove("open");
  document.body.style.overflow = "";
}

function goToQuiz(jobId, jobTitle, company) {
  // Store job info so quiz page knows which job
  sessionStorage.setItem('quiz_job_id',    jobId);
  sessionStorage.setItem('quiz_job_title', jobTitle);
  sessionStorage.setItem('quiz_company',   company);
  window.location.href = 'quiz.php';
}

// Close modal with Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeModal();
});

/* ================================================
     SKILL BAR ANIMATION
     Bars start at 0% (set in PHP/HTML)
     then animate to data-target value on page load
  ================================================ */
function animateSkillBars() {
  setTimeout(() => {
    document.querySelectorAll(".skill-fill").forEach((bar) => {
      bar.style.width = bar.dataset.target + "%";
    });
  }, 300);
}

/* ================================================
     INIT
  ================================================ */
document.addEventListener("DOMContentLoaded", () => {
  animateSkillBars();
});
