// /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */

/*
  main.js — SkillHub frontend logic.
  Handles navigation, scroll animations, feedback form validation,
  booking modal (instant seat counter + Already Booked swap),
  workshop details modal (instructor hover popup, What you'll learn,
  Good Fit For), profile modals, live search, and local time display.
*/

function initMobileNav() {
  const toggle = document.getElementById("menu-toggle");
  const nav = document.getElementById("main-nav");
  if (!toggle || !nav) return;
  toggle.addEventListener("click", function () {
    nav.classList.toggle("open");
    toggle.textContent = nav.classList.contains("open") ? "✕" : "☰";
  });
}

function setActiveNavLink() {
  const currentPage = window.location.pathname.split("/").pop() || "index.php";
  document.querySelectorAll("nav#main-nav ul li a").forEach(function (link) {
    const href = link.getAttribute("href");
    if (!href) return;
    if (
      href === currentPage ||
      (currentPage === "index.php" &&
        (href === "../index.php" || href === "index.php"))
    ) {
      link.classList.add("active");
    }
  });
}

function initScrollAnimations() {
  const targets = document.querySelectorAll(
    ".card, .info-card, .section-header, .page-hero, .table-wrapper",
  );
  if (!targets.length) return;
  const observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 },
  );
  targets.forEach(function (el) {
    el.classList.add("fade-in");
    observer.observe(el);
  });
}

function initFormValidation() {
  const form = document.getElementById("feedback-form");
  if (!form) return;
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    let isValid = true;
    const errors = [];
    const nameInput = document.getElementById("name");
    const nameError = document.getElementById("name-error");
    if (!nameInput.value.trim()) {
      nameInput.classList.add("error");
      nameError.classList.add("visible");
      errors.push("Name is required.");
      isValid = false;
    } else {
      nameInput.classList.remove("error");
      nameError.classList.remove("visible");
    }
    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("email-error");
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailInput.value.trim()) {
      emailInput.classList.add("error");
      emailError.classList.add("visible");
      emailError.textContent = "Please enter your email address.";
      errors.push("Email is required.");
      isValid = false;
    } else if (!emailRegex.test(emailInput.value.trim())) {
      emailInput.classList.add("error");
      emailError.classList.add("visible");
      emailError.textContent = "Please enter a valid email address.";
      errors.push("Email format is invalid.");
      isValid = false;
    } else {
      emailInput.classList.remove("error");
      emailError.classList.remove("visible");
    }
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const ratingError = document.getElementById("rating-error");
    const ratingSelected = Array.from(ratingInputs).some(function (r) {
      return r.checked;
    });
    if (!ratingSelected) {
      if (ratingError) ratingError.classList.add("visible");
      errors.push("Please select a rating.");
      isValid = false;
    } else {
      if (ratingError) ratingError.classList.remove("visible");
    }
    if (!isValid) {
      alert(
        "Please fix the following errors before submitting:\n\n• " +
          errors.join("\n• "),
      );
      return;
    }
    try {
      fetch("../server/process_feedback.php", {
        method: "POST",
        body: new FormData(form),
      });
    } catch (e) {}
    form.style.display = "none";
    const successMsg = document.getElementById("success-message");
    if (successMsg) {
      successMsg.style.display = "block";
      successMsg.scrollIntoView({ behavior: "smooth" });
    }
  });
  ["name", "email"].forEach(function (id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener("input", function () {
      el.classList.remove("error");
      const errEl = document.getElementById(id + "-error");
      if (errEl) errEl.classList.remove("visible");
    });
  });
}

function initRippleEffect() {
  document.querySelectorAll(".btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      const ripple = document.createElement("span");
      ripple.classList.add("btn-ripple");
      ripple.style.left = e.offsetX - 5 + "px";
      ripple.style.top = e.offsetY - 5 + "px";
      btn.appendChild(ripple);
      setTimeout(function () {
        ripple.remove();
      }, 500);
    });
  });
}

// ===== BOOKING MODAL =====
var currentWorkshop = {};

