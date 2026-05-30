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


/*
  This function handles the navigation menu behavior.
  It gets the menu button and navigation container, then checks that both
  elements exist before continuing. When the user clicks the menu button,
  it opens or closes the navigation menu and changes the button symbol
  to match the current state.
*/
// ===== MOBILE NAVIGATION TOGGLE =====
function initMobileNav() {
  const toggle = document.getElementById('menu-toggle'); // gets the mobile menu toggle button
  const nav = document.getElementById('main-nav'); // gets the main navigation menu
  if (!toggle || !nav) return;  // stops the function if one of the required elements is missing

  toggle.addEventListener('click', function () { // runs when the user clicks the menu button
    nav.classList.toggle('open');  // adds or removes the open class to show or hide the menu
    toggle.textContent = nav.classList.contains('open') ? '✕' : '☰'; // changes the icon depending on whether the menu is open or closed
  });
}


/*
  This function highlights the navigation link of the current page.
  It reads the current file name from the page URL, then selects all
  navigation links inside the main menu. After that, it compares each
  link path with the current page and adds the active class to the
  matching link.
*/
// ===== ACTIVE NAV LINK =====
function setActiveNavLink() {
  const currentPage = window.location.pathname.split('/').pop() || 'index.php'; // gets the current page file name from the URL
  const navLinks = document.querySelectorAll('nav#main-nav ul li a'); // selects all links inside the main navigation

  navLinks.forEach(function (link) { // loops through each navigation link
    const href = link.getAttribute('href'); // gets the href value of the current link
    if (!href) return; // skips this link if it does not have an href value

    if (
      href === currentPage || // checks if the link matches the current page directly
      (currentPage === 'index.php' && href === '../index.php') || // also handles the home link if written as ../index.php
      (currentPage === 'index.php' && href === 'index.php') // also handles the home link if written as index.php
    ) {
      link.classList.add('active'); // adds the active class to highlight the current page link
    }
  });
}


/*
  This function adds a scroll animation effect to selected page elements.
  It first creates the CSS rules needed for the fade-in effect, then adds
  them to the page. After that, it selects the target elements and uses
  IntersectionObserver to detect when each element appears on the screen.
  When an element becomes visible, the function adds the visible class so
  the animation plays only once.
*/
// ===== SCROLL ANIMATIONS =====
function initScrollAnimations() {
  const targets = document.querySelectorAll('.card, .info-card, .section-header, .page-hero, .table-wrapper'); // selects the elements that should receive the scroll animation
  if (!targets.length) return; // stops the function if no matching elements are found

  const observer = new IntersectionObserver(function (entries) { // creates an observer to watch when elements enter the viewport
    entries.forEach(function (entry) { // checks each observed element
      if (entry.isIntersecting) { // runs when the element becomes visible on screen
        entry.target.classList.add('visible'); // adds the visible class to trigger the animation
        observer.unobserve(entry.target); // stops observing this element so the animation only happens once
      }
    });
  }, { threshold: 0.12 }); // triggers when about 12% of the element is visible

  targets.forEach(function (el) { // loops through each target element
    el.classList.add('fade-in'); // adds the initial hidden animation class
    observer.observe(el); // starts observing the element
  });
}

