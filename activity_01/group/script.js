// Helper function to get users from localStorage
function getUsers() {
  const users = localStorage.getItem("users");
  return users ? JSON.parse(users) : [];
}

// Helper function to save users to localStorage
function saveUsers(users) {
  localStorage.setItem("users", JSON.stringify(users));
}

// Handle signup
const signupForm = document.getElementById("signupForm");
if (signupForm) {
  signupForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const role = document.getElementById("role").value;

    if (!username || !email || !password || !role) {
      alert("Please fill in all fields.");
      return;
    }

    const users = getUsers();

    // Check for duplicate username
    if (users.some((u) => u.username === username)) {
      alert("Username already exists. Please choose another one.");
      return;
    }

    // Add new user
    users.push({ username, email, password, role });
    saveUsers(users);

    alert("Registration successful! You can now log in.");
    window.location.href = "index.html";
  });
}

// Handle login
const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    const users = getUsers();
    const user = users.find(
      (u) => u.username === username && u.password === password
    );

    if (user) {
      alert(`Welcome back, ${user.username}!`);
      // You can redirect to dashboard.html or another page here
    } else {
      alert("Invalid credentials. Please try again.");
    }
  });
}

// Handle password reset
const forgotPasswordForm = document.getElementById("forgotPasswordForm");
if (forgotPasswordForm) {
  forgotPasswordForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const currentPassword = document
      .getElementById("current-password")
      .value.trim();
    const newPassword = document.getElementById("new-password").value.trim();
    const confirmPassword = document
      .getElementById("confirm-password")
      .value.trim();

    if (!currentPassword || !newPassword || !confirmPassword) {
      alert("Please fill in all fields.");
      return;
    }

    if (newPassword !== confirmPassword) {
      alert("New passwords do not match.");
      return;
    }

    const users = getUsers();
    const username = prompt("Enter your username to confirm password change:");
    const userIndex = users.findIndex((u) => u.username === username);

    if (userIndex === -1) {
      alert("Username not found.");
      return;
    }

    if (users[userIndex].password !== currentPassword) {
      alert("Current password is incorrect.");
      return;
    }

    users[userIndex].password = newPassword;
    saveUsers(users);

    alert("Password updated successfully!");
    window.location.href = "index.html";
  });
}
