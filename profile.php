<?php
session_start();

// ── Auth guard ──────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hireready_db');

// ── Database Save Handler ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
    $field  = trim($_POST['field'] ?? '');
    $cvData = trim($_POST['cv_data'] ?? '');
    $userId = (int)$_SESSION['user_id'];

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
        
        // Ensure cv_data column exists
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'cv_data'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN cv_data LONGTEXT DEFAULT NULL");
        }

        $stmt = $conn->prepare("UPDATE users SET field=?, cv_data=? WHERE id=?");
        $stmt->bind_param('ssi', $field, $cvData, $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        $_SESSION['field'] = $field;
    }
    // Return json for AJAX save success
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// ── Get CV Data to populate form ──────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
$userId = (int)$_SESSION['user_id'];
$cvStmt = $conn->prepare("SELECT cv_data FROM users WHERE id = ?");
$cvStmt->bind_param('i', $userId);
$cvStmt->execute();
$res = $cvStmt->get_result()->fetch_assoc();
$cvStmt->close();
$conn->close();
$cvDataJson = $res['cv_data'] ?? '{}';

// ── Capture dashboard.php output ────────────────────────────
ob_start();
include 'dashboard.php';
$dashboard_html = ob_get_clean();

// ── Construct Modal & Javascript Injection ──────────────────
$modal_markup = '
<!-- ── Profile Edit Overlay and Modal ── -->
<script>
  tailwind = {
    config: {
      corePlugins: {
        preflight: false,
      }
    }
  }
</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .profile-edit-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
    }
    .tab-btn.active {
        color: #111 !important;
        border-color: #111 !important;
    }
    .modal-content-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .modal-content-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .modal-content-scroll::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 99px;
    }
    .modal-content-scroll::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
    .edit-chip.selected {
        background: #111 !important;
        color: #fff !important;
        border-color: #111 !important;
    }
</style>

