document.addEventListener('DOMContentLoaded', function(){
  var yearElement = document.getElementById('year');
  if(yearElement){
    yearElement.textContent = new Date().getFullYear();
  }

  var navToggle = document.getElementById('navToggle');
  var navMenu = document.querySelector('.nav');
  if(navToggle && navMenu){
    navToggle.addEventListener('click', function(){
      var isOpen = navMenu.style.display === 'flex';
      navMenu.style.display = isOpen ? 'none' : 'flex';
      navToggle.textContent = isOpen ? '☰' : '✕';
    });
    
    // Close menu when clicking on a link
    document.querySelectorAll('.nav-link').forEach(function(link){
      link.addEventListener('click', function(){
        navMenu.style.display = 'none';
        navToggle.textContent = '☰';
      });
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event){
      if(!navToggle.contains(event.target) && !navMenu.contains(event.target)){
        if(navMenu.style.display === 'flex'){
          navMenu.style.display = 'none';
          navToggle.textContent = '☰';
        }
      }
    });
  }

  document.querySelectorAll('.password-toggle').forEach(function(toggle){
    var targetId = toggle.getAttribute('data-target');
    if(!targetId) return;
    var input = document.getElementById(targetId);
    if(!input) return;
    toggle.addEventListener('click', function(){
      var visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      toggle.textContent = visible ? 'Show' : 'Hide';
      toggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
    });
  });

  function loadUsers(){
    var data = localStorage.getItem('medlinkUsers');
    if(!data){
      var demoUsers = [{ identifier: 'patient@medlink.com', password: 'Patient123', name: 'Patient Demo' }, { identifier: 'receptionist@medlink.com', password: 'Reception123', name: 'Reception Demo' }, { identifier: 'doctor@medlink.com', password: 'Doctor123', name: 'Doctor Demo' }, { identifier: 'admin@medlink.com', password: 'Admin123', name: 'Admin Demo' }];
      localStorage.setItem('medlinkUsers', JSON.stringify(demoUsers));
      return demoUsers;
    }
    try { return JSON.parse(data) || []; } catch (e) { return []; }
  }

  function saveUsers(users){
    localStorage.setItem('medlinkUsers', JSON.stringify(users));
  }

  function validateEmail(value){
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(value);
  }

  var loginForm = document.getElementById('loginForm');
  if(loginForm){
    var identifierField = document.getElementById('loginIdentifier');
    var passwordInput = document.getElementById('passwordField');
    var identifierError = document.getElementById('identifierError');
    var passwordError = document.getElementById('passwordError');
    var statusText = document.getElementById('formStatus');

    loginForm.addEventListener('submit', function(event){
      event.preventDefault();
      identifierError.textContent = '';
      passwordError.textContent = '';
      statusText.textContent = '';

      var identifierValue = identifierField.value.trim();
      var passwordValue = passwordInput.value;
      var valid = true;

      if(!identifierValue){
        identifierError.textContent = 'Please enter your username or email.';
        valid = false;
      }
      if(!passwordValue.trim()){
        passwordError.textContent = 'Password is required.';
        valid = false;
      }
      if(!valid){
        statusText.textContent = 'Please fix the errors above to continue.';
        return;
      }

      var users = loadUsers();
      var user = users.find(function(u){ return u.identifier.toLowerCase() === identifierValue.toLowerCase() || (u.email && u.email.toLowerCase() === identifierValue.toLowerCase()); });
      if(!user){
        identifierError.textContent = 'No account found with that username or email.';
        statusText.textContent = 'Please check your login details.';
        return;
      }
      if(user.password !== passwordValue){
        passwordError.textContent = 'Incorrect password.';
        statusText.textContent = 'Please check your login details.';
        return;
      }

      statusText.style.color = 'var(--accent)';
      statusText.textContent = 'Login successful. Redirecting...';
      localStorage.setItem('medlinkCurrentUser', JSON.stringify(user));
      setTimeout(function(){ window.location.href = './'; }, 900);
    });
  }

  var registerForm = document.getElementById('registerForm');
  if(registerForm){
    var fullNameField = document.getElementById('fullName');
    var identifierField = document.getElementById('registerIdentifier');
    var passwordField = document.getElementById('registerPassword');
    var confirmField = document.getElementById('registerConfirm');
    var statusText = document.getElementById('registerStatus');
    var fullNameError = document.getElementById('fullNameError');
    var identifierError = document.getElementById('registerIdentifierError');
    var passwordError = document.getElementById('registerPasswordError');
    var confirmError = document.getElementById('registerConfirmError');

    registerForm.addEventListener('submit', function(event){
      event.preventDefault();
      fullNameError.textContent = '';
      identifierError.textContent = '';
      passwordError.textContent = '';
      confirmError.textContent = '';
      statusText.textContent = '';
      statusText.style.color = '#b91c1c';

      var fullNameValue = fullNameField.value.trim();
      var identifierValue = identifierField.value.trim();
      var passwordValue = passwordField.value;
      var confirmValue = confirmField.value;
      var valid = true;

      if(!fullNameValue){
        fullNameError.textContent = 'Please enter your full name.';
        valid = false;
      }
      if(!identifierValue){
        identifierError.textContent = 'Please enter your email.';
        valid = false;
      } else if(!validateEmail(identifierValue)){
        identifierError.textContent = 'Please enter a valid email address.';
        valid = false;
      }
      if(passwordValue.length < 8){
        passwordError.textContent = 'Password must be at least 8 characters.';
        valid = false;
      }
      if(passwordValue !== confirmValue){
        confirmError.textContent = 'Passwords do not match.';
        valid = false;
      }
      if(!valid){
        statusText.textContent = 'Please fix the errors above.';
        return;
      }

      var users = loadUsers();
      var existing = users.find(function(u){ return u.identifier.toLowerCase() === identifierValue.toLowerCase() || (u.email && u.email.toLowerCase() === identifierValue.toLowerCase()); });
      if(existing){
        identifierError.textContent = 'An account already exists with that email.';
        statusText.textContent = 'Please use a different email.';
        return;
      }

      var user = { name: fullNameValue, identifier: identifierValue, email: identifierValue, password: passwordValue };
      users.push(user);
      saveUsers(users);
      statusText.style.color = 'var(--accent)';
      statusText.textContent = 'Account created. Redirecting to sign in...';
      setTimeout(function(){ window.location.href = 'login.html'; }, 1000);
    });
  }

  var forgotForm = document.getElementById('forgotForm');
  if(forgotForm){
    var forgotIdentifier = document.getElementById('forgotIdentifier');
    var forgotStatus = document.getElementById('forgotStatus');
    forgotForm.addEventListener('submit', function(event){
      event.preventDefault();
      forgotStatus.style.color = '#b91c1c';
      forgotStatus.textContent = '';
      var value = forgotIdentifier.value.trim();
      if(!value){
        forgotStatus.textContent = 'Please enter your email.';
        return;
      }
      forgotStatus.style.color = 'var(--accent)';
      forgotStatus.textContent = 'If an account exists, reset instructions have been sent (frontend demo).';
    });
  }

  var resetForm = document.getElementById('resetForm');
  if(resetForm){
    var resetToken = document.getElementById('tokenField');
    var resetPassword = document.getElementById('resetPassword');
    var resetConfirm = document.getElementById('resetConfirm');
    var resetStatus = document.getElementById('resetStatus');
    resetForm.addEventListener('submit', function(event){
      event.preventDefault();
      resetStatus.style.color = '#b91c1c';
      resetStatus.textContent = '';
      var tokenValue = resetToken.value.trim();
      var passwordValue = resetPassword.value;
      var confirmValue = resetConfirm.value;
      if(!tokenValue){ resetStatus.textContent = 'Please provide your reset token.'; return; }
      if(passwordValue.length < 8){ resetStatus.textContent = 'Password must be at least 8 characters.'; return; }
      if(passwordValue !== confirmValue){ resetStatus.textContent = 'Passwords do not match.'; return; }
      resetStatus.style.color = 'var(--accent)';
      resetStatus.textContent = 'Password reset simulated — frontend only.';
    });
  }
});
