function showSection(sectionId){

let sections=document.querySelectorAll(".section");

sections.forEach(section=>{
section.style.display = "none"
section.classList.remove("active");
});

document.getElementById(sectionId).classList.add("active")
document.getElementById(sectionId).style.display = "block";

}
setTimeout(()=>{

document.getElementById(sectionId).classList.add("active");

},100);


/* CLIENT */

function openModal(){
document.getElementById("clientModal").style.display="flex";
}

function closeModal(){
document.getElementById("clientModal").style.display="none";
}

/* AJOUTER CLIENT */

document.getElementById("clientForm").addEventListener("submit", function(e){

e.preventDefault();

let id = document.getElementById("id").value;
let nom = document.getElementById("nom").value;
let email = document.getElementById("email").value;
let telephone = document.getElementById("telephone").value;
let adresse = document.getElementById("adresse").value;

let table = document.getElementById("tableClients").getElementsByTagName("tbody")[0];

let newRow = table.insertRow();

newRow.innerHTML = `
<td>${id}</td>
<td>${nom}</td>
<td>${email}</td>
<td>${telephone}</td>
<td>${adresse}</td>
<td>
<button onclick="deleteClient(this)">
<i class="fa-solid fa-eye"></i>
<i class="fa-solid fa-pen"></i>
<i  id="delete" class="fa-solid fa-trash"></i>
</button>
</td>
`;

this.reset();

closeModal();

});

/* SUPPRIMER CLIENT */

function deleteClient(button){

let row = button.parentElement.parentElement;

row.remove();

}


/* TECHNICIEN */

function openTechModal(){
document.getElementById("techModal").style.display="flex";
}

function closeTechModal(){
document.getElementById("techModal").style.display="none";
}

/* ajouter technicien */

document.getElementById("techForm").addEventListener("submit", function(e){

e.preventDefault();

let id = document.getElementById("techId").value;
let nom = document.getElementById("techNom").value;
let email = document.getElementById("techEmail").value;
let tel = document.getElementById("techTel").value;
let specialite = document.getElementById("techSpecialite").value;

let table = document.getElementById("tableTech").getElementsByTagName("tbody")[0];

let newRow = table.insertRow();

newRow.innerHTML = `
<td>${id}</td>
<td>${nom}</td>
<td>${email}</td>
<td>${tel}</td>
<td>${specialite}</td>
<td>
<button onclick="deleteTech(this)">
<i class="fa-solid fa-eye"></i>
<i class="fa-solid fa-pen"></i>
<i  id="delete" class="fa-solid fa-trash"></i>
</button>
</td>
`;

this.reset();

closeTechModal();

});

/* supprimer technicien */

function deleteTech(button){

let row = button.parentElement.parentElement;

row.remove();

}




/* PRESTATION */

function addPrestation(){

let id=prompt("Id");
let nom=prompt("Nom prestation");
let description=prompt("Description");
let prix=prompt("Prix");

let table=document.getElementById("listePrestations");

let row=table.insertRow();

row.innerHTML=
"<td>"+id+"</td><td>"+nom+"</td><td>"+description+"</td><td>"+prix+"</td><td<i class= 'fa-solid fa-eye' ></i><i class= 'fa-solid fa-pen' ></i><i  id= 'delete' class= 'fa-solid fa-trash' ></i></td>";


}

// ouvrir modal intervention
function openModal(){
    document.getElementById("modalIntervention").style.display = "flex";
}

// fermer modal intervention
function closeModal(){
    document.getElementById("modalIntervention").style.display = "none";
}

// ouvrir modal succès
function openSuccessModal(){
    document.getElementById("successModal").style.display = "flex";
}

// fermer modal succès
function closeSuccessModal(){
    document.getElementById("successModal").style.display = "none";
}

// fermer en cliquant dehors
window.onclick = function(e){
    let modal1 = document.getElementById("modalIntervention");
    let modal2 = document.getElementById("successModal");

    if(e.target === modal1){
        modal1.style.display = "none";
    }
    if(e.target === modal2){
        modal2.style.display = "none";
    }
}

// SUBMIT FORMULAIRE
document.getElementById("formIntervention").addEventListener("submit", function(e){
    e.preventDefault();

    closeModal();         // fermer formulaire
    openSuccessModal();   // afficher succès
});


// Graphique interventions par mois
const ctx1 = document.getElementById('interventionsChart').getContext('2d');
const interventionsChart = new Chart(ctx1, {
  type: 'line',
  data: {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [{
      label: 'Nombre d’interventions',
      data: [12, 19, 10, 15, 20, 25],
      backgroundColor: 'rgba(0,123,255,0.2)',
      borderColor: 'rgba(0,123,255,1)',
      borderWidth: 2,
      tension: 0.4,
      fill: true
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: true, position: 'top' },
      tooltip: { mode: 'index', intersect: false }
    },
    scales: {
      y: { beginAtZero: true },
    }
  }
});

// Graphique statut interventions
const ctx2 = document.getElementById('statutChart').getContext('2d');
const statutChart = new Chart(ctx2, {
  type: 'bar', 
  data: {
    labels: ['En attente', 'En cours', 'Terminées'],
    datasets: [{
      data: [10, 8, 7],
      backgroundColor: [
        'red',
        'orange',
        'green'
      ],
      hoverOffset: 10
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: { mode: 'nearest' }
    }
  }
});


document.addEventListener("DOMContentLoaded",function(){

showSection("dashboard");

});