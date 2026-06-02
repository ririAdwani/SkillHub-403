/*
  admin.js — SkillHub Admin Dashboard
  =====================================
  Handles all AJAX interactions: Add, Edit, Delete workshops
  without reloading the page (Fetch API / AJAX as required by assignment).
*/

const $ = (id) => document.getElementById(id);

// ── HELPER: Format date from YYYY-MM-DD to "Jun 25, 2026" ──────────────────
// PHP formats dates on page load, but JS needs to do the same for
// dynamically added/edited rows (those come from the API as raw values)
function formatDate(dateStr) {
  if (!dateStr) return "";
  try {
    // Parse as local date to avoid timezone shifts
    const [y, m, d] = dateStr.split("-").map(Number);
    const date = new Date(y, m - 1, d);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  } catch (e) {
    return dateStr;
  }
}

// ── HELPER: Format time from HH:MM:SS to "9:00 AM" ────────────────────────
function formatTime(timeStr) {
  if (!timeStr) return "";
  try {
    const [h, m] = timeStr.split(":").map(Number);
    const date = new Date();
    date.setHours(h, m, 0);
    return date.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  } catch (e) {
    return timeStr;
  }
}

// ── TOAST NOTIFICATION ──────────────────────────────────────────────────────
function showToast(message, type = "success") {
  const toast = $("admin-toast");
  const iconEl = $("admin-toast-icon");
  const msgEl = $("admin-toast-msg");

  iconEl.innerHTML =
    type === "success"
      ? '<i class="fa-solid fa-circle-check"></i>'
      : '<i class="fa-solid fa-circle-xmark"></i>';

  msgEl.textContent = message;
  toast.className = `admin-toast toast-${type}`;
  toast.hidden = false;

  setTimeout(() => {
    toast.hidden = true;
  }, 3000);
}

// ── MODAL OPEN / CLOSE ──────────────────────────────────────────────────────
function openModal(name) {
  const overlay = $(`${name}-modal-overlay`);
  if (overlay) overlay.hidden = false;
}

function closeModal(name) {
  const overlay = $(`${name}-modal-overlay`);
  if (overlay) overlay.hidden = true;
}

// Close when clicking the dark background overlay
document.querySelectorAll(".admin-overlay").forEach((overlay) => {
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) overlay.hidden = true;
  });
});

// ── OPEN / CLOSE BUTTONS ────────────────────────────────────────────────────
$("open-add-modal").addEventListener("click", () => {
  $("add-workshop-form").reset();
  $("add-form-error").hidden = true;
  openModal("add");
});

$("close-add-modal").addEventListener("click", () => closeModal("add"));
$("cancel-add-modal").addEventListener("click", () => closeModal("add"));
$("close-edit-modal").addEventListener("click", () => closeModal("edit"));
$("cancel-edit-modal").addEventListener("click", () => closeModal("edit"));
$("close-delete-modal").addEventListener("click", () => closeModal("delete"));
$("cancel-delete-modal").addEventListener("click", () => closeModal("delete"));

// ── SINGLE TABLE CLICK HANDLER (Edit + Delete) ──────────────────────────────
// One listener handles both buttons to prevent the double-modal bug
let workshopIdToDelete = null;

