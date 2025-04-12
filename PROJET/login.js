function validateRegister() {
    let username = document.getElementById("reg-username").value;
    let email = document.getElementById("reg-email").value;
    let password = document.getElementById("reg-password").value;
    let error = document.getElementById("reg-error");

    let emailRegex = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;


    if (username.length < 3) {
        error.innerText = "Nom d'utilisateur trop court.";
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


function validateLogin() {
    let email = document.getElementById("login-email").value;
    let password = document.getElementById("login-password").value;
    let error = document.getElementById("login-error");

    if (email === "" || password === "") {
        error.innerText = "Veuillez remplir tous les champs.";
        return false;
    }
    return true;
}