<div class="profile-edit-overlay">
  <div class="bg-white rounded-2xl w-11/12 max-w-2xl max-h-[85vh] shadow-2xl border border-gray-100 flex flex-col overflow-hidden font-sans text-left">
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 flex-shrink-0">
      <div>
        <h2 class="text-base font-black text-gray-900 leading-tight m-0">Edit Profile &amp; CV Data</h2>
        <p class="text-[11px] text-gray-400 m-0 mt-0.5">Keep your details updated for recommendations &amp; search</p>
      </div>
      <button type="button" class="text-gray-400 hover:text-red-500 font-bold text-xl leading-none transition border-none bg-transparent cursor-pointer outline-none" onclick="window.location.href=\'dashboard.php\'">×</button>
    </div>

    <!-- Modal Tabs -->
    <div class="flex border-b border-gray-100 bg-gray-50 px-4 py-1.5 gap-1.5 overflow-x-auto flex-shrink-0">
      <button type="button" id="tabBtn-career" class="tab-btn active px-3 py-1.5 text-xs font-bold text-gray-400 border-b-2 border-transparent transition outline-none cursor-pointer bg-transparent" onclick="switchEditTab(\'career\')">Career &amp; Goals</button>
      <button type="button" id="tabBtn-skills" class="tab-btn px-3 py-1.5 text-xs font-bold text-gray-400 border-b-2 border-transparent transition outline-none cursor-pointer bg-transparent" onclick="switchEditTab(\'skills\')">Skills &amp; Tech</button>
      <button type="button" id="tabBtn-projects" class="tab-btn px-3 py-1.5 text-xs font-bold text-gray-400 border-b-2 border-transparent transition outline-none cursor-pointer bg-transparent" onclick="switchEditTab(\'projects\')">Projects &amp; Links</button>
      <button type="button" id="tabBtn-education" class="tab-btn px-3 py-1.5 text-xs font-bold text-gray-400 border-b-2 border-transparent transition outline-none cursor-pointer bg-transparent" onclick="switchEditTab(\'education\')">Edu &amp; Summary</button>
    </div>

    <form id="editProfileForm" onsubmit="saveProfile(event)" class="flex-1 flex flex-col overflow-hidden m-0">
      <!-- Modal Body (scrollable) -->
      <div class="flex-1 overflow-y-auto p-6 modal-content-scroll flex flex-col gap-4">
        
        <!-- Tab 1: Career & Goals -->
        <div id="tabPanel-career" class="tab-panel flex flex-col gap-4">
          <div>
            <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Technology Field Interest</label>
            <select id="editField" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
              <option>Web Development</option>
              <option>Mobile Development</option>
              <option>Data Science</option>
              <option>Cybersecurity</option>
              <option>Cloud &amp; DevOps</option>
              <option>UI/UX Design</option>
              <option>Artificial Intelligence</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Target Role</label>
            <select id="editRole" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
              <option>Frontend Developer</option>
              <option>Backend Developer</option>
              <option>Full Stack Developer</option>
              <option>Mobile Developer</option>
              <option>Data Analyst</option>
              <option>Data Scientist</option>
              <option>Cybersecurity Analyst</option>
              <option>DevOps Engineer</option>
              <option>UI/UX Designer</option>
              <option>AI/ML Engineer</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Experience Level</label>
              <select id="editExperience" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                <option>Beginner</option>
                <option>Intermediate</option>
                <option>Advanced</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Current Status</label>
              <select id="editStatus" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                <option>Student</option>
                <option>Fresh Graduate</option>
                <option>Working Professional</option>
                <option>Career Switcher</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Primary Goal</label>
              <select id="editPrimaryGoal" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                <option>Get a Job Quickly</option>
                <option>Improve Skills First</option>
                <option>Find an Internship</option>
                <option>Explore Career Options</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Weekly Availability</label>
              <select id="editAvailability" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                <option>Less than 5 hours</option>
                <option>5-10 hours</option>
                <option>10+ hours</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Preferred Work Arrangement</label>
              <select id="editWorkArrangement" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                <option>Remote</option>
                <option>Hybrid</option>
                <option>On-site</option>
              </select>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Preferred Employment Type</label>
              <select id="editEmploymentType" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white">
                <option>Full-time</option>
                <option>Internship</option>
                <option>Contract</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Tab 2: Skills & Tech -->
        <div id="tabPanel-skills" class="tab-panel hidden flex flex-col gap-4">
          <div>
            <label class="block text-[10px] font-bold text-gray-700 mb-2.5 uppercase tracking-wider">Rate Confidence (1-5)</label>
            <div class="flex flex-col gap-3">
              <div>
                <div class="flex justify-between items-center mb-1">
                  <span class="text-xs font-semibold text-gray-600">Programming</span>
                  <span id="editProgVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                </div>
                <input type="range" id="editSkillProg" class="skill-slider" min="1" max="5" value="1" oninput="updateEditSlider(this,\'editProgVal\')">
              </div>
              <div>
                <div class="flex justify-between items-center mb-1">
                  <span class="text-xs font-semibold text-gray-600">Databases</span>
                  <span id="editDbVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                </div>
                <input type="range" id="editSkillDb" class="skill-slider" min="1" max="5" value="1" oninput="updateEditSlider(this,\'editDbVal\')">
              </div>
              <div>
                <div class="flex justify-between items-center mb-1">
                  <span class="text-xs font-semibold text-gray-600">Problem Solving</span>
                  <span id="editPsVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                </div>
                <input type="range" id="editSkillPs" class="skill-slider" min="1" max="5" value="1" oninput="updateEditSlider(this,\'editPsVal\')">
              </div>
              <div>
                <div class="flex justify-between items-center mb-1">
                  <span class="text-xs font-semibold text-gray-600">Communication</span>
                  <span id="editCommVal" class="text-xs font-bold text-white bg-gray-900 px-2 py-0.5 rounded-full">1 / 5</span>
                </div>
                <input type="range" id="editSkillComm" class="skill-slider" min="1" max="5" value="1" oninput="updateEditSlider(this,\'editCommVal\')">
              </div>
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Selected Technologies</label>
            <div class="flex flex-wrap gap-1.5 p-2 border border-gray-200 rounded-lg max-h-48 overflow-y-auto bg-white">
              ';
              $techs = [
                  'HTML', 'CSS', 'JavaScript', 'TypeScript', 'PHP', 'Python', 'Java', 'C#',
                  'React', 'Vue', 'Node.js', 'Django', 'Laravel', 'MySQL', 'PostgreSQL', 
                  'MongoDB', 'Docker', 'Git', 'Linux', 'AWS', 'Swift', 'Kotlin', 'Go', 'Flutter'
              ];
              foreach ($techs as $t) {
                  $modal_markup .= '<div class="edit-chip skill-chip" onclick="toggleEditChip(this,\'' . $t . '\')">' . $t . '</div>';
              }
