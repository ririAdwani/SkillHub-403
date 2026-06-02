// /* Name=Aseel Musaid Alamri, ID=2108290, Section=DAR, Date=20/3 */
/* Name=Shahenaz Abushanab , ID=2215050, Section=DAR, Date=20/3 */
/* Name=Raghad Abdullah Alzahrani , ID=2206740, Section=DAR, Date=20/3 */

/*
  This JavaScript file controls the main interactive features of the website.
  It handles navigation behavior through initMobileNav() and setActiveNavLink(),
  adds visual effects using initScrollAnimations() and initRippleEffect(),
  validates the feedback form with initFormValidation(), and manages the
  workshop booking process through openBookingModal(), closeBookingModal(),
  clearBookingErrors(), and initBookingForm(). Finally, all main features are
  initialized when the page finishes loading inside the DOMContentLoaded event.
*/

// ===== MOBILE NAVIGATION TOGGLE =====
/*
  This function handles the navigation menu behavior.
  It gets the menu button and navigation container, then checks that both
  elements exist before continuing. When the user clicks the menu button,
  it opens or closes the navigation menu and changes the button symbol
  to match the current state.
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

// ===== ACTIVE NAV LINK =====
/*
  This function highlights the navigation link of the current page.
  It reads the current file name from the page URL, then selects all
  navigation links inside the main menu and adds the active class to
  the matching link.
*/
function setActiveNavLink() {
  const currentPage = window.location.pathname.split("/").pop() || "index.php";
  const navLinks = document.querySelectorAll("nav#main-nav ul li a");

  navLinks.forEach(function (link) {
    const href = link.getAttribute("href");
    if (!href) return;

    if (
      href === currentPage ||
      (currentPage === "index.php" && href === "../index.php") ||
      (currentPage === "index.php" && href === "index.php")
    ) {
      link.classList.add("active");
    }
  });
}

// ===== SCROLL ANIMATIONS =====
/*
  This function adds a fade-in scroll animation to selected page elements.
  It uses IntersectionObserver to detect when each element appears on screen
  and adds the visible class so the animation plays only once.
*/
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

// ===== FEEDBACK FORM VALIDATION =====
/*
  This function handles validation for the feedback form before submission.
  It checks that the name, email, and rating fields are filled correctly,
  shows error messages for invalid inputs, and prevents submission until
  the required data is valid.
*/
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

    // Save feedback to database via AJAX — silent fail
    try {
      var feedbackData = new FormData(form);
      fetch("../server/process_feedback.php", {
        method: "POST",
        body: feedbackData,
      });
    } catch (e) {
      /* silent */
    }

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

// ===== BUTTON RIPPLE EFFECT =====
/*
  This function adds a ripple click effect to website buttons.
*/
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

// Stores the currently selected workshop data for use during booking
var currentWorkshop = {};

/*
  Switches the booking modal between confirm / loading / success / error states.
*/
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

/*
  Opens the booking modal and fills it with the selected workshop details.
*/
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

/*
  Closes the booking modal and restores page scrolling.
*/
function closeBookingModal() {
  var overlay = document.getElementById("booking-overlay");
  if (!overlay) return;
  overlay.hidden = true;
  document.body.style.overflow = "";
}

