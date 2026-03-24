function showSection(sectionId){

let sections = document.querySelectorAll(".section");

sections.forEach(section => {
section.classList.remove("active");
});

document.getElementById(sectionId).classList.add("active");

}


 /* PAIEMENT FACTURE*/

function payerFacture(button){

let row = button.closest("tr");

let statut = row.querySelector(".attente");

statut.innerText = "Payée";

statut.classList.remove("attente");
statut.classList.add("termine");

button.innerText = "Payé";
button.disabled = true;

alert("Paiement effectué avec succès");

}

/*demande*/

function openDemandeModal(){
document.getElementById("demandeModal").style.display="flex";
}

function closeDemandeModal(){
document.getElementById("demandeModal").style.display="none";
}

document.getElementById("demandeForm").addEventListener("submit", function(e){

e.preventDefault();

this.reset();

closeDemandeModal();

});

document.getElementById("demandeForm").addEventListener("submit", function(e){

e.preventDefault();

/* fermer modal formulaire */
closeDemandeModal();

/* afficher modal succès */
document.getElementById("successModal").style.display="flex";

});

/* fermer modal succès */

function closeSuccessModal(){
document.getElementById("successModal").style.display="none";
/*windows.location : ""*/
}