function setBookingState(state) {
  [
    "booking-state-confirm",
    "booking-state-loading",
    "booking-state-success",
    "booking-state-error",
  ].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.hidden = true;
  });
  var active = document.getElementById("booking-state-" + state);
  if (active) active.hidden = false;
}

function openBookingModal(id, name, date, time, link) {
  if (document.body.dataset.loggedIn === "0") {
    window.location.href = "login.php?reason=booking";
    return;
  }
  currentWorkshop = { id: id, name: name, date: date, time: time, link: link };
  document.getElementById("info-name").textContent = name;
  document.getElementById("info-date").textContent = "Date: " + date;
  document.getElementById("info-time").textContent = "Time: " + time;
  setBookingState("confirm");
  var overlay = document.getElementById("booking-overlay");
  overlay.hidden = false;
  document.body.style.overflow = "hidden";
}

function closeBookingModal() {
  var overlay = document.getElementById("booking-overlay");
  if (!overlay) return;
  overlay.hidden = true;
  document.body.style.overflow = "";
}

// ===== WORKSHOP DETAILS MODAL =====
/*
  ASEEL ADDITION:
  Populates the details modal with:
  - Title, description, category, seats badge
  - Instructor hover popup (name, specialty, experience, email)
  - Date, Time cards
  - "What you'll learn" (green) from learning_points — admin-written bullets
  - "Good fit for" (purple) from good_fit_for — admin-written bullets
  Both sections are hidden if the field is empty.
*/
function initWorkshopDetailsModal() {
  document.addEventListener("click", function (event) {
    var btn = event.target.closest(".view-details-btn");
    if (btn) {
      var title = btn.dataset.title || "";
      var description = btn.dataset.description || "";
      var instructor = btn.dataset.instructor || "Not assigned";
      var instructorEmail = btn.dataset.instructorEmail || "";
      var instructorSpecialty = btn.dataset.instructorSpecialty || "";
      var instructorExp = btn.dataset.instructorExperience || "";
      var learningPoints = btn.dataset.learningPoints || "";
      var goodFitFor = btn.dataset.goodFitFor || "";
      var date = btn.dataset.date || "";
      var time = btn.dataset.time || "";
      var seats = btn.dataset.seats || "0";
      var category = btn.dataset.category || "Workshop";

      document.getElementById("details-title").textContent = title;
      document.getElementById("details-description").textContent = description;
      document.getElementById("details-category").textContent = category;
      document.getElementById("details-seats-badge").textContent =
        seats + " seats available";
      document.getElementById("details-date").textContent = date;
      document.getElementById("details-time").textContent = time;

      // Instructor popup
      var nameEl = document.getElementById("details-instructor-name");
      if (nameEl) nameEl.textContent = instructor;
      var popupNameEl = document.getElementById(
        "details-instructor-popup-name",
      );
      if (popupNameEl) popupNameEl.textContent = instructor;

      var specEl = document.getElementById("details-instructor-specialty");
      if (specEl) {
        if (instructorSpecialty) {
          specEl.textContent = "Specialty: " + instructorSpecialty;
          specEl.style.display = "block";
        } else {
          specEl.textContent = "";
          specEl.style.display = "none";
        }
      }

      var expEl = document.getElementById("details-instructor-experience");
      if (expEl) {
        if (instructorExp) {
          expEl.textContent = "Experience: " + instructorExp;
          expEl.style.display = "block";
        } else {
          expEl.textContent = "";
          expEl.style.display = "none";
        }
      }

      var emailEl = document.getElementById("details-instructor-email");
      if (emailEl) {
        if (instructorEmail) {
          emailEl.textContent = instructorEmail;
          emailEl.style.display = "block";
        } else {
          emailEl.textContent = "";
          emailEl.style.display = "none";
        }
      }

      // ── "What you'll learn" (green) ──
      // Admin-written bullet points from learning_points field. NOT the description.
      var learnSection = document.getElementById("details-learn-section");
      var learnEl = document.getElementById("details-learn");
      if (learnEl && learnSection) {
        if (learningPoints.trim()) {
          var points = learningPoints
            .split("\n")
            .map(function (p) {
              return p.trim();
            })
            .filter(function (p) {
              return p.length > 0;
            });
          if (points.length > 0) {
            learnEl.innerHTML = points
              .map(function (p) {
                return (
                  '<li style="display:flex;align-items:flex-start;gap:9px;font-size:0.9rem;color:#065f46;line-height:1.6;">' +
                  '<i class="fa-solid fa-circle-check" style="color:#10b981;margin-top:3px;font-size:0.7rem;flex-shrink:0;"></i>' +
                  p +
                  "</li>"
                );
              })
              .join("");
            learnSection.style.display = "";
          } else {
            learnSection.style.display = "none";
          }
        } else {
          learnSection.style.display = "none";
        }
      }

      // ── "Good Fit For" (purple) ──
      // Admin-written bullet points from good_fit_for field.
      // Uses purple color scheme to distinguish from the green learn section.
      var fitSection = document.getElementById("details-fit-section");
      var fitEl = document.getElementById("details-fit");
      if (fitEl && fitSection) {
        if (goodFitFor.trim()) {
          var fitPoints = goodFitFor
            .split("\n")
            .map(function (p) {
              return p.trim();
            })
            .filter(function (p) {
              return p.length > 0;
            });
          if (fitPoints.length > 0) {
            fitEl.innerHTML = fitPoints
              .map(function (p) {
                return (
                  '<li style="display:flex;align-items:flex-start;gap:9px;font-size:0.9rem;color:#5b21b6;line-height:1.6;">' +
                  '<i class="fa-solid fa-user-check" style="color:#8b5cf6;margin-top:3px;font-size:0.7rem;flex-shrink:0;"></i>' +
                  p +
                  "</li>"
                );
              })
              .join("");
            fitSection.style.display = "";
          } else {
            fitSection.style.display = "none";
          }
        } else {
          fitSection.style.display = "none";
        }
      }

      document.getElementById("details-overlay").hidden = false;
      document.body.style.overflow = "hidden";
      return;
    }

    if (
      event.target.classList.contains("details-close-btn") ||
      event.target.id === "details-overlay"
    ) {
      document.getElementById("details-overlay").hidden = true;
      document.body.style.overflow = "";
    }
  });
}