/*
  This function handles validation for the feedback form before submission.
  It checks that the name, email, and rating fields are filled correctly,
  shows error messages for invalid inputs, and prevents submission until
  the required data is valid. If all inputs are correct, the form is hidden
  and a success message is displayed. It also removes error styling while
  the user edits the name and email fields.
*/
function initFormValidation() {
  const form = document.getElementById('feedback-form'); // gets the feedback form element
  if (!form) return; // stops the function if the form does not exist on the page

  form.addEventListener('submit', function (e) { // runs when the user tries to submit the form
    e.preventDefault(); // prevents the default form submission behavior

    let isValid = true; // keeps track of whether the form passes all validation checks
    const errors = []; // stores validation error messages to show in one alert

    const nameInput = document.getElementById('name'); // gets the name input field
    const nameError = document.getElementById('name-error'); // gets the error message element for the name field
    if (!nameInput.value.trim()) { // checks whether the name field is empty after removing extra spaces
      nameInput.classList.add('error'); // adds error styling to the name field
      nameError.classList.add('visible'); // shows the name error message
      errors.push('Name is required.'); // adds the error text to the error list
      isValid = false; // marks the form as invalid
    } else {
      nameInput.classList.remove('error'); // removes error styling if the name is valid
      nameError.classList.remove('visible'); // hides the name error message
    }

    const emailInput = document.getElementById('email'); // gets the email input field
    const emailError = document.getElementById('email-error'); // gets the error message element for the email field
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // defines a simple pattern to check valid email format

    if (!emailInput.value.trim()) { // checks whether the email field is empty
      emailInput.classList.add('error'); // adds error styling to the email field
      emailError.classList.add('visible'); // shows the email error message
      emailError.textContent = 'Please enter your email address.'; // sets the error text for missing email
      errors.push('Email is required.'); // adds the error to the error list
      isValid = false; // marks the form as invalid
    } else if (!emailRegex.test(emailInput.value.trim())) { // checks whether the email format is incorrect
      emailInput.classList.add('error'); // adds error styling to the email field
      emailError.classList.add('visible'); // shows the email error message
      emailError.textContent = 'Please enter a valid email address.'; // sets the error text for invalid email format
      errors.push('Email format is invalid.'); // adds the format error to the error list
      isValid = false; // marks the form as invalid
    } else {
      emailInput.classList.remove('error'); // removes error styling if the email is valid
      emailError.classList.remove('visible'); // hides the email error message
    }

    const ratingInputs = document.querySelectorAll('input[name="rating"]'); // gets all rating radio button inputs
    const ratingError = document.getElementById('rating-error'); // gets the error message element for the rating field
    const ratingSelected = Array.from(ratingInputs).some(function (r) { return r.checked; }); // checks whether at least one rating option is selected

 if (!ratingSelected) {
    if (ratingError) ratingError.classList.add('visible');
    errors.push('Please select a rating.');
    isValid = false;
  } else {
    if (ratingError) ratingError.classList.remove('visible');
  }

    if (!isValid) { // checks whether any validation error was found
      alert('Please fix the following errors before submitting:\n\n• ' + errors.join('\n• ')); // Shows all collected validation errors in one alert message
      return; // stops the function so the form is not processed further
    }

    form.style.display = 'none'; // hides the form after successful validation
    const successMsg = document.getElementById('success-message'); // gets the success message element
    if (successMsg) { // checks that the success message element exists
      successMsg.style.display = 'block'; // displays the success message
      successMsg.scrollIntoView({ behavior: 'smooth' }); // scrolls smoothly to the success message
    }
  });

  ['name', 'email'].forEach(function (id) { // loops through the input fields that should clear errors while typing
    const el = document.getElementById(id); // gets the current input element by its id
    if (!el) return; // skips this field if the element does not exist

    el.addEventListener('input', function () { // runs whenever the user types in the field
      el.classList.remove('error'); // removes the error styling from the input field
      const errEl = document.getElementById(id + '-error'); // gets the related error message element
      if (errEl) errEl.classList.remove('visible'); // hides the related error message if it exists
    });
  });
}


// ===== BUTTON RIPPLE EFFECT =====
/*
  This function adds a ripple click effect to website buttons.
  It attaches a click event to each button with the btn class, creates
  a ripple element at the click position, and removes it after the
  animation ends. The visual styling of the ripple is handled in the
  main CSS file.
*/
// ===== BUTTON RIPPLE EFFECT =====
function initRippleEffect() {
  document.querySelectorAll('.btn').forEach(function (btn) { // loops through all buttons that use the btn class
    btn.addEventListener('click', function (e) { // runs when the user clicks a button
      const ripple = document.createElement('span'); // creates a span element that will act as the ripple
      ripple.classList.add('btn-ripple'); // applies the ripple styling class from the CSS file
      ripple.style.left = (e.offsetX - 5) + 'px'; // positions the ripple horizontally based on the click point
      ripple.style.top = (e.offsetY - 5) + 'px'; // positions the ripple vertically based on the click point

      btn.appendChild(ripple); // adds the ripple element inside the clicked button

      setTimeout(function () { // waits until the animation finishes
        ripple.remove(); // removes the ripple element from the button
      }, 500);
    });
  });
}