// ===== WORKSHOP DETAILS MODAL =====
/*
  ASEEL ADDITION:
  Opens the workshop details modal, fills in all fields,
  and generates a "What you'll learn" section from the description.
  Works for both PHP-rendered cards and AJAX search result cards.

  FIX: removed references to details-location, details-price, details-seats
  (those elements are commented out in the HTML).
  FIX: "What you'll learn" now always shows — if description can be split
  into sentences it shows bullets, otherwise shows the full description.
*/
function initWorkshopDetailsModal() {
  document.addEventListener("click", function (event) {
    // ── Open details modal ──
    var detailsButton = event.target.closest(".view-details-btn");
    if (detailsButton) {
      var title = detailsButton.dataset.title || "";
      var description = detailsButton.dataset.description || "";
      var instructor = detailsButton.dataset.instructor || "Not assigned";
      var date = detailsButton.dataset.date || "";
      var time = detailsButton.dataset.time || "";
      var seats = detailsButton.dataset.seats || "0";
      var category = detailsButton.dataset.category || "Workshop";

      document.getElementById("details-title").textContent = title;
      document.getElementById("details-description").textContent = description;
      document.getElementById("details-instructor").textContent = instructor;
      document.getElementById("details-date").textContent = date;
      document.getElementById("details-time").textContent = time;
      document.getElementById("details-category").textContent = category;
      // Seats badge at top of modal — just the number
      document.getElementById("details-seats-badge").textContent =
        seats + " seats available";

      // ── "What you'll learn" section ──
      // Split description into sentences and show as bullet points.
      // If description is a single sentence, show it as one bullet.
      // Never hide the section when there is a description.
      var learnSection = document.getElementById("details-learn-section");
      var learnEl = document.getElementById("details-learn");

      if (learnEl && learnSection && description.trim()) {
        // Split by sentence-ending punctuation followed by whitespace
        var sentences = description
          .split(/(?<=[.!?])\s+/)
          .map(function (s) {
            return s.trim();
          })
          .filter(function (s) {
            return s.length > 10;
          })
          .slice(0, 4);

        // Use sentences as bullets if we got 2+, otherwise use full description
        var items = sentences.length >= 2 ? sentences : [description.trim()];

        learnEl.innerHTML = items
          .map(function (s) {
            return (
              '<li style="display:flex;align-items:flex-start;gap:9px;' +
              'font-size:0.9rem;color:#065f46;line-height:1.6;">' +
              '<i class="fa-solid fa-circle-check" style="color:#10b981;' +
              'margin-top:3px;font-size:0.7rem;flex-shrink:0;"></i>' +
              s +
              "</li>"
            );
          })
          .join("");

        learnSection.style.display = "";
      } else if (learnSection) {
        learnSection.style.display = "none";
      }

      document.getElementById("details-overlay").hidden = false;
      document.body.style.overflow = "hidden";
      return;
    }

    // ── Close details modal ──
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
  Sends the booking request to the PHP API, then updates the modal state.
  On success: instantly replaces the Book Workshop button with Already Booked
  so the user sees the change without refreshing the page.
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
      // Update success message text based on whether email was sent
      var successMessage = document.getElementById("booking-success-message");
      if (successMessage) {
        successMessage.textContent = result.email_sent
          ? "Your seat has been reserved successfully. A confirmation email was sent with your booking details."
          : "Your seat has been reserved successfully, but the confirmation email could not be sent. You can still view the booking from your profile.";
      }

      // ── INSTANT Already Booked swap ──
      // Replace the Book Workshop button immediately without page reload.
      // Targets the specific button by its workshop ID data attribute.
      var bookedId = currentWorkshop.id;
      if (bookedId) {
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

        // Keep global booked IDs array in sync so search results also show correct state
        if (window.bookedWorkshopIds) {
          window.bookedWorkshopIds.push(parseInt(bookedId));
        }
      }

      setBookingState("success");
      return;
    }

    // Already booked — show success state with friendly message instead of error
    if (result.already_booked) {
      var successMessage = document.getElementById("booking-success-message");
      if (successMessage) {
        successMessage.textContent =
          "You have already booked this workshop. Visit your profile to view your bookings.";
      }
      setBookingState("success");
      return;
    }

    // Other error
    if (errorMessage) {
      errorMessage.textContent =
        result.message || "Booking failed. Please try again.";
    }
    setBookingState("error");
  } catch (error) {
    if (errorMessage) {
      errorMessage.textContent =
        "Something went wrong while submitting your booking.";
    }
    setBookingState("error");
  } finally {
    if (confirmButton) confirmButton.disabled = false;
  }
}