$modal_markup .= '
            </div>
          </div>
        </div>

        <!-- Tab 3: Projects & Links -->
        <div id="tabPanel-projects" class="tab-panel hidden flex flex-col gap-4">
          <div class="flex flex-col gap-3">
            <h3 class="text-[10px] font-bold text-gray-800 uppercase tracking-wider border-b border-gray-100 pb-1 m-0">Profile Links</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">GitHub Profile URL</label>
                <input type="url" id="editLinkGithub" placeholder="https://github.com/username" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">LinkedIn Profile URL</label>
                <input type="url" id="editLinkLinkedin" placeholder="https://linkedin.com/in/username" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Portfolio Website URL (optional)</label>
              <input type="url" id="editLinkPortfolio" placeholder="https://myportfolio.com" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
            </div>
          </div>

          <div class="border-t border-gray-100 pt-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
              <h3 class="text-[10px] font-bold text-gray-800 uppercase tracking-wider m-0">Dynamic Projects</h3>
              <select id="editHasProjects" class="border border-gray-200 rounded p-1 text-xs focus:border-black outline-none bg-white" onchange="toggleEditProjectsSection(this.value === \'yes\')">
                <option value="yes">Yes, show projects</option>
                <option value="no">No, hide projects</option>
              </select>
            </div>
            <div id="editProjectsContainer" class="flex flex-col gap-3">
              <div id="editProjectList" class="flex flex-col gap-3 max-h-48 overflow-y-auto p-1">
                <!-- project blocks inserted here -->
              </div>
              <button type="button" onclick="addEditProjectInputBlock()" class="w-full py-1.5 border border-dashed border-gray-300 rounded-lg text-xs font-bold text-gray-500 hover:border-black hover:text-black transition flex items-center justify-center gap-1.5 cursor-pointer bg-transparent">
                + Add Another Project
              </button>
            </div>
          </div>
        </div>

        <!-- Tab 4: Education & Summary -->
        <div id="tabPanel-education" class="tab-panel hidden flex flex-col gap-4">
          <div class="flex flex-col gap-3">
            <h3 class="text-[10px] font-bold text-gray-800 uppercase tracking-wider border-b border-gray-100 pb-1 m-0">Education Credentials</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Education Level</label>
                <select id="editEduLevel" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
                  <option>Diploma</option>
                  <option>Bachelor\'s</option>
                  <option>Master\'s</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Graduation Year</label>
                <input type="number" id="editEduYear" placeholder="2026" min="1990" max="2035" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Institution Name</label>
              <input type="text" id="editEduInstitution" placeholder="e.g. University of Dhaka" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Degree / Program of Study</label>
              <input type="text" id="editEduDegree" placeholder="e.g. Computer Science &amp; Engineering" class="w-full border border-gray-200 rounded-lg p-2 text-sm focus:border-black outline-none bg-white">
            </div>
          </div>

          <div class="border-t border-gray-100 pt-4">
            <label class="block text-[10px] font-bold text-gray-700 mb-1 uppercase tracking-wider">Professional CV Summary</label>
            <textarea id="editProfSummary" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm focus:border-black outline-none bg-white h-24 resize-none" placeholder="Describe yourself..."></textarea>
            <p class="text-[10px] text-gray-400 mt-1 m-0">Briefly summarize your strengths and targets (min. 15 chars).</p>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50 flex-shrink-0">
        <button type="button" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 hover:border-black transition cursor-pointer bg-transparent" onclick="window.location.href=\'dashboard.php\'">Cancel</button>
        <button type="submit" id="saveBtn" class="px-4 py-2 bg-black text-white rounded-lg text-sm font-bold hover:bg-gray-800 border-none transition cursor-pointer">Save Changes</button>
      </div>
    </form>

  </div>
</div>

<script>
// Initial values from PHP
const initialCvData = ' . $cvDataJson . ';