// function initRippleEffect() {
//   document.querySelectorAll('.btn').forEach(function (btn) {
//     btn.addEventListener('click', function (e) {
//       const ripple = document.createElement('span');
//       ripple.style.cssText = `
//         position: absolute;
//         width: 10px; height: 10px;
//         background: rgba(255,255,255,0.5);
//         border-radius: 50%;
//         transform: scale(0);
//         animation: ripple-anim 0.5s linear;
//         pointer-events: none;
//         left: ${e.offsetX - 5}px;
//         top: ${e.offsetY - 5}px;
//       `;

//       if (!document.getElementById('ripple-style')) {
//         const s = document.createElement('style');
//         s.id = 'ripple-style';
//         s.textContent = `@keyframes ripple-anim { to { transform: scale(30); opacity: 0; } }`;
//         document.head.appendChild(s);
//       }

//       btn.style.position = 'relative';
//       btn.style.overflow = 'hidden';
//       btn.appendChild(ripple);

//       setTimeout(function () {
//         ripple.remove();
//       }, 500);
//     });
//   });
// }

// ===== BOOKING MODAL =====

/*
  This object stores the information of the workshop currently selected
  by the user. It is updated when the booking modal is opened, so the
  selected workshop data can be shown inside the booking form and used
  later during the booking process.
*/
var currentWorkshop = {};


/*
  This function switches the booking modal between confirmation,
  loading, success, and error states.
*/
function setBookingState(state) {
  var states = [
    'booking-state-confirm',
    'booking-state-loading',
    'booking-state-success',
    'booking-state-error'
  ];

  states.forEach(function (id) {
    var element = document.getElementById(id);
    if (element) element.hidden = true;
  });

  var activeState = document.getElementById('booking-state-' + state);
  if (activeState) activeState.hidden = false;
}

/*
  This function opens the booking modal and fills it with the selected
  workshop details before the user confirms the reservation.
*/
function openBookingModal(id, name, date, time, link) {
  if (document.body.dataset.loggedIn === '0') {
    window.location.href = 'login.php?reason=booking';
    return;
  }

  currentWorkshop = {
    id: id,
    name: name,
    date: date,
    time: time,
    link: link
  };

  document.getElementById('info-name').textContent = name;
  document.getElementById('info-date').textContent = 'Date: ' + date;
  document.getElementById('info-time').textContent = 'Time: ' + time;

  setBookingState('confirm');

  var overlay = document.getElementById('booking-overlay');
  overlay.hidden = false;
  document.body.style.overflow = 'hidden';
}

/*
  This function closes the booking modal and restores page scrolling.
*/
function closeBookingModal() {
  var overlay = document.getElementById('booking-overlay');
  if (!overlay) return;

  overlay.hidden = true;
  document.body.style.overflow = '';
}

/*
  This function sends the booking request to the PHP API, then updates
  the modal state based on whether the booking succeeds or fails.
*/
async function submitWorkshopBooking() {
  var confirmButton = document.getElementById('booking-confirm-btn');
  var errorMessage = document.getElementById('booking-error-message');

  if (!currentWorkshop.id) return;

  setBookingState('loading');

  if (confirmButton) {
    confirmButton.disabled = true;
  }

  var fullName = document.body.dataset.userName || 'SkillHub User';
  var email = document.body.dataset.userEmail || '';
  var nameParts = fullName.trim().split(' ');
  var firstName = nameParts.shift() || 'SkillHub';
  var lastName = nameParts.join(' ');

  var formData = new FormData();
  formData.append('workshop_id', currentWorkshop.id);
  formData.append('first_name', firstName);
  formData.append('last_name', lastName);
  formData.append('email', email);

  try {
    var response = await fetch('../api/create_booking.php', {
      method: 'POST',
      body: formData
    });

    var result = await response.json();

    if (result.success) {
      setBookingState('success');
      return;
    }

    if (errorMessage) {
      errorMessage.textContent = result.message || 'Booking failed. Please try again.';
    }

    setBookingState('error');
  } catch (error) {
    if (errorMessage) {
      errorMessage.textContent = 'Something went wrong while submitting your booking.';
    }

    setBookingState('error');
  } finally {
    if (confirmButton) {
      confirmButton.disabled = false;
    }
  }
}

