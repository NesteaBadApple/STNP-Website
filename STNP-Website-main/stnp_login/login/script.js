function showForm(id) {
    document.getElementById("login-form").classList.remove("active");
    document.getElementById("register-form").classList.remove("active");

    document.getElementById(id).classList.add("active");
}