$("workshops-tbody").addEventListener("click", (e) => {
  // ── EDIT BUTTON ──
  const editBtn = e.target.closest(".btn-admin-edit");
  if (editBtn) {
    e.stopPropagation();
    const d = editBtn.dataset;

    $("edit-workshop-id").value = d.id;
    $("edit-title").value = d.title;
    $("edit-description").value = d.description;
    $("edit-seats").value = d.seats;
    $("edit-form-error").hidden = true;
    $("edit-date").value = d.date;
    $("edit-start").value = d.start;
    $("edit-end").value = d.end;

    // Set category dropdown to saved value
    const catSelect = $("edit-category");
    catSelect.value = d.category;
    if (catSelect.value !== d.category) {
      setTimeout(() => {
        catSelect.value = d.category;
      }, 10);
    }

    // Set instructor dropdown to saved value
    const instSelect = $("edit-instructor");
    if (instSelect) {
      instSelect.value = d.instructor || "";
    }

    // Pre-fill image URL if one was saved
    const imgInput = $("edit-image");
    if (imgInput) imgInput.value = d.image || "";

    openModal("edit");
    return; // Don't fall through to delete check
  }

  // ── DELETE BUTTON ──
  const deleteBtn = e.target.closest(".btn-admin-delete");
  if (deleteBtn) {
    e.stopPropagation();
    workshopIdToDelete = deleteBtn.dataset.id;
    const row = deleteBtn.closest("tr");
    const workshopName = row
      .querySelector("td:nth-child(2)")
      .textContent.trim();
    $("delete-workshop-name").textContent = workshopName;
    $("delete-form-error").hidden = true;
    openModal("delete");
  }
});

// ── ADD WORKSHOP ─────────────────────────────────────────────────────────────
$("add-workshop-form").addEventListener("submit", async (e) => {
  e.preventDefault(); // Stop page reload — AJAX requirement

  const form = e.target;
  const errorBox = $("add-form-error");
  const submitBtn = form.querySelector("[type=submit]");

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';

  try {
    const formData = new FormData(form);
    formData.append("action", "create");

    // ── FIX: Read instructor name from the dropdown BEFORE sending ──
    // The API response may not return instructor_name if it doesn't JOIN
    // the instructors table. So we grab it from the UI directly here.
    const instrSelect = $("add-instructor");
    const instrId = instrSelect ? instrSelect.value : "";
    const instrName =
      instrSelect && instrSelect.value
        ? instrSelect.options[instrSelect.selectedIndex].text.trim()
        : "";

    const response = await fetch("../../api/workshops.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      // ── FIX: Inject instructor_name and instructor_id into the response
      // object before building the row, because the API may not return them.
      // We use what the admin actually selected in the dropdown.
      result.workshop.instructor_name = instrName;
      result.workshop.instructor_id = instrId;

      // Add the new row to the table immediately without page reload
      appendWorkshopRow(result.workshop);
      updateStatNumbers();
      showToast("Workshop added successfully!", "success");
      closeModal("add");
      form.reset();
    } else {
      errorBox.textContent = result.message || "Something went wrong.";
      errorBox.hidden = false;
    }
  } catch (err) {
    errorBox.textContent = "Network error. Please try again.";
    errorBox.hidden = false;
  }

  submitBtn.disabled = false;
  submitBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Workshop';
});

// ── EDIT WORKSHOP ─────────────────────────────────────────────────────────────
$("edit-workshop-form").addEventListener("submit", async (e) => {
  e.preventDefault();

  const form = e.target;
  const errorBox = $("edit-form-error");
  const submitBtn = form.querySelector("[type=submit]");

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

  try {
    const formData = new FormData(form);
    formData.append("action", "update");

    // ── FIX: Same as add — read instructor name from dropdown before sending
    const instrSelect = $("edit-instructor");
    const instrId = instrSelect ? instrSelect.value : "";
    const instrName =
      instrSelect && instrSelect.value
        ? instrSelect.options[instrSelect.selectedIndex].text.trim()
        : "";

    const response = await fetch("../../api/workshops.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      // ── FIX: Inject instructor name/id from dropdown into response
      result.workshop.instructor_name = instrName;
      result.workshop.instructor_id = instrId;

      // Update existing row in place — no page reload
      updateWorkshopRow(result.workshop);
      showToast("Workshop updated successfully!", "success");
      closeModal("edit");
    } else {
      errorBox.textContent = result.message || "Something went wrong.";
      errorBox.hidden = false;
    }
  } catch (err) {
    errorBox.textContent = "Network error. Please try again.";
    errorBox.hidden = false;
  }

  submitBtn.disabled = false;
  submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Save Changes';
});