/*
  This function wires the booking modal buttons without inline JavaScript.
*/
function initBookingConfirmation() {
  var overlay = document.getElementById('booking-overlay');
  var closeButton = document.getElementById('modal-close');
  var cancelButton = document.getElementById('booking-cancel-btn');
  var confirmButton = document.getElementById('booking-confirm-btn');
  var backButton = document.getElementById('booking-back-btn');
  var errorBackButton = document.getElementById('booking-error-back-btn');

  if (!overlay) return;

  if (closeButton) closeButton.addEventListener('click', closeBookingModal);
  if (cancelButton) cancelButton.addEventListener('click', closeBookingModal);
  if (backButton) backButton.addEventListener('click', closeBookingModal);
  if (errorBackButton) errorBackButton.addEventListener('click', closeBookingModal);

  if (confirmButton) {
    confirmButton.addEventListener('click', submitWorkshopBooking);
  }

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) {
      closeBookingModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) {
      closeBookingModal();
    }
  });
}


/*
  This function handles live workshop search and category filtering.
  It sends the current search text and selected category to the PHP API,
  receives matching workshops as JSON, and updates the workshop cards
  without reloading the page.
*/
async function initWorkshopSearch() {
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const grid = document.querySelector('.grid-2');

  // Stop if the current page does not contain the workshop search elements.
  if (!searchInput || !categoryFilter || !grid) return;

  async function loadWorkshops() {
    const searchValue = searchInput.value;
    const categoryValue = categoryFilter.value;

    const response = await fetch(
      '../api/search_workshops.php?search=' +
      encodeURIComponent(searchValue) +
      '&category=' +
      encodeURIComponent(categoryValue)
    );

    const workshops = await response.json();

    grid.innerHTML = '';

    workshops.forEach(function (workshop) {
      grid.innerHTML += `
        <div class="card">
          <img
            src="${workshop.image_path}"
            alt="${workshop.title}"
            class="card-img"
          />

          <div class="card-body">
            <div class="card-icon card-icon-web">
              <i class="fa-solid fa-laptop-code"></i>
            </div>

            <h3>${workshop.title}</h3>
            <p>${workshop.description}</p>

            <div class="card-tags" style="margin-top: 16px">
              <span class="tag tag-primary">${workshop.category_name}</span>
              <span class="tag tag-secondary">${workshop.available_seats} Seats</span>
            </div>

                <button
            type="button"
            class="btn btn-primary book-btn workshop-book-btn"
            data-workshop-id="${workshop.workshop_id}"
            data-workshop-title="${workshop.title}"
            data-workshop-date="${workshop.workshop_date}"
            data-workshop-time="${workshop.start_time} - ${workshop.end_time}"
            data-workshop-link="#"
          >
            <i class="fa-solid fa-calendar-days"></i>
            Book Workshop
          </button>
          </div>
        </div>
      `;
    });
  }

  searchInput.addEventListener('keyup', loadWorkshops);
  categoryFilter.addEventListener('change', loadWorkshops);
}

/*
  This function handles clicks on Book Workshop buttons through event delegation.
  It works for both initial workshop cards and cards added later by live search.
*/
function initBookingButtons() {
  document.addEventListener('click', function (e) {
    var button = e.target.closest('.book-btn');
    if (!button) return;

    var isLoggedIn = document.body.dataset.loggedIn === '1';

    if (!isLoggedIn) {
      window.location.href = 'login.php?reason=booking';
      return;
    }

    openBookingModal(
      button.dataset.workshopId,
      button.dataset.workshopTitle,
      button.dataset.workshopDate,
      button.dataset.workshopTime,
      button.dataset.workshopLink
    );
  });
}