// State
const survey = {
    field:            null,
    role:             null,
    experience_level: null,
    current_status:   null,
    technologies:     [],
    skill_prog:       1,
    skill_db:         1,
    skill_ps:         1,
    skill_comm:       1,
    has_projects:     null,
    projects:         [],
    link_github:      "",
    link_linkedin:    "",
    link_portfolio:   "",
    education_level:  null,
    edu_institution:  "",
    edu_degree:       "",
    edu_year:         "",
    summary:          ""
};

// Switch tab inside modal
function switchEditTab(tab) {
    document.querySelectorAll(".tab-btn").forEach(btn => {
        btn.classList.remove("active", "border-black");
        btn.classList.add("text-gray-400");
    });
    const activeBtn = document.getElementById("tabBtn-" + tab);
    if (activeBtn) {
        activeBtn.classList.add("active", "border-black");
        activeBtn.classList.remove("text-gray-400");
    }

    document.querySelectorAll(".tab-panel").forEach(panel => {
        panel.classList.add("hidden");
    });
    const activePanel = document.getElementById("tabPanel-" + tab);
    if (activePanel) activePanel.classList.remove("hidden");
}

// Toggle chip selection in edit view
function toggleEditChip(el, value) {
    el.classList.toggle("selected");
    if (el.classList.contains("selected")) {
        if (!survey.technologies.includes(value)) {
            survey.technologies.push(value);
        }
    } else {
        survey.technologies = survey.technologies.filter(t => t !== value);
    }
}

// Update slider visual fill
function updateEditSlider(input, labelId) {
    const val = input.value;
    document.getElementById(labelId).textContent = val + " / 5";
    const pct = ((val - 1) / 4) * 100;
    input.style.background = `linear-gradient(to right, #111 ${pct}%, #e5e7eb ${pct}%)`;
}

// Toggle projects list view
function toggleEditProjectsSection(show) {
    const container = document.getElementById("editProjectsContainer");
    if (show) {
        container.classList.remove("hidden");
        survey.has_projects = true;
        if (document.getElementById("editProjectList").children.length === 0) {
            addEditProjectInputBlock();
        }
    } else {
        container.classList.add("hidden");
        survey.has_projects = false;
    }
}

