/* ================================================
   QUIZ DATA — injected by PHP from quiz.php
   (questionsData, quizMeta defined in quiz.php)
================================================ */
const questions = questionsData;


/* ================================================
   STATE
================================================ */
const TOTAL_TIME = (quizMeta.timeLimit > 0 ? quizMeta.timeLimit : 30) * 60; // seconds
let timeLeft      = TOTAL_TIME;
let timerInterval = null;
let currentIndex  = 0;
const answers     = {}; // { questionIndex: value }
const startTime   = Date.now();

/* ================================================
   INIT
================================================ */
document.addEventListener("DOMContentLoaded", () => {
  loadJobContext();
  buildProgressDots();
  buildQuestions();
  showQuestion(0);
  startTimer();
});

/* ================================================
   LOAD JOB CONTEXT from sessionStorage
   (set by dashboard.js when Apply Now is clicked)
================================================ */
function loadJobContext() {
  const jobTitle  = quizMeta.jobTitle  || "Job Application";
  const company   = quizMeta.company   || "";
  const jobId     = quizMeta.jobId     || sessionStorage.getItem("quiz_job_id") || "0";

  // Set hidden job ID field
  document.getElementById("h-job-id").value = jobId;

  // Update quiz header
  document.getElementById("quizTitle").textContent =
    (quizMeta.quizTitle || jobTitle) + " — Skill Quiz";
  document.getElementById("quizCompany").textContent = company;

  // Update navbar pill
  document.getElementById("jobContext").innerHTML = `
    <div class="job-context-pill">
      <i class="fas fa-briefcase" style="font-size:11px"></i>
      Applying for: <span>${jobTitle} @ ${company}</span>
    </div>
  `;

  // Update meta info (question count, time limit)
  const totalQ = questions.length;
  document.querySelectorAll(".quiz-meta-item span")[0].textContent = `${totalQ} Questions`;
  document.querySelectorAll(".quiz-meta-item span")[1].textContent =
    `${quizMeta.timeLimit || 30} Minutes`;
  document.getElementById("navCenterText").innerHTML =
    `Question <span id="currentQ">1</span> of ${totalQ}`;
}

/* ================================================
   BUILD PROGRESS DOTS
================================================ */
function buildProgressDots() {
  const wrap = document.getElementById("progressDots");
  wrap.innerHTML = "";

  questions.forEach((_, i) => {
    const dot = document.createElement("div");
    dot.className = "prog-dot";
    dot.id = `dot-${i}`;
    dot.title = `Question ${i + 1}`;
    dot.onclick = () => jumpToQuestion(i);
    wrap.appendChild(dot);
  });
}