// ===== PROFILE PICTURE UPLOAD MODAL =====
/*
  This function controls the profile picture upload modal.
  The avatar opens the modal, the close button and outside overlay close it,
  and the selected file name is shown before the user submits the upload form.
*/
function initProfilePictureUpload() {
  var openButton = document.getElementById('profile-avatar-open');
  var overlay = document.getElementById('profile-upload-overlay');
  var closeButton = document.getElementById('profile-upload-close');
  var fileInput = document.getElementById('profile_image');
  var fileName = document.getElementById('profile-file-name');

  if (!openButton || !overlay) return;

  function openProfileUploadModal() {
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeProfileUploadModal() {
    overlay.hidden = true;
    document.body.style.overflow = '';
  }

  openButton.addEventListener('click', openProfileUploadModal);

  if (closeButton) {
    closeButton.addEventListener('click', closeProfileUploadModal);
  }

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) {
      closeProfileUploadModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) {
      closeProfileUploadModal();
    }
  });

  if (fileInput && fileName) {
    fileInput.addEventListener('change', function () {
      fileName.textContent = fileInput.files.length
        ? fileInput.files[0].name
        : 'No file selected';
    });
  }
}

// ===== PROFILE PASSWORD MODAL =====
/*
  This function controls the change password modal on the profile page.
  The visible button opens the modal, while the close button, overlay click,
  and Escape key close it without using inline JavaScript.
*/
function initProfilePasswordModal() {
  var openButton = document.getElementById('profile-password-open');
  var overlay = document.getElementById('profile-password-overlay');
  var closeButton = document.getElementById('profile-password-close');

  if (!openButton || !overlay) return;

  function openPasswordModal() {
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closePasswordModal() {
    overlay.hidden = true;
    document.body.style.overflow = '';
  }

  openButton.addEventListener('click', openPasswordModal);

  if (closeButton) {
    closeButton.addEventListener('click', closePasswordModal);
  }

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) {
      closePasswordModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.hidden) {
      closePasswordModal();
    }
  });
}

// ===== PROFILE NAME EDIT MODE =====
/*
  This function controls the profile name edit field.
  The name starts as read-only. Clicking the pen enables editing,
  changes the icon to a check mark, and lets the user submit by
  pressing Enter or clicking the check button.
*/
function initProfileNameEdit() {
  var form = document.getElementById('profile-name-form');
  var input = document.getElementById('full_name');
  var button = document.getElementById('profile-name-edit');

  if (!form || !input || !button) return;

  var originalValue = input.value.trim();
  var isEditing = false;

  function setEditMode() {
    isEditing = true;

    input.removeAttribute('readonly');
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);

    button.classList.add('is-editing');
    button.setAttribute('aria-label', button.dataset.saveLabel || 'Save name');
    button.innerHTML = '<i class="fa-solid fa-check"></i>';
  }

  function setViewMode() {
    isEditing = false;

    input.setAttribute('readonly', 'readonly');
    input.value = originalValue;

    button.classList.remove('is-editing');
    button.setAttribute('aria-label', button.dataset.editLabel || 'Edit name');
    button.innerHTML = '<i class="fa-solid fa-pen"></i>';
  }

  button.addEventListener('click', function (e) {
    if (!isEditing) {
      e.preventDefault();
      setEditMode();
      return;
    }

    form.requestSubmit();
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && isEditing) {
      e.preventDefault();
      form.requestSubmit();
    }

    if (e.key === 'Escape' && isEditing) {
      e.preventDefault();
      setViewMode();
    }
  });

  form.addEventListener('submit', function (e) {
    var currentValue = input.value.trim();

    if (!isEditing) {
      e.preventDefault();
      return;
    }

    if (currentValue === '') {
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



/*
  This event runs after the HTML document finishes loading.
  It starts the main website features by calling the functions
  responsible for navigation behavior, active link highlighting,
  scroll animations, feedback form validation, button ripple effects,
  and booking form handling.
*/
// ===== INITIALIZE =====
document.addEventListener('DOMContentLoaded', function () {
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
});