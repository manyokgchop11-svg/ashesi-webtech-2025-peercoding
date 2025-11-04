
// Get all users from localStorage
function getUsers() {
  let users = localStorage.getItem("users");
  if (users) {
    return JSON.parse(users);
  } else {
    return [];
  }
}

// Save users to localStorage
function saveUsers(users) {
  localStorage.setItem("users", JSON.stringify(users));
}

// SIGN UP
let signupForm = document.getElementById("signupForm");

if (signupForm) {
  signupForm.addEventListener("submit", function (event) {
    event.preventDefault();

    let username = document.getElementById("username").value;
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;
    let role = document.getElementById("role").value;

    if (username === "" || email === "" || password === "" || role === "") {
      alert("Please fill in all the fields.");
      return;
    }

    let users = getUsers();

    // check if username already exists
    for (let i = 0; i < users.length; i++) {
      if (users[i].username === username) {
        alert("That username already exists!");
        return;
      }
    }

    // add new user
    let newUser = {
      username: username,
      email: email,
      password: password,
      role: role
    };

    users.push(newUser);
    saveUsers(users);

    alert("Sign up successful! You can log in now.");
    window.location.href = "index.html";
  });
}

// LOGIN
let loginForm = document.getElementById("loginForm");

if (loginForm) {
  loginForm.addEventListener("submit", function (event) {
    event.preventDefault();

    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;

    let users = getUsers();
    let found = false;

    for (let i = 0; i < users.length; i++) {
      if (users[i].username === username && users[i].password === password) {
        alert("Welcome back, " + username + "!");
        found = true;
        break;
      }
    }

    if (!found) {
      alert("Wrong username or password. Try again.");
    }
  });
}

// CHANGE PASSWORD
let forgotPasswordForm = document.getElementById("forgotPasswordForm");

if (forgotPasswordForm) {
  forgotPasswordForm.addEventListener("submit", function (event) {
    event.preventDefault();

    let currentPassword = document.getElementById("current-password").value;
    let newPassword = document.getElementById("new-password").value;
    let confirmPassword = document.getElementById("confirm-password").value;

    if (currentPassword === "" || newPassword === "" || confirmPassword === "") {
      alert("Please fill in all the fields.");
      return;
    }

    if (newPassword !== confirmPassword) {
      alert("New passwords do not match.");
      return;
    }

    let username = prompt("Enter your username:");
    let users = getUsers();
    let found = false;

    for (let i = 0; i < users.length; i++) {
      if (users[i].username === username && users[i].password === currentPassword) {
        users[i].password = newPassword;
        saveUsers(users);
        alert("Password changed successfully!");
        found = true;
        window.location.href = "index.html";
        break;
      }
    }

    if (!found) {
      alert("Username not found or current password incorrect.");
    }
  });
}