// Add dynamic project card
let editProjectCount = 0;
function addEditProjectInputBlock(name = "", desc = "", techs = "", github = "") {
    editProjectCount++;
    const list = document.getElementById("editProjectList");
    const div = document.createElement("div");
    div.className = "project-block bg-gray-50 border border-gray-100 rounded-lg p-3 relative flex flex-col gap-2 mt-2";
    div.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-2.5 right-3 text-gray-400 hover:text-red-500 border-none bg-transparent cursor-pointer font-bold text-sm">×</button>
        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Project #${editProjectCount}</span>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase mb-1">Project Name</label>
            <input type="text" class="proj-name w-full border border-gray-200 rounded-md p-1.5 text-xs focus:border-black outline-none bg-white" placeholder="e.g. E-Commerce Backend" value="\${name}">
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase mb-1">Project Description</label>
            <textarea class="proj-desc w-full border border-gray-200 rounded-md p-1.5 text-xs focus:border-black outline-none bg-white h-12 resize-none" placeholder="Describe what you built...">\${desc}</textarea>
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase mb-1">Technologies Used</label>
            <input type="text" class="proj-techs w-full border border-gray-200 rounded-md p-1.5 text-xs focus:border-black outline-none bg-white" placeholder="e.g. React, Node.js" value="\${techs}">
        </div>
        <div>
            <label class="block text-[9px] font-bold text-gray-500 uppercase mb-1">GitHub Link (optional)</label>
            <input type="url" class="proj-github w-full border border-gray-200 rounded-md p-1.5 text-xs focus:border-black outline-none bg-white" placeholder="https://github.com/..." value="\${github}">
        </div>
    `;
    list.appendChild(div);
}

// Populate Edit Form fields on load
function populateEditForm() {
    // 1. Career
    if (initialCvData.field) document.getElementById("editField").value = initialCvData.field;
    if (initialCvData.role) document.getElementById("editRole").value = initialCvData.role;
    if (initialCvData.experience_level) document.getElementById("editExperience").value = initialCvData.experience_level;
    if (initialCvData.current_status) document.getElementById("editStatus").value = initialCvData.current_status;
    if (initialCvData.primary_goal) document.getElementById("editPrimaryGoal").value = initialCvData.primary_goal;
    if (initialCvData.availability) document.getElementById("editAvailability").value = initialCvData.availability;
    if (initialCvData.work_arrangement) document.getElementById("editWorkArrangement").value = initialCvData.work_arrangement;
    if (initialCvData.employment_type) document.getElementById("editEmploymentType").value = initialCvData.employment_type;

    // 2. Skills sliders & chips
    if (initialCvData.skill_prog) {
        document.getElementById("editSkillProg").value = initialCvData.skill_prog;
        updateEditSlider(document.getElementById("editSkillProg"), "editProgVal");
    }
    if (initialCvData.skill_db) {
        document.getElementById("editSkillDb").value = initialCvData.skill_db;
        updateEditSlider(document.getElementById("editSkillDb"), "editDbVal");
    }
    if (initialCvData.skill_ps) {
        document.getElementById("editSkillPs").value = initialCvData.skill_ps;
        updateEditSlider(document.getElementById("editSkillPs"), "editPsVal");
    }
    if (initialCvData.skill_comm) {
        document.getElementById("editSkillComm").value = initialCvData.skill_comm;
        updateEditSlider(document.getElementById("editSkillComm"), "editCommVal");
    }

    if (initialCvData.technologies) {
        survey.technologies = [...initialCvData.technologies];
        document.querySelectorAll(".edit-chip").forEach(chip => {
            const val = chip.textContent.trim();
            if (survey.technologies.includes(val)) {
                chip.classList.add("selected");
            }
        });
    }

    // 3. Links & Projects
    if (initialCvData.link_github) document.getElementById("editLinkGithub").value = initialCvData.link_github;
    if (initialCvData.link_linkedin) document.getElementById("editLinkLinkedin").value = initialCvData.link_linkedin;
    if (initialCvData.link_portfolio) document.getElementById("editLinkPortfolio").value = initialCvData.link_portfolio;

    const hasProj = initialCvData.has_projects ?? false;
    document.getElementById("editHasProjects").value = hasProj ? "yes" : "no";
    toggleEditProjectsSection(hasProj);

    if (hasProj && initialCvData.projects) {
        document.getElementById("editProjectList").innerHTML = "";
        initialCvData.projects.forEach(p => {
            addEditProjectInputBlock(p.name, p.desc, p.techs, p.github);
        });
    }

    // 4. Education & Summary
    if (initialCvData.education_level) document.getElementById("editEduLevel").value = initialCvData.education_level;
    if (initialCvData.edu_year) document.getElementById("editEduYear").value = initialCvData.edu_year;
    if (initialCvData.edu_institution) document.getElementById("editEduInstitution").value = initialCvData.edu_institution;
    if (initialCvData.edu_degree) document.getElementById("editEduDegree").value = initialCvData.edu_degree;
    if (initialCvData.summary) document.getElementById("editProfSummary").value = initialCvData.summary;
}

// Compile inputs back into survey state
function compileSurveyState() {
    survey.field = document.getElementById("editField").value;
    survey.role = document.getElementById("editRole").value;
    survey.experience_level = document.getElementById("editExperience").value;
    survey.current_status = document.getElementById("editStatus").value;
    survey.primary_goal = document.getElementById("editPrimaryGoal").value;
    survey.availability = document.getElementById("editAvailability").value;
    survey.work_arrangement = document.getElementById("editWorkArrangement").value;
    survey.employment_type = document.getElementById("editEmploymentType").value;

    survey.skill_prog = parseInt(document.getElementById("editSkillProg").value);
    survey.skill_db = parseInt(document.getElementById("editSkillDb").value);
    survey.skill_ps = parseInt(document.getElementById("editSkillPs").value);
    survey.skill_comm = parseInt(document.getElementById("editSkillComm").value);

    // Tech chips are updated live
    
    survey.link_github = document.getElementById("editLinkGithub").value.trim();
    survey.link_linkedin = document.getElementById("editLinkLinkedin").value.trim();
    survey.link_portfolio = document.getElementById("editLinkPortfolio").value.trim();

    survey.has_projects = (document.getElementById("editHasProjects").value === "yes");
    survey.projects = [];
    if (survey.has_projects) {
        const blocks = document.querySelectorAll("#editProjectList .project-block");
        blocks.forEach(block => {
            const name = block.querySelector(".proj-name").value.trim();
            const desc = block.querySelector(".proj-desc").value.trim();
            const techs = block.querySelector(".proj-techs").value.trim();
            const github = block.querySelector(".proj-github").value.trim();
            if (name && desc) {
                survey.projects.push({ name, desc, techs, github });
            }
        });
    }

    survey.education_level = document.getElementById("editEduLevel").value;
    survey.edu_year = parseInt(document.getElementById("editEduYear").value) || 2026;
    survey.edu_institution = document.getElementById("editEduInstitution").value.trim();
    survey.edu_degree = document.getElementById("editEduDegree").value.trim();

    survey.summary = document.getElementById("editProfSummary").value.trim();
}

// Validate inputs
function validateAll() {
    const gh = document.getElementById("editLinkGithub").value.trim();
    const li = document.getElementById("editLinkLinkedin").value.trim();
    if (!gh) {
        alert("GitHub Profile Link is required.");
        switchEditTab("projects");
        return false;
    }
    if (!li) {
        alert("LinkedIn Profile Link is required.");
        switchEditTab("projects");
        return false;
    }

    if (document.getElementById("editHasProjects").value === "yes") {
        const blocks = document.querySelectorAll("#editProjectList .project-block");
        let projectAdded = false;
        for (const block of blocks) {
            const name = block.querySelector(".proj-name").value.trim();
            const desc = block.querySelector(".proj-desc").value.trim();
            if (name || desc) {
                if (!name || !desc) {
                    alert("Please enter both Name and Description for your projects.");
                    switchEditTab("projects");
                    return false;
                }
                projectAdded = true;
            }
        }
        if (!projectAdded) {
            alert("Please add at least one project, or set completed projects to No.");
            switchEditTab("projects");
            return false;
        }
    }

    const inst = document.getElementById("editEduInstitution").value.trim();
    const degree = document.getElementById("editEduDegree").value.trim();
    const year = document.getElementById("editEduYear").value.trim();
    if (!inst || !degree || !year) {
        alert("Institution, Degree, and Graduation Year are required.");
        switchEditTab("education");
        return false;
    }

    const summary = document.getElementById("editProfSummary").value.trim();
    if (summary.length < 15) {
        alert("Please write a short Professional Summary about yourself (min. 15 chars).");
        switchEditTab("education");
        return false;
    }

    return true;
}

// Show green success alert animation
function showSuccessNotification() {
    const notify = document.createElement("div");
    notify.className = "fixed top-5 right-5 bg-green-500 text-white font-bold px-5 py-3 rounded-lg shadow-xl z-[9999999] flex items-center gap-2 animate-bounce";
    notify.innerHTML = `
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
        <span>Profile updated successfully!</span>
    `;
    document.body.appendChild(notify);
}

// Save profile via AJAX
async function saveProfile(e) {
    e.preventDefault();
    if (!validateAll()) return;

    const btn = document.getElementById("saveBtn");
    btn.disabled = true;
    btn.innerHTML = \'<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Saving...\';

    compileSurveyState();

    const formData = new FormData();
    formData.append("action", "save_profile");
    formData.append("field", survey.field);
    formData.append("cv_data", JSON.stringify(survey));

    try {
        const response = await fetch("profile.php", {
            method: "POST",
            body: formData
        });
        const resData = await response.json();
        if (resData.success) {
            showSuccessNotification();
            setTimeout(() => {
                window.location.href = "dashboard.php";
            }, 1200);
        } else {
            alert("Failed to save profile. Please try again.");
            btn.disabled = false;
            btn.innerHTML = "Save Changes";
        }
    } catch (err) {
        console.error(err);
        alert("An error occurred. Please try again.");
        btn.disabled = false;
        btn.innerHTML = "Save Changes";
    }
}

// Initialize on DOMContentLoaded
document.addEventListener("DOMContentLoaded", () => {
    populateEditForm();
    switchEditTab("career");
});
</script>
';

// Inject modal HTML before </body> tag
$dashboard_html = str_replace('</body>', $modal_markup . '</body>', $dashboard_html);
echo $dashboard_html;
?>
