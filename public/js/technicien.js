function showSection(sectionId){

let sections = document.querySelectorAll(".section");

sections.forEach(section => {

section.classList.remove("active");

});

setTimeout(()=>{

document.getElementById(sectionId).classList.add("active");

},100);

}



// démarrer intervention

function startIntervention(){

alert("Intervention démarrée");

}


// terminer intervention

function finishIntervention(button){

let row = button.closest("tr");

let statut = row.querySelector(".statut");

statut.textContent = "Terminée";

statut.classList.remove("en-cours");
statut.classList.add("termine");

button.innerText = "Terminé";
button.disabled = true;

}