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

// Close when clicking the dark background
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
// ONE listener handles both to prevent the double-modal bug
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

    // Set category dropdown
    const catSelect = $("edit-category");
    catSelect.value = d.category;
    if (catSelect.value !== d.category) {
      setTimeout(() => {
        catSelect.value = d.category;
      }, 10);
    }

    // Set instructor dropdown
    const instSelect = $("edit-instructor");
    if (instSelect) {
      instSelect.value = d.instructor || "";
    }

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
  e.preventDefault(); // Stop page reload — this is the AJAX requirement

  const form = e.target;
  const errorBox = $("add-form-error");
  const submitBtn = form.querySelector("[type=submit]");

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';

  try {
    const formData = new FormData(form);
    formData.append("action", "create");

    // Send to PHP API — returns JSON
    const response = await fetch("../../api/workshops.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      // ✅ Add styled row immediately without page reload
      appendWorkshopRow(result.workshop);
      renumberRows(); // Fix row numbering
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

    const response = await fetch("../../api/workshops.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      // ✅ Update existing row in place — no page reload
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
      // ✅ Remove row and renumber immediately
      removeWorkshopRow(workshopIdToDelete);
      renumberRows(); // Renumber after deletion
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

function appendWorkshopRow(w) {
  const tbody = $("workshops-tbody");
  const tr = document.createElement("tr");
  tr.dataset.id = w.workshop_id;
  tr.innerHTML = buildRowHTML(w);
  tbody.appendChild(tr);
}

function updateWorkshopRow(w) {
  const row = $("workshops-tbody").querySelector(
    `tr[data-id="${w.workshop_id}"]`,
  );
  if (row) row.innerHTML = buildRowHTML(w);
}

function removeWorkshopRow(id) {
  const row = $("workshops-tbody").querySelector(`tr[data-id="${id}"]`);
  if (row) row.remove();
}

// ── RENUMBER ROWS ────────────────────────────────────────────────────────────
// After add or delete, update the # column to show 1, 2, 3... in order.
// This uses the ACTUAL workshop_id from data-id, not sequential numbers,
// but keeps the ID column showing the real database ID in sorted display order.
function renumberRows() {
  // We just re-render the first cell of each row with its real ID
  // The rows are already in date order from PHP — JS just appends new ones at the end
  // The ID shown is the real DB id, which is fine and traceable
  // Nothing to do here for real IDs — they stay correct
  // We only re-sort visually if needed
}

// ── BUILD ROW HTML ───────────────────────────────────────────────────────────
// This is called for BOTH new rows (add) and updated rows (edit).
// It formats dates and times exactly like PHP does on page load.
function buildRowHTML(w) {
  // Escape HTML to prevent XSS
  const safe = (str) =>
    String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  // Format date and time to match PHP's date('M j, Y') and date('g:i A')
  const formattedDate = formatDate(w.workshop_date);
  const formattedStart = formatTime(w.start_time);
  const formattedEnd = formatTime(w.end_time);

  return `
    <td class="admin-id-cell">${safe(w.workshop_id)}</td>
    <td class="admin-title-cell">${safe(w.title)}</td>
    <td><span class="admin-category-tag">${safe(w.category_name)}</span></td>
    <td>${formattedDate}</td>
    <td class="admin-time-cell">${formattedStart} – ${formattedEnd}</td>
    <td><span class="admin-seats-badge">${safe(w.available_seats)}</span></td>
    <td class="admin-instructor-cell">
      ${
        w.instructor_name && w.instructor_name.trim()
          ? safe(w.instructor_name)
          : '<span class="admin-unassigned">Unassigned</span>'
      }
    </td>
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

// ── FEEDBACK REPLY (AJAX — no page reload, no jumping) ────────────────────
/*
  openReplyBox(id, existingText) — shows the reply textarea for a feedback item
  closeReplyBox(id)              — hides it without saving
  submitReply(id)                — sends the reply to the server via fetch(), 
                                   updates the UI, reduces the sidebar badge
*/
function openReplyBox(feedbackId, existingText) {
  const box = document.getElementById("reply-box-" + feedbackId);
  const textarea = document.getElementById("reply-text-" + feedbackId);
  if (!box || !textarea) return;

  textarea.value = existingText || "";
  box.style.setProperty("display", "block", "important");
  box.scrollIntoView({ block: "nearest" });
  textarea.focus();

  const actions = document.getElementById("reply-actions-" + feedbackId);
  if (actions) actions.style.display = "none";
}

// Inline edit helpers for dynamically added bubbles (no DB message_id)
function saveInlineEdit(btn, feedbackId) {
  var editForm = btn.closest(".msg-edit-form");
  var bubble = btn.closest(".admin-msg-bubble");
  var ta = editForm ? editForm.querySelector(".msg-edit-textarea") : null;
  if (!ta) return;
  var newText = ta.value.trim();
  if (!newText) {
    alert("Message cannot be empty.");
    return;
  }
  var formData = new FormData();
  formData.append("action", "reply_feedback");
  formData.append("feedback_id", feedbackId);
  formData.append("admin_reply", newText);
  fetch("admin.php", { method: "POST", body: formData })
    .then(function (r) {
      return r.json();
    })
    .then(function (res) {
      if (res.success) {
        var mt = bubble.querySelector(".msg-text");
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
  var editForm = btn.closest(".msg-edit-form");
  var bubble = btn.closest(".admin-msg-bubble");
  if (!editForm || !bubble) return;
  editForm.style.display = "none";
  var mt = bubble.querySelector(".msg-text");
  if (mt) mt.style.display = "";
}

function closeReplyBox(feedbackId) {
  const box = document.getElementById("reply-box-" + feedbackId);
  const actions = document.getElementById("reply-actions-" + feedbackId);
  if (box) box.style.display = "none"; // NOT box.hidden = true
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

  // Send to server
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
      // Update the displayed reply box without page reload
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

      // Remove legacy single-reply display if present
      const oldSingle = card.querySelector(
        ".feedback-reply-display:not(.feedback-message-history .feedback-reply-display)",
      );
      if (oldSingle) oldSingle.remove();

      // Build timestamp
      const now = new Date();
      const timeStr = now.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      });

      // Append new message as a double-click-editable bubble
      // We don't have the message_id yet (need page reload for that)
      // so double-click edit will work after next page load
      const safeText = replyText
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
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
        ')"><i class="fa-solid fa-check"></i> Save</button>' +
        '<button class="btn-reply-cancel" onclick="cancelInlineEdit(this)">Cancel</button>' +
        "</div>" +
        "</div>";
      // Wire up double-click on this dynamically created bubble
      msgDiv.addEventListener("dblclick", function () {
        var ef = msgDiv.querySelector(".msg-edit-form");
        var mt = msgDiv.querySelector(".msg-text");
        if (!ef || !mt) return;
        mt.style.display = "none";
        ef.style.display = "block";
        var ta = ef.querySelector(".msg-edit-textarea");
        if (ta) {
          ta.focus();
          ta.select();
        }
      });
      historyContainer.appendChild(msgDiv);

      // Update the actions row after sending a reply
      // Rules: NO Edit Reply button ever. Show: New Message + Resolve only.
      const actions = document.getElementById("reply-actions-" + feedbackId);
      if (actions) {
        const resolveBtn = actions.querySelector(".btn-resolve");

        // Remove any Edit Reply button that might exist
        actions.querySelectorAll(".btn-reply-edit").forEach((b) => b.remove());

        // Change Write Reply button to New Message, or add it if missing
        let writeBtn = actions.querySelector(".btn-reply-open");
        if (writeBtn) {
          // Rename to New Message
          writeBtn.innerHTML =
            '<i class="fa-solid fa-paper-plane"></i> New Message';
          writeBtn.setAttribute(
            "onclick",
            "openReplyBox(" + feedbackId + ", '')",
          );
        } else if (!actions.querySelector(".btn-reply-new")) {
          // First reply — create New Message button
          const newMsgBtn = document.createElement("button");
          newMsgBtn.className = "btn-reply-open";
          newMsgBtn.innerHTML =
            '<i class="fa-solid fa-paper-plane"></i> New Message';
          newMsgBtn.setAttribute(
            "onclick",
            "openReplyBox(" + feedbackId + ", '')",
          );
          if (resolveBtn) {
            actions.insertBefore(newMsgBtn, resolveBtn);
          } else {
            actions.appendChild(newMsgBtn);
          }
        }

        actions.style.display = ""; // Make sure row is visible
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

function reduceFeedbackBadge() {
  const badge = document.querySelector(".admin-sidebar-badge");
  if (!badge) return;
  const current = parseInt(badge.textContent) || 0;
  if (current <= 1) {
    badge.remove(); // Remove badge entirely when count reaches 0
  } else {
    badge.textContent = current - 1;
  }
}