// ── DELETE WORKSHOP ─────────────────────────────────────────────────────────
$("confirm-delete-btn").addEventListener("click", async () => {
  if (!workshopIdToDelete) return;

  const errorBox = $("delete-form-error");
  const deleteBtn = $("confirm-delete-btn");

  deleteBtn.disabled = true;
  deleteBtn.innerHTML =
    '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';

  try {
    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("workshop_id", workshopIdToDelete);

    const response = await fetch("../../api/workshops.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      // Remove row from table and update counts immediately
      removeWorkshopRow(workshopIdToDelete);
      updateStatNumbers();
      showToast("Workshop deleted.", "success");
      closeModal("delete");
      workshopIdToDelete = null;
    } else {
      errorBox.textContent = result.message || "Could not delete.";
      errorBox.hidden = false;
    }
  } catch (err) {
    errorBox.textContent = "Network error. Please try again.";
    errorBox.hidden = false;
  }

  deleteBtn.disabled = false;
  deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Yes, Delete';
});

// ── TABLE ROW HELPERS ────────────────────────────────────────────────────────

// Appends a new <tr> to the bottom of the workshops table
function appendWorkshopRow(w) {
  const tbody = $("workshops-tbody");
  const tr = document.createElement("tr");
  tr.dataset.id = w.workshop_id;
  tr.innerHTML = buildRowHTML(w);

  // Insert in correct date order instead of appending at the bottom.
  // Compare the new workshop's date against each existing row's date cell
  // and insert before the first row that has a later date.
  const rows = tbody.querySelectorAll("tr");
  let inserted = false;

  for (const existingRow of rows) {
    // Read the date from the edit button's data-date attribute
    // This is the raw YYYY-MM-DD value — safe to compare as strings
    const editBtn = existingRow.querySelector(".btn-admin-edit");
    if (editBtn) {
      const existingDate = editBtn.dataset.date || "";
      const newDate = w.workshop_date || "";

      if (newDate < existingDate) {
        // New workshop date is earlier — insert before this row
        tbody.insertBefore(tr, existingRow);
        inserted = true;
        break;
      }
    }
  }

  // If no earlier row found, append at the end (it's the latest date)
  if (!inserted) {
    tbody.appendChild(tr);
  }
}
// Replaces the inner HTML of an existing row with updated data
function updateWorkshopRow(w) {
  const row = $("workshops-tbody").querySelector(
    `tr[data-id="${w.workshop_id}"]`,
  );
  if (row) row.innerHTML = buildRowHTML(w);
}

// Removes a row from the table by workshop_id
function removeWorkshopRow(id) {
  const row = $("workshops-tbody").querySelector(`tr[data-id="${id}"]`);
  if (row) row.remove();
}

// ── BUILD ROW HTML ───────────────────────────────────────────────────────────
// Called for both new rows (add) and updated rows (edit).
// Formats dates/times exactly like PHP does on page load.
// instructor_name is now always set before this is called (see add/edit handlers above).
function buildRowHTML(w) {
  // Escape all values to prevent XSS
  const safe = (str) =>
    String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  // Format date and times to match PHP's date() output
  const formattedDate = formatDate(w.workshop_date);
  const formattedStart = formatTime(w.start_time);
  const formattedEnd = formatTime(w.end_time);

  // Instructor cell: show name if assigned, otherwise show "Unassigned"
  // instructor_name is injected from the dropdown before this is called
  const instrCell =
    w.instructor_name && w.instructor_name.trim()
      ? safe(w.instructor_name)
      : '<span class="admin-unassigned">Unassigned</span>';

  return `
    <td class="admin-id-cell">${safe(w.workshop_id)}</td>
    <td class="admin-title-cell">${safe(w.title)}</td>
    <td><span class="admin-category-tag">${safe(w.category_name)}</span></td>
    <td>${formattedDate}</td>
    <td class="admin-time-cell">${formattedStart} – ${formattedEnd}</td>
    <td><span class="admin-seats-badge">${safe(w.available_seats)}</span></td>
    <td class="admin-instructor-cell">${instrCell}</td>
    <td class="admin-action-btns">
      <button class="btn-admin-edit"
        data-id="${safe(w.workshop_id)}"
        data-title="${safe(w.title)}"
        data-description="${safe(w.description)}"
        data-category="${safe(w.category_id)}"
        data-date="${safe(w.workshop_date)}"
        data-start="${safe(w.start_time)}"
        data-end="${safe(w.end_time)}"
        data-seats="${safe(w.available_seats)}"
        data-instructor="${safe(w.instructor_id ?? "")}"
        data-image="${safe(w.image_path ?? "")}">
        <i class="fa-solid fa-pen"></i> Edit
      </button>
      <button class="btn-admin-delete" data-id="${safe(w.workshop_id)}">
        <i class="fa-solid fa-trash"></i> Delete
      </button>
    </td>
  `;
}

// ── UPDATE STAT NUMBERS ──────────────────────────────────────────────────────
// Recalculates the total workshop count and total seats after any change
function updateStatNumbers() {
  const rows = $("workshops-tbody").querySelectorAll("tr");
  let seats = 0;
  rows.forEach((row) => {
    const badge = row.querySelector(".admin-seats-badge");
    if (badge) seats += parseInt(badge.textContent) || 0;
  });
  $("stat-total").textContent = rows.length;
  $("stat-seats").textContent = seats;
}

// ── FEEDBACK REPLY (AJAX — no page reload) ────────────────────────────────
// openReplyBox  — shows the reply textarea for a feedback card
// closeReplyBox — hides it without saving
// submitReply   — sends the reply to the server and updates the UI

function openReplyBox(feedbackId, existingText) {
  const box = document.getElementById("reply-box-" + feedbackId);
  const textarea = document.getElementById("reply-text-" + feedbackId);
  if (!box || !textarea) return;

  textarea.value = existingText || "";
  box.style.setProperty("display", "block", "important");
  box.scrollIntoView({ block: "nearest" });
  textarea.focus();

  // Hide the action buttons row while the reply box is open
  const actions = document.getElementById("reply-actions-" + feedbackId);
  if (actions) actions.style.display = "none";
}

function closeReplyBox(feedbackId) {
  const box = document.getElementById("reply-box-" + feedbackId);
  const actions = document.getElementById("reply-actions-" + feedbackId);
  if (box) box.style.display = "none";
  if (actions) actions.style.display = "";
}

async function submitReply(feedbackId) {
  const textarea = document.getElementById("reply-text-" + feedbackId);
  if (!textarea) return;

  const replyText = textarea.value.trim();
  if (!replyText) {
    showToast("Reply cannot be empty.", "error");
    return;
  }

  // Send reply to server via AJAX
  const formData = new FormData();
  formData.append("action", "reply_feedback");
  formData.append("feedback_id", feedbackId);
  formData.append("admin_reply", replyText);

  try {
    const response = await fetch("admin.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      const card = document
        .getElementById("reply-actions-" + feedbackId)
        .closest(".feedback-card");

      // Get or create the message history container
      let historyContainer = card.querySelector(".feedback-message-history");
      if (!historyContainer) {
        historyContainer = document.createElement("div");
        historyContainer.className = "feedback-message-history";
        card.querySelector(".feedback-reply-actions").before(historyContainer);
      }

      // Remove any old single-reply display if present
      const oldSingle = card.querySelector(
        ".feedback-reply-display:not(.feedback-message-history .feedback-reply-display)",
      );
      if (oldSingle) oldSingle.remove();

      // Build a readable timestamp for the new message bubble
      const now = new Date();
      const timeStr = now.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      });

      // Escape reply text to prevent XSS
      const safeText = replyText
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

      // Create the new message bubble (double-click to edit after page reload)
      const msgDiv = document.createElement("div");
      msgDiv.className = "feedback-reply-display admin-msg-bubble";
      msgDiv.style.marginBottom = "8px";
      msgDiv.title = "Double-click to edit";
      msgDiv.innerHTML =
        '<div class="feedback-reply-label">' +
        '<i class="fa-solid fa-reply"></i> Admin · ' +
        timeStr +
        '<span class="msg-edit-hint">double-click to edit</span>' +
        "</div>" +
        '<p class="msg-text">' +
        safeText +
        "</p>" +
        '<div class="msg-edit-form" style="display:none;margin-top:8px;">' +
        '<textarea class="msg-edit-textarea" rows="2">' +
        safeText +
        "</textarea>" +
        '<div style="display:flex;gap:8px;margin-top:6px;">' +
        '<button class="btn-reply-save" onclick="saveInlineEdit(this,' +
        feedbackId +
        ')">' +
        '<i class="fa-solid fa-check"></i> Save' +
        "</button>" +
        '<button class="btn-reply-cancel" onclick="cancelInlineEdit(this)">Cancel</button>' +
        "</div>" +
        "</div>";

      // Wire up double-click editing on this dynamically created bubble
      msgDiv.addEventListener("dblclick", function () {
        const ef = msgDiv.querySelector(".msg-edit-form");
        const mt = msgDiv.querySelector(".msg-text");
        if (!ef || !mt) return;
        mt.style.display = "none";
        ef.style.display = "block";
        const ta = ef.querySelector(".msg-edit-textarea");
        if (ta) {
          ta.focus();
          ta.select();
        }
      });

      historyContainer.appendChild(msgDiv);

      // Update the action buttons: rename "Write Reply" to "New Message"
      const actions = document.getElementById("reply-actions-" + feedbackId);
      if (actions) {
        // Remove any Edit Reply button
        actions.querySelectorAll(".btn-reply-edit").forEach((b) => b.remove());

        let writeBtn = actions.querySelector(".btn-reply-open");
        if (writeBtn) {
          writeBtn.innerHTML =
            '<i class="fa-solid fa-paper-plane"></i> New Message';
          writeBtn.setAttribute(
            "onclick",
            "openReplyBox(" + feedbackId + ", '')",
          );
        }
        actions.style.display = "";
      }

      closeReplyBox(feedbackId);
      showToast("Reply sent!", "success");
    } else {
      showToast(result.message || "Could not send reply.", "error");
    }
  } catch (err) {
    showToast("Network error. Please try again.", "error");
  }
}

// ── INLINE MESSAGE EDIT HELPERS ──────────────────────────────────────────────
// Used for message bubbles created dynamically (before page reload gives them a real message_id)

function saveInlineEdit(btn, feedbackId) {
  const editForm = btn.closest(".msg-edit-form");
  const bubble = btn.closest(".admin-msg-bubble");
  const ta = editForm ? editForm.querySelector(".msg-edit-textarea") : null;
  if (!ta) return;

  const newText = ta.value.trim();
  if (!newText) {
    alert("Message cannot be empty.");
    return;
  }

  // Send updated text to server
  const formData = new FormData();
  formData.append("action", "reply_feedback");
  formData.append("feedback_id", feedbackId);
  formData.append("admin_reply", newText);

  fetch("admin.php", { method: "POST", body: formData })
    .then((r) => r.json())
    .then((res) => {
      if (res.success) {
        const mt = bubble.querySelector(".msg-text");
        if (mt) {
          mt.textContent = newText;
          mt.style.display = "";
        }
        editForm.style.display = "none";
        showToast("Message updated.", "success");
      } else {
        alert(res.message || "Could not update.");
      }
    });
}

function cancelInlineEdit(btn) {
  const editForm = btn.closest(".msg-edit-form");
  const bubble = btn.closest(".admin-msg-bubble");
  if (!editForm || !bubble) return;
  editForm.style.display = "none";
  const mt = bubble.querySelector(".msg-text");
  if (mt) mt.style.display = "";
}

// ── REDUCE FEEDBACK SIDEBAR BADGE ────────────────────────────────────────────
// Called when a feedback thread is resolved — decrements the badge count
function reduceFeedbackBadge() {
  const badge = document.querySelector(".admin-sidebar-badge");
  if (!badge) return;
  const current = parseInt(badge.textContent) || 0;
  if (current <= 1) {
    badge.remove(); // Remove badge entirely when count hits 0
  } else {
    badge.textContent = current - 1;
  }
}