// ===== BOOKING CONFIRMATION WIRING =====
/*
  Wires the booking modal buttons without inline JavaScript.
*/
function initBookingConfirmation() {
  var overlay = document.getElementById("booking-overlay");
  var closeButton = document.getElementById("modal-close");
  var cancelButton = document.getElementById("booking-cancel-btn");
  var confirmButton = document.getElementById("booking-confirm-btn");
  var backButton = document.getElementById("booking-back-btn");
  var errorBackButton = document.getElementById("booking-error-back-btn");

  if (!overlay) return;

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

// ===== LIVE WORKSHOP SEARCH (initWorkshopSearch) =====
/*
  This function handles live workshop search and category filtering.
  Sends the current search text and selected category to the PHP API,
  receives matching workshops as JSON, and updates the cards without reload.

  FIX: uses instructor_name from API (not instructor/location which don't exist).
  FIX: includes Already Booked state for search results using bookedWorkshopIds.
  FIX: includes image/placeholder logic matching the PHP card rendering.
*/
async function initWorkshopSearch() {
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const grid = document.querySelector(".grid-2");

  if (!searchInput || !categoryFilter || !grid) return;

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

      // Image or placeholder — same logic as PHP rendering
      const hasImg = workshop.image_path && workshop.image_path.trim() !== "";
      const imgHtml = hasImg
        ? `<img src="${workshop.image_path}" alt="${workshop.title}" class="card-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />`
        : "";
      const placeholderStyle = hasImg ? 'style="display:none"' : "";
      const placeholderHtml = `<div class="card-img-placeholder" ${placeholderStyle}>
        <i class="fa-solid fa-book-open"></i>
        <span>${workshop.category_name}</span>
      </div>`;

      // Instructor name from API — fallback to dash
      const instructorName = (workshop.instructor_name || "").trim() || "—";

      // Action button: Full / Already Booked / Book Workshop
      let btnHtml = "";
      if (!isAdmin) {
        if (isFull) {
          btnHtml = `<button type="button" class="btn btn-secondary workshop-book-btn" disabled style="margin-top:8px;">
            <i class="fa-solid fa-circle-xmark"></i> Full
          </button>`;
        } else if (bookedIds.includes(parseInt(workshop.workshop_id))) {
          btnHtml = `<button type="button" class="btn btn-booked-already" disabled>
            <i class="fa-solid fa-circle-check"></i> Already Booked
          </button>`;
        } else {
          btnHtml = `<button type="button"
            class="btn btn-primary book-btn workshop-book-btn"
            data-workshop-id="${workshop.workshop_id}"
            data-workshop-title="${workshop.title}"
            data-workshop-date="${workshop.workshop_date}"
            data-workshop-time="${workshop.start_time} - ${workshop.end_time}"
            data-workshop-link="#">
            <i class="fa-solid fa-calendar-days"></i> Book Workshop
          </button>`;
        }
      }

      // View Details button — outlined style
      const viewDetailsBtn = `<button
        type="button"
        class="btn view-details-btn"
        data-title="${workshop.title}"
        data-description="${workshop.description}"
        data-category="${workshop.category_name}"
        data-instructor="${instructorName}"
        data-date="${workshop.workshop_date}"
        data-time="${workshop.start_time} - ${workshop.end_time}"
        data-seats="${workshop.available_seats}">
        <i class="fa-solid fa-eye"></i> View Details
      </button>`;

      grid.innerHTML += `
        <div class="card">
          ${imgHtml}
          ${placeholderHtml}
          <div class="card-body">
            <div class="card-icon card-icon-web">
              <i class="fa-solid fa-laptop-code"></i>
            </div>
            <h3>${workshop.title}</h3>
            <p>${workshop.description}</p>
            <div class="card-tags" style="margin-top:16px">
              <span class="tag tag-primary">${workshop.category_name}</span>
              <span class="tag tag-secondary">${workshop.available_seats} Seats</span>
            </div>

            <button
              type="button"
              class="btn btn-secondary view-details-btn"
              data-title="${workshop.title}"
              data-description="${workshop.description}"
              data-instructor="${workshop.instructor || ""}"
              data-specialty="${workshop.instructor_specialty || ""}"
              data-experience="${workshop.instructor_experience || ""}"
              data-bio="${workshop.instructor_bio || ""}"
              data-date="${workshop.workshop_date}"
              data-time="${workshop.start_time} - ${workshop.end_time}"
              data-location="${workshop.location || "Online"}"
              data-price="${workshop.price || "0.00"}"
              data-seats="${workshop.available_seats}"
            >
              <i class="fa-solid fa-eye"></i>
              View Details
            </button>

            ${btnHtml}
          </div>
        </div>
      `;
    });
  }

  searchInput.addEventListener("keyup", loadWorkshops);
  categoryFilter.addEventListener("change", loadWorkshops);
}