// ===== SUBMIT BOOKING =====
/*
  On success:
  1. Instantly swaps Book button to Already Booked
  2. Instantly reduces the seats counter by 1
  3. Updates global bookedWorkshopIds array
*/
async function submitWorkshopBooking() {
  var confirmButton = document.getElementById("booking-confirm-btn");
  var errorMessage = document.getElementById("booking-error-message");
  if (!currentWorkshop.id) return;
  setBookingState("loading");
  if (confirmButton) confirmButton.disabled = true;

  var fullName = document.body.dataset.userName || "SkillHub User";
  var email = document.body.dataset.userEmail || "";
  var nameParts = fullName.trim().split(" ");
  var firstName = nameParts.shift() || "SkillHub";
  var lastName = nameParts.join(" ");

  var formData = new FormData();
  formData.append("workshop_id", currentWorkshop.id);
  formData.append("first_name", firstName);
  formData.append("last_name", lastName);
  formData.append("email", email);

  try {
    var response = await fetch("../api/create_booking.php", {
      method: "POST",
      body: formData,
    });
    var result = await response.json();

    if (result.success) {
      var successMessage = document.getElementById("booking-success-message");
      if (successMessage) {
        successMessage.textContent = result.email_sent
          ? "Your seat has been reserved successfully. A confirmation email was sent with your booking details."
          : "Your seat has been reserved successfully, but the confirmation email could not be sent.";
      }
      var bookedId = currentWorkshop.id;
      if (bookedId) {
        // Swap button instantly
        var bookBtn = document.querySelector(
          '.workshop-book-btn[data-workshop-id="' + bookedId + '"]',
        );
        if (bookBtn) {
          var alreadyBtn = document.createElement("button");
          alreadyBtn.type = "button";
          alreadyBtn.className = "btn btn-booked-already";
          alreadyBtn.disabled = true;
          alreadyBtn.innerHTML =
            '<i class="fa-solid fa-circle-check"></i> Already Booked';
          bookBtn.replaceWith(alreadyBtn);
        }
        // Reduce seats counter instantly
        var seatsTag = document.getElementById("seats-tag-" + bookedId);
        if (seatsTag) {
          var currentSeats = parseInt(seatsTag.textContent) || 0;
          seatsTag.textContent = Math.max(0, currentSeats - 1) + " Seats";
        }
        if (window.bookedWorkshopIds)
          window.bookedWorkshopIds.push(parseInt(bookedId));
      }
      setBookingState("success");
      return;
    }
    if (result.already_booked) {
      var successMessage = document.getElementById("booking-success-message");
      if (successMessage)
        successMessage.textContent =
          "You have already booked this workshop. Visit your profile to view your bookings.";
      setBookingState("success");
      return;
    }
    if (errorMessage)
      errorMessage.textContent =
        result.message || "Booking failed. Please try again.";
    setBookingState("error");
  } catch (error) {
    if (errorMessage)
      errorMessage.textContent =
        "Something went wrong while submitting your booking.";
    setBookingState("error");
  } finally {
    if (confirmButton) confirmButton.disabled = false;
  }
}

