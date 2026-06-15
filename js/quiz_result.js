/* ================================================
   FILE SELECT — from clicking the drop zone
================================================ */
function handleFileSelect(input) {
  if (input.files && input.files[0]) {
    displayFile(input.files[0]);
  }
}

/* ================================================
   DRAG AND DROP HANDLERS
================================================ */
function handleDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  document.getElementById("dropZone").classList.add("dragover");
}

function handleDragLeave(e) {
  e.preventDefault();
  document.getElementById("dropZone").classList.remove("dragover");
}

function handleDrop(e) {
  e.preventDefault();
  e.stopPropagation();

  const dropZone = document.getElementById("dropZone");
  dropZone.classList.remove("dragover");

  const file = e.dataTransfer.files[0];
  if (!file) return;

  // Validate type
  const allowed = ["pdf", "doc", "docx"];
  const ext = file.name.split(".").pop().toLowerCase();

  if (!allowed.includes(ext)) {
    showDropError("Only PDF, DOC, and DOCX files are accepted.");
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    showDropError("File too large — maximum size is 5MB.");
    return;
  }

  // Assign to the file input
  const dataTransfer = new DataTransfer();
  dataTransfer.items.add(file);
  document.getElementById("cvFile").files = dataTransfer.files;

  displayFile(file);
}

/* ================================================
   DISPLAY SELECTED FILE IN DROP ZONE
================================================ */
function displayFile(file) {
  const ext  = file.name.split(".").pop().toLowerCase();
  const size = formatFileSize(file.size);

  // Update icon based on type
  const iconEl = document.getElementById("fileTypeIcon");
  if (ext === "pdf") {
    iconEl.className = "fas fa-file-pdf";
  } else {
    iconEl.className = "fas fa-file-word";
  }

  document.getElementById("fileName").textContent = file.name;
  document.getElementById("fileSize").textContent = size;

  // Toggle drop zone display
  document.getElementById("dropContent").style.display  = "none";
  document.getElementById("dropSelected").style.display = "block";

  const dropZone = document.getElementById("dropZone");
  dropZone.classList.add("has-file");
  dropZone.onclick = null; // disable click-to-browse while file selected

  // Enable submit button
  document.getElementById("uploadBtn").disabled = false;
}

/* ================================================
   REMOVE SELECTED FILE
================================================ */
function removeFile(e) {
  e.stopPropagation();

  // Clear file input
  document.getElementById("cvFile").value = "";

  // Toggle back to drop zone default state
  document.getElementById("dropContent").style.display  = "block";
  document.getElementById("dropSelected").style.display = "none";

  const dropZone = document.getElementById("dropZone");
  dropZone.classList.remove("has-file");
  dropZone.onclick = () => document.getElementById("cvFile").click();

  // Disable submit button
  document.getElementById("uploadBtn").disabled = true;
}

/* ================================================
   FORMAT FILE SIZE
================================================ */
function formatFileSize(bytes) {
  if (bytes < 1024)        return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / (1024 * 1024)).toFixed(2) + " MB";
}

/* ================================================
   DROP ZONE ERROR (inline)
================================================ */
function showDropError(msg) {
  // Remove old error if any
  const old = document.getElementById("dropInlineError");
  if (old) old.remove();

  const err = document.createElement("p");
  err.id = "dropInlineError";
  err.style.cssText = `
    color: #ef4444;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
    text-align: center;
  `;
  err.innerHTML = `<i class="fas fa-triangle-exclamation"></i> ${msg}`;
  document.getElementById("dropZone").after(err);

  setTimeout(() => err.remove(), 4000);
}

/* ================================================
   ANIMATE SCORE COUNTER ON LOAD
================================================ */
document.addEventListener("DOMContentLoaded", () => {
  // Animate all .sc-num elements that contain numbers
  document.querySelectorAll(".sc-num").forEach((el) => {
    const raw = el.textContent.trim();
    const num = parseFloat(raw);
    if (isNaN(num)) return; // skip "Pass"/"Fail" or time strings

    el.textContent = "0";
    let start = 0;
    const end = num;
    const duration = 800;
    const step = (end / duration) * 16;

    const interval = setInterval(() => {
      start += step;
      if (start >= end) {
        el.textContent = raw; // restore original (may have % or /)
        clearInterval(interval);
      } else {
        el.textContent = Math.floor(start);
      }
    }, 16);
  });
});