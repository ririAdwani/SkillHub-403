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
  This function opens the booking modal and fills it with the selected
  workshop information. It stores the workshop data in currentWorkshop,
  updates the modal text fields, resets the booking form, clears any old
  validation errors, and then displays the modal overlay. It also disables
  page scrolling while the modal is open.
*/
function openBookingModal(name, date, time, link) {
  currentWorkshop = { name: name, date: date, time: time, link: link }; // stores the selected workshop details in the shared currentWorkshop object

  // shows the workshop name, date, and time inside the modal
  document.getElementById('info-name').textContent = '🎓 ' + name;
  document.getElementById('info-date').textContent = '📅 ' + date;
  document.getElementById('info-time').textContent = '🕐 ' + time;

  var linkEl = document.getElementById('info-link'); // gets the element that displays the workshop link
  linkEl.textContent = '🔗 ' + link; // shows the workshop link text inside the modal
  linkEl.href = link; // sets the actual hyperlink destination

  document.getElementById('booking-form').reset();
  clearBookingErrors(); // removes old validation errors before showing the form again

  document.getElementById('booking-overlay').style.display = 'flex'; // makes the booking modal overlay visible
  document.body.style.overflow = 'hidden';  // prevents background page scrolling while the modal is open
}

/*
  This function closes the booking modal and restores the normal page view.
  It hides the modal overlay if it exists, then returns the page scrolling
  behavior back to normal.
*/
function closeBookingModal() {
  var overlay = document.getElementById('booking-overlay');
  if (overlay) overlay.style.display = 'none'; // hides the overlay if it exists
  document.body.style.overflow = ''; // restores normal page scrolling
}

/*
  This function clears the validation error state from the booking form.
  It removes the error styling from the required input fields and hides
  their related error messages, so the form appears clean when reopened
  or reused.
*/
function clearBookingErrors() {
  ['b-firstname', 'b-email'].forEach(function (id) { // loops through the booking fields that may contain validation errors
    var el = document.getElementById(id); // gets the current input field
    if (el) el.classList.remove('error'); // removes the error style from the field if it exists

    var err = document.getElementById(id + '-error'); // gets the related error message element
    if (err) err.classList.remove('visible'); // hides the error message if it exists
  });
}

/*
  This function handles validation and submission for the workshop booking form.
  It checks that the required fields are filled correctly, especially the first
  name and email fields, and prevents confirmation if the entered data is not
  valid. If the form passes validation, it closes the booking modal and shows
  a confirmation message that includes the participant information and the
  selected workshop details. It also removes error styling while the user is
  correcting the input.
*/
function initBookingForm() {
  var form = document.getElementById('booking-form');
  if (!form) return;

  form.addEventListener('submit', function (e) {  // runs when the user submits the booking form
    e.preventDefault();

    var isValid = true; // tracks whether all required validation checks pass
    var errors = []; // stores validation error messages to show them together

    var firstNameInput = document.getElementById('b-firstname');
    var firstNameError = document.getElementById('b-firstname-error');
    if (!firstNameInput.value.trim()) { // checks whether the first name field is empty
      firstNameInput.classList.add('error');
      firstNameError.classList.add('visible');
      errors.push('First name is required.');
      isValid = false;
    } else {
      firstNameInput.classList.remove('error');
      firstNameError.classList.remove('visible');
    }

    var emailInput = document.getElementById('b-email');
    var emailError = document.getElementById('b-email-error');
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // defines a pattern for checking basic email format

    if (!emailInput.value.trim()) {
      emailInput.classList.add('error');
      emailError.classList.add('visible');
      emailError.textContent = 'Email address is required.';
      errors.push('Email address is required.');
      isValid = false;

    } else if (!emailRegex.test(emailInput.value.trim())) {
      emailInput.classList.add('error');
      emailError.classList.add('visible');
      emailError.textContent = 'Please enter a valid email address.';
      errors.push('Email format is invalid.');
      isValid = false;
    } 
    
    else {
      emailInput.classList.remove('error');
      emailError.classList.remove('visible');
    }

    if (!isValid) { // checks whether the form failed any validation rule
      alert('Please fix the following before confirming:\n\n• ' + errors.join('\n• '));  // shows all collected validation errors in one alert
      return;
    }

    var firstName = firstNameInput.value.trim(); // stores the cleaned first name value
    var lastName = document.getElementById('b-lastname').value.trim(); // gets the optional last name value
    var email = emailInput.value.trim();  // stores the cleaned email value
    var fullName = lastName ? firstName + ' ' + lastName : firstName;

    closeBookingModal();

    alert(
      '🎉 You have successfully reserved a seat in this workshop!\n\n' +
      '👤 Name: ' + fullName + '\n' +
      '📧 Email: ' + email + '\n' +
      '📌 Workshop: ' + currentWorkshop.name + '\n' +
      '📅 Date: ' + currentWorkshop.date + '\n' +
      '🕐 Time: ' + currentWorkshop.time + '\n' +
      '🔗 Link: ' + currentWorkshop.link + '\n\n' +
      '✉️ Check your email (' + email + ') for the workshop details and confirmation.'
    );
  });

  var fnField = document.getElementById('b-firstname'); // gets the first name field for live error clearing
  if (fnField) {
    fnField.addEventListener('input', function () { // runs whenever the user types in the first name field
      fnField.classList.remove('error');
      document.getElementById('b-firstname-error').classList.remove('visible');
    });
  }

  var emField = document.getElementById('b-email'); // gets the email field for live error clearing
  if (emField) {
    emField.addEventListener('input', function () {
      emField.classList.remove('error');
      document.getElementById('b-email-error').classList.remove('visible');
    });
  }
}


/*
  This event listener closes the booking modal when the user clicks
  on the overlay area outside the modal content. It checks whether
  the clicked element is the overlay itself, then calls the function
  that hides the modal.
*/
document.addEventListener('click', function (e) {
  var overlay = document.getElementById('booking-overlay');
  if (overlay && e.target === overlay) {
    closeBookingModal();
  }
});

/*
  This event listener closes the booking modal when the user presses
  the Escape key on the keyboard. It improves usability by giving the
  user another simple way to dismiss the modal.
*/
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    closeBookingModal();
  }
});

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
  initBookingForm(); // enables validation and confirmation handling for the booking form
});