function initBookingConfirmation() {
  var overlay = document.getElementById("booking-overlay");
  if (!overlay) return;
  var closeButton = document.getElementById("modal-close");
  var cancelButton = document.getElementById("booking-cancel-btn");
  var confirmButton = document.getElementById("booking-confirm-btn");
  var backButton = document.getElementById("booking-back-btn");
  var errorBackButton = document.getElementById("booking-error-back-btn");
  if (closeButton) closeButton.addEventListener("click", closeBookingModal);
  if (cancelButton) cancelButton.addEventListener("click", closeBookingModal);
  if (backButton) backButton.addEventListener("click", closeBookingModal);
  if (errorBackButton)
    errorBackButton.addEventListener("click", closeBookingModal);
  if (confirmButton)
    confirmButton.addEventListener("click", submitWorkshopBooking);
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeBookingModal();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !overlay.hidden) closeBookingModal();
  });
}

// ===== LIVE WORKSHOP SEARCH =====
async function initWorkshopSearch() {
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const grid = document.querySelector(".grid-2");
  if (!searchInput || !categoryFilter || !grid) return;

  function formatTimeStr(t) {
    if (!t) return "";
    const [h, m] = t.split(":").map(Number);
    const ampm = h >= 12 ? "PM" : "AM";
    const hour = h % 12 || 12;
    return hour + ":" + String(m).padStart(2, "0") + " " + ampm;
  }

  async function loadWorkshops() {
    const searchValue = searchInput.value;
    const categoryValue = categoryFilter.value;
    const response = await fetch(
      "../api/search_workshops.php?search=" +
        encodeURIComponent(searchValue) +
        "&category=" +
        encodeURIComponent(categoryValue),
    );
    const workshops = await response.json();
    grid.innerHTML = "";
    const bookedIds = window.bookedWorkshopIds || [];
    const isAdmin = document.body.dataset.isAdmin === "1";

    workshops.forEach(function (workshop) {
      const seats = parseInt(workshop.available_seats);
      const isFull = seats <= 0;
      const hasImg = workshop.image_path && workshop.image_path.trim() !== "";
      const imgHtml = hasImg
        ? `<img src="${workshop.image_path}" alt="${workshop.title}" class="card-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />`
        : "";
      const placeholderStyle = hasImg ? 'style="display:none"' : "";
      const placeholderHtml = `<div class="card-img-placeholder" ${placeholderStyle}><i class="fa-solid fa-book-open"></i><span>${workshop.category_name}</span></div>`;

      const instructorName = (workshop.instructor_name || "").trim() || "—";
      const instructorEmail = (workshop.instructor_email || "").trim();
      const instructorSpecialty = (workshop.instructor_specialty || "").trim();
      const instructorExp = (workshop.instructor_experience || "").trim();
      const learningPoints = (workshop.learning_points || "").trim();
      const goodFitFor = (workshop.good_fit_for || "").trim();
      const hookMessage = (workshop.hook_message || "").trim();

      let btnHtml = "";
      if (!isAdmin) {
        if (isFull) {
          btnHtml = `<button type="button" class="btn btn-secondary workshop-book-btn" disabled style="margin-top:8px;"><i class="fa-solid fa-circle-xmark"></i> Full</button>`;
        } else if (bookedIds.includes(parseInt(workshop.workshop_id))) {
          btnHtml = `<button type="button" class="btn btn-booked-already" disabled><i class="fa-solid fa-circle-check"></i> Already Booked</button>`;
        } else {
          btnHtml = `<button type="button" class="btn btn-primary book-btn workshop-book-btn"
            data-workshop-id="${workshop.workshop_id}"
            data-workshop-title="${workshop.title}"
            data-workshop-date="${workshop.workshop_date}"
            data-workshop-time="${workshop.start_time} - ${workshop.end_time}"
            data-workshop-link="#"><i class="fa-solid fa-calendar-days"></i> Book Workshop</button>`;
        }
      }

      const hookHtml = hookMessage
        ? `<p class="card-hook-message"><i class="fa-solid fa-bolt"></i> ${hookMessage}</p>`
        : "";

      const viewDetailsBtn = `<button type="button" class="btn view-details-btn"
        data-title="${workshop.title}"
        data-description="${workshop.description}"
        data-category="${workshop.category_name}"
        data-instructor="${instructorName}"
        data-instructor-email="${instructorEmail}"
        data-instructor-specialty="${instructorSpecialty}"
        data-instructor-experience="${instructorExp}"
        data-learning-points="${learningPoints}"
        data-good-fit-for="${goodFitFor}"
        data-date="${workshop.workshop_date}"
        data-time="${formatTimeStr(workshop.start_time)} – ${formatTimeStr(workshop.end_time)}"
        data-seats="${workshop.available_seats}"
        data-workshop-id="${workshop.workshop_id}">
        <i class="fa-solid fa-eye"></i> View Details
      </button>`;

      grid.innerHTML += `
        <div class="card">
          ${imgHtml}${placeholderHtml}
          <div class="card-body">
            <div class="card-icon card-icon-web"><i class="fa-solid fa-laptop-code"></i></div>
            <h3>${workshop.title}</h3>
            <p>${workshop.description}</p>
            ${hookHtml}
            <div class="card-tags" style="margin-top:16px">
              <span class="tag tag-primary">${workshop.category_name}</span>
              <span class="tag tag-secondary seats-tag" id="seats-tag-${workshop.workshop_id}">${workshop.available_seats} Seats</span>
            </div>
            ${viewDetailsBtn}
            ${btnHtml}
          </div>
        </div>`;
    });
  }

  searchInput.addEventListener("keyup", loadWorkshops);
  categoryFilter.addEventListener("change", loadWorkshops);
}