/* ================================================
   BUILD ALL QUESTION PANELS (hidden by default)
================================================ */
function buildQuestions() {
  const wrap = document.getElementById("questionsWrap");
  wrap.innerHTML = "";

  questions.forEach((q, i) => {
    const panel = document.createElement("div");
    panel.className = "question-panel";
    panel.id = `question-${i}`;

    const typeLabel =
      q.type === "mc"   ? "Multiple Choice" :
      q.type === "tf"   ? "True / False"    :
      q.type === "code" ? "Code Answer"     : "Written Answer";

    const typeClass =
      (q.type === "mc" || q.type === "tf") ? "q-type-mc"   :
      q.type === "code" ? "q-type-code" : "q-type-text";

    let inputHTML = "";

    if (q.type === "mc" || q.type === "tf") {
      const letters = ["A", "B", "C", "D"];
      inputHTML = `<div class="mc-options">` +
        q.options.map((opt, oi) => `
          <div class="mc-option" id="opt-${i}-${oi}"
            onclick="selectMC(${i}, ${oi})">
            <div class="option-letter">${letters[oi]}</div>
            <div class="option-text">${opt}</div>
          </div>
        `).join("") +
      `</div>`;

    } else if (q.type === "text") {
      inputHTML = `
        <div class="text-input-wrap">
          <textarea
            class="q-textarea"
            id="text-${i}"
            placeholder="${q.placeholder}"
            oninput="handleTextInput(${i}, this)"
            rows="5"
          ></textarea>
          <div class="char-count" id="chars-${i}">0 characters</div>
        </div>
      `;

    } else if (q.type === "code") {
      inputHTML = `
        <div class="code-label">
          <div class="code-dot" style="background:#ef4444"></div>
          <div class="code-dot" style="background:#f59e0b; margin-left:4px"></div>
          <div class="code-dot" style="background:#22c55e; margin-left:4px"></div>
          <span style="margin-left:12px">javascript</span>
        </div>
        <textarea
          class="q-code-input has-label"
          id="code-${i}"
          placeholder="${q.placeholder}"
          oninput="handleCodeInput(${i}, this)"
          rows="7"
          spellcheck="false"
        ></textarea>
        ${q.hint ? `
          <div class="q-hint">
            <i class="fas fa-lightbulb"></i>
            <span>${q.hint}</span>
          </div>
        ` : ""}
      `;
    }

    panel.innerHTML = `
      <div class="question-card">
        <div class="q-number">
          Question ${i + 1} of ${questions.length}
          <span class="q-type-badge ${typeClass}">${typeLabel}</span>
        </div>
        <p class="q-text">${q.text}</p>
        ${inputHTML}
      </div>
    `;

    wrap.appendChild(panel);
  });
}

/* ================================================
   SHOW A SPECIFIC QUESTION
================================================ */
function showQuestion(index) {
  // Hide all
  document.querySelectorAll(".question-panel").forEach((p) =>
    p.classList.remove("active")
  );

  // Show target
  document.getElementById(`question-${index}`).classList.add("active");
  currentIndex = index;

  // Update dots
  updateDots();

  // Update nav buttons
  const btnBack = document.getElementById("btnBack");
  const btnNext = document.getElementById("btnNext");
  const navText = document.getElementById("currentQ");

  btnBack.style.visibility = index === 0 ? "hidden" : "visible";
  navText.textContent = index + 1;

  // Show/hide Next vs Submit wrap
  const isLast = index === questions.length - 1;
  btnNext.style.display    = isLast ? "none"  : "flex";
  document.getElementById("submitWrap").style.display =
    isLast ? "flex" : "none";

  if (isLast) updateSubmitSummary();

  // Update timer bar question counter
  document.getElementById("timerProgress").textContent =
    `Question ${index + 1} of ${questions.length}`;

  // Scroll to top
  window.scrollTo({ top: 0, behavior: "smooth" });
}

/* ================================================
   NAVIGATION
================================================ */
function nextQuestion() {
  if (currentIndex < questions.length - 1) {
    showQuestion(currentIndex + 1);
  }
}

function prevQuestion() {
  if (currentIndex > 0) {
    showQuestion(currentIndex - 1);
  }
}

function jumpToQuestion(index) {
  showQuestion(index);
}

/* ================================================
   MULTIPLE CHOICE SELECTION
================================================ */
function selectMC(questionIndex, optionIndex) {
  // Deselect all options for this question
  const q = questions[questionIndex];
  q.options.forEach((_, oi) => {
    const el = document.getElementById(`opt-${questionIndex}-${oi}`);
    if (el) el.classList.remove("selected");
  });

  // Select clicked option
  document.getElementById(`opt-${questionIndex}-${optionIndex}`)
    .classList.add("selected");

  // Save answer
  answers[questionIndex] = optionIndex;
  updateDots();
}

/* ================================================
   TEXT INPUT HANDLER
================================================ */
function handleTextInput(questionIndex, textarea) {
  const val = textarea.value.trim();
  answers[questionIndex] = val;

  // Update char count
  const chars = textarea.value.length;
  document.getElementById(`chars-${questionIndex}`).textContent =
    `${chars} character${chars !== 1 ? "s" : ""}`;

  updateDots();
}

/* ================================================
   CODE INPUT HANDLER
================================================ */
function handleCodeInput(questionIndex, textarea) {
  answers[questionIndex] = textarea.value;
  updateDots();
}

/* ================================================
   UPDATE PROGRESS DOTS
================================================ */
function updateDots() {
  questions.forEach((_, i) => {
    const dot = document.getElementById(`dot-${i}`);
    if (!dot) return;

    dot.classList.remove("active", "answered");

    const hasAnswer =
      answers[i] !== undefined &&
      answers[i] !== null &&
      answers[i] !== "";

    if (hasAnswer)        dot.classList.add("answered");
    if (i === currentIndex) dot.classList.add("active");
  });
}

/* ================================================
   TIMER
================================================ */
function startTimer() {
  updateTimerDisplay();

  timerInterval = setInterval(() => {
    timeLeft--;
    updateTimerDisplay();

    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      triggerTimeout();
    }
  }, 1000);
}

function updateTimerDisplay() {
  const mins = Math.floor(timeLeft / 60);
  const secs = timeLeft % 60;
  const display = `${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;

  document.getElementById("timerDisplay").textContent = display;

  // Update progress bar (shrinks left to right)
  const pct = (timeLeft / TOTAL_TIME) * 100;
  document.getElementById("timerFill").style.width = pct + "%";

  // State changes based on time left
  const timerBar = document.getElementById("timerBar");
  timerBar.classList.remove("warning", "danger");

  if (timeLeft <= 300 && timeLeft > 60) {
    // Under 5 mins — warning
    timerBar.classList.add("warning");
  } else if (timeLeft <= 60) {
    // Under 1 min — danger
    timerBar.classList.add("danger");
  }
}

function triggerTimeout() {
  document.getElementById("timeoutModal").classList.add("open");
}

/* ================================================
   SUBMIT SUMMARY
================================================ */
function updateSubmitSummary() {
  const answered = Object.keys(answers).filter(
    (k) => answers[k] !== undefined && answers[k] !== ""
  ).length;

  const skipped  = questions.length - answered;
  const elapsed  = Math.floor((Date.now() - startTime) / 1000);
  const mins     = Math.floor(elapsed / 60);
  const secs     = elapsed % 60;

  document.getElementById("submitSummary").innerHTML = `
    <div class="summary-item summary-answered">
      <div class="summary-num">${answered}</div>
      <div class="summary-label">Answered</div>
    </div>
    <div class="summary-item summary-skipped">
      <div class="summary-num">${skipped}</div>
      <div class="summary-label">Skipped</div>
    </div>
    <div class="summary-item summary-time">
      <div class="summary-num">${String(mins).padStart(2,"0")}:${String(secs).padStart(2,"0")}</div>
      <div class="summary-label">Time Spent</div>
    </div>
  `;
}

/* ================================================
   SUBMIT QUIZ
================================================ */
function submitQuiz() {
  updateSubmitSummary();
  document.getElementById("confirmModal").classList.add("open");

  // Update confirm modal text
  const answered = Object.keys(answers).filter(
    (k) => answers[k] !== undefined && answers[k] !== ""
  ).length;
  const skipped = questions.length - answered;

  document.getElementById("confirmText").textContent =
    skipped > 0
      ? `You answered ${answered} of ${questions.length} questions (${skipped} skipped). Ready to submit?`
      : `You answered all ${questions.length} questions. Ready to submit?`;
}

function forceSubmit() {
  clearInterval(timerInterval);

  // Calculate score (MC/TF auto-graded by marks; text graded manually)
  let score = 0;
  questions.forEach((q, i) => {
    if ((q.type === "mc" || q.type === "tf") && answers[i] === q.correct) {
      score += (q.mark || 1);
    }
  });

  // Fill hidden fields
  const elapsed = Math.floor((Date.now() - startTime) / 1000);
  document.getElementById("h-score").value   = score;
  document.getElementById("h-time").value    = elapsed;
  document.getElementById("h-answers").value = JSON.stringify(answers);

  // Submit the form → goes to PHP handler at top of quiz.php
  document.getElementById("quizForm").submit();
}