// ===== BOOKING BUTTONS =====
/*
  Handles clicks on Book Workshop buttons through event delegation.
  Works for both initial PHP-rendered cards and AJAX search result cards.
  Admins cannot book workshops.
*/
function initBookingButtons() {
  var isAdmin = document.body.dataset.isAdmin === "1";

  // Hide all booking buttons for admin users immediately
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

// ===== PROFILE PICTURE UPLOAD MODAL =====
/*
  Controls the profile picture upload modal.
  The avatar button opens it; close button, overlay click, and Escape close it.
  Shows the selected filename before the user submits.
*/
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

// ===== PROFILE PASSWORD MODAL =====
/*
  Controls the change password modal on the profile page.
*/
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

// ===== PROFILE NAME EDIT MODE =====
/*
  Controls the profile name edit field.
  The name starts read-only. Clicking the pen enables editing;
  Enter or the check button submits; Escape cancels.
*/
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

// ===== LOCAL TIME DISPLAY =====
/*
  Converts UTC timestamps printed by PHP into the user's browser timezone.
*/
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
/*
  ASEEL ADDITION:
  Enables the View Details modal for both PHP-rendered cards
  and AJAX search/filter cards.
*/
function initWorkshopDetailsModal() {
  document.addEventListener("click", function (event) {
    var detailsButton = event.target.closest(".view-details-btn");

    if (detailsButton) {
      document.getElementById("details-title").textContent = detailsButton.dataset.title || "";
      document.getElementById("details-description").textContent = detailsButton.dataset.description || "";
  //     document.getElementById("details-instructor").textContent =
  // detailsButton.dataset.instructor || "Not assigned";
  document.getElementById("details-instructor-name").textContent =
  detailsButton.dataset.instructor || "Not assigned";

document.getElementById("details-instructor-popup-name").textContent =
  detailsButton.dataset.instructor || "Not assigned";

document.getElementById("details-instructor-specialty").textContent =
  "Specialty: " + (detailsButton.dataset.specialty || "Not specified");

document.getElementById("details-instructor-experience").textContent =
  "Experience: " + (detailsButton.dataset.experience || "Not specified");

document.getElementById("details-instructor-bio").textContent =
  detailsButton.dataset.bio || "No instructor bio available.";

document.getElementById("details-date").textContent =
  detailsButton.dataset.date || "";

document.getElementById("details-time").textContent =
  detailsButton.dataset.time || "";

// document.getElementById("details-location").textContent =
//   detailsButton.dataset.location || "Online";

// document.getElementById("details-price").textContent =
//   (detailsButton.dataset.price || "0.00") + " SAR";

// document.getElementById("details-seats").textContent =
//   detailsButton.dataset.seats || "0";

document.getElementById("details-category").textContent =
  "Workshop";

document.getElementById("details-seats-badge").textContent =
  (detailsButton.dataset.seats || "0") + " Seats";

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
/*
  This event runs after the HTML document finishes loading.
  It starts the main website features by calling the functions
  responsible for navigation behavior, active link highlighting,
  scroll animations, feedback form validation, button ripple effects,
  and booking form handling.
*/
document.addEventListener("DOMContentLoaded", function () {
  initWorkshopDetailsModal(); // enables the workshop View Details modal
  initMobileNav(); // initializes the navigation menu toggle behavior
  setActiveNavLink(); // highlights the link of the current page in the navigation menu
  initScrollAnimations(); // activates fade-in animations for selected page elements during scrolling
  initFormValidation(); // enables validation for the feedback form
  initRippleEffect(); // enables the ripple click effect on buttons
  initProfilePictureUpload(); // enables the profile picture upload modal behavior
  initProfilePasswordModal(); // enables the profile password change modal behavior
  initProfileNameEdit(); // enables edit mode for the profile name field
  initWorkshopSearch(); // enables live workshop search and filtering
  initBookingButtons(); // controls guest redirect and logged-in booking modal
  initBookingConfirmation(); // controls booking confirmation, loading, and success states
  initLocalTimeDisplay(); // formats UTC timestamps using the user's browser timezone
});