function initBookingButtons() {
  var isAdmin = document.body.dataset.isAdmin === "1";
  if (isAdmin) {
    document
      .querySelectorAll(".book-btn, .workshop-book-btn")
      .forEach(function (btn) {
        btn.style.display = "none";
      });
  }
  document.addEventListener("click", function (e) {
    var button = e.target.closest(".book-btn");
    if (!button) return;
    if (isAdmin) return;
    var isLoggedIn = document.body.dataset.loggedIn === "1";
    if (!isLoggedIn) {
      window.location.href = "login.php?reason=booking";
      return;
    }
    openBookingModal(
      button.dataset.workshopId,
      button.dataset.workshopTitle,
      button.dataset.workshopDate,
      button.dataset.workshopTime,
      button.dataset.workshopLink,
    );
  });
}

function initProfilePictureUpload() {
  var openButton = document.getElementById("profile-avatar-open");
  var overlay = document.getElementById("profile-upload-overlay");
  var closeButton = document.getElementById("profile-upload-close");
  var fileInput = document.getElementById("profile_image");
  var uploadBox = document.getElementById("profile-upload-box");
  var uploadLabel = document.getElementById("profile-upload-label");
  var selectedName = document.getElementById("profile-upload-selected-name");
  var submitButton = document.getElementById("profile-upload-submit");
  if (!openButton || !overlay) return;
  function openProfileUploadModal() {
    overlay.hidden = false;
    document.body.style.overflow = "hidden";
  }
  function closeProfileUploadModal() {
    overlay.hidden = true;
    document.body.style.overflow = "";
  }
  openButton.addEventListener("click", openProfileUploadModal);
  if (closeButton)
    closeButton.addEventListener("click", closeProfileUploadModal);
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeProfileUploadModal();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !overlay.hidden) closeProfileUploadModal();
  });
  if (fileInput && uploadBox && uploadLabel && selectedName && submitButton) {
    fileInput.addEventListener("change", function () {
      var hasFile = fileInput.files.length > 0;
      if (hasFile) {
        uploadBox.classList.add("has-file");
        uploadLabel.textContent = "Picture selected";
        selectedName.textContent = fileInput.files[0].name;
        submitButton.disabled = false;
      } else {
        uploadBox.classList.remove("has-file");
        uploadLabel.textContent = "Choose picture";
        selectedName.textContent = "JPG, JPEG, or PNG";
        submitButton.disabled = true;
      }
    });
  }
}

function initProfilePasswordModal() {
  var openButton = document.getElementById("profile-password-open");
  var overlay = document.getElementById("profile-password-overlay");
  var closeButton = document.getElementById("profile-password-close");
  if (!openButton || !overlay) return;
  function openPasswordModal() {
    overlay.hidden = false;
    document.body.style.overflow = "hidden";
  }
  function closePasswordModal() {
    overlay.hidden = true;
    document.body.style.overflow = "";
  }
  openButton.addEventListener("click", openPasswordModal);
  if (closeButton) closeButton.addEventListener("click", closePasswordModal);
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closePasswordModal();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !overlay.hidden) closePasswordModal();
  });
}

function initProfileNameEdit() {
  var form = document.getElementById("profile-name-form");
  var input = document.getElementById("full_name");
  var button = document.getElementById("profile-name-edit");
  if (!form || !input || !button) return;
  var originalValue = input.value.trim();
  var isEditing = false;
  function setEditMode() {
    isEditing = true;
    input.removeAttribute("readonly");
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);
    button.classList.add("is-editing");
    button.setAttribute("aria-label", button.dataset.saveLabel || "Save name");
    button.innerHTML = '<i class="fa-solid fa-check"></i>';
  }
  function setViewMode() {
    isEditing = false;
    input.setAttribute("readonly", "readonly");
    input.value = originalValue;
    button.classList.remove("is-editing");
    button.setAttribute("aria-label", button.dataset.editLabel || "Edit name");
    button.innerHTML = '<i class="fa-solid fa-pen"></i>';
  }
  button.addEventListener("click", function (e) {
    if (!isEditing) {
      e.preventDefault();
      setEditMode();
      return;
    }
    form.requestSubmit();
  });
  input.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && isEditing) {
      e.preventDefault();
      form.requestSubmit();
    }
    if (e.key === "Escape" && isEditing) {
      e.preventDefault();
      setViewMode();
    }
  });
  form.addEventListener("submit", function (e) {
    var currentValue = input.value.trim();
    if (!isEditing) {
      e.preventDefault();
      return;
    }
    if (currentValue === "") {
      e.preventDefault();
      input.focus();
      return;
    }
    if (currentValue === originalValue) {
      e.preventDefault();
      setViewMode();
    }
  });
}

function initLocalTimeDisplay() {
  var timeElements = document.querySelectorAll(".js-local-time");
  if (!timeElements.length) return;
  timeElements.forEach(function (element) {
    var utcTime = element.dataset.utcTime;
    if (!utcTime) return;
    var date = new Date(utcTime);
    if (Number.isNaN(date.getTime())) return;
    element.textContent = date.toLocaleString(undefined, {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  initWorkshopDetailsModal();
  initMobileNav();
  setActiveNavLink();
  initScrollAnimations();
  initFormValidation();
  initRippleEffect();
  initProfilePictureUpload();
  initProfilePasswordModal();
  initProfileNameEdit();
  initWorkshopSearch();
  initBookingButtons();
  initBookingConfirmation();
  initLocalTimeDisplay();
});
