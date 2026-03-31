function showSection(sectionId) {
    document.querySelectorAll(".section").forEach(section => {
        section.classList.remove("active");
    });
    document.getElementById(sectionId).classList.add("active");
}

function openDemandeModal() {
    document.getElementById("demandeModal").style.display = "flex";
}

function closeDemandeModal() {
    document.getElementById("demandeModal").style.display = "none";
}

function closeSuccessModal() {
    document.getElementById("successModal").style.display = "none";
}