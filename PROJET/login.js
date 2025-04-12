// Vérification du formulaire de connexion
function validateLogin() {
    let email = document.getElementById("login-email").value.trim();
    let password = document.getElementById("login-password").value;
    let error = document.getElementById("login-error");
    error.innerText = "";

    if (email === "" || password === "") {
        error.innerText = "Veuillez remplir tous les champs.";
        return false;
    }

    return true;
}

// Vérification du formulaire d'inscription
function validateRegister() {
    let username = document.getElementById("reg-username").value.trim();
    let email = document.getElementById("reg-email").value.trim();
    let password = document.getElementById("reg-password").value;
    let error = document.getElementById("reg-error");
    error.innerText = "";

    let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (username.length < 3) {
        error.innerText = "Le pseudo est trop court.";
        return false;
    }

    if (!emailRegex.test(email)) {
        error.innerText = "Email invalide.";
        return false;
    }

    if (password.length < 6) {
        error.innerText = "Le mot de passe doit contenir au moins 6 caractères.";
        return false;
    }

    return true;
}

// Affiche le formulaire d'inscription
function toggleRegisterForm() {
    document.querySelector('.register-box').style.display = 'block';
    document.querySelector('.box').style.display = 'none';
    document.getElementById("reg-username").focus();
}

// Affiche le formulaire de connexion
function toggleLoginForm() {
    document.querySelector('.box').style.display = 'block';
    document.querySelector('.register-box').style.display = 'none';
    document.getElementById("login-email").focus();
}
