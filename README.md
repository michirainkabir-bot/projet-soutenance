<?php if (isset($_GET['erreur'])): ?>
    <p style="color:red; text-align:center;">Email, mot de passe ou rôle incorrect.</p>
<?php endif; ?>











Section 2 : Cahier de charges

I.	Définition
Un cahier de charges est un document contractuel à respecter lors de la conception d’un projet. Le cahier de charges permet au maitre ouvrage de faire savoir au maitre d’œuvre ce qu’il attend de lui lors de la réalisation du projet, entrainant des pénalités en cas de non-respect. Il décrit précisément les besoins auxquels le prestataire ou le soumissionnaire doit répondre, et organise la relation entre les différents acteurs tout au long du projet.

II.	Délimitation du périmètre du projet
Le périmètre du projet est volontairement limité afin de garantir sa faisabilité dans le cadre du stage académique.
1.	Périmètre fonctionnel
Le projet couvre les fonctionnalités suivantes :
	L’authentification des utilisateurs (Administrateur, Technicien, Client) ;
	La gestion des clients (création, modification, suppression, consultation) ;
	La gestion des prestations de services ;
	L’affectation des techniciens aux prestations ;
	L’enregistrement et le suivi des interventions ;
	La génération et la consultation des factures ;
	La production de rapports simples (prestations, interventions, facturation).

2.	Périmètre organisationnel
Le projet concerne uniquement les activités internes de l’entreprise FGCL liées à la gestion des prestations de services. Il implique principalement :
	L’administration de l’application par le responsable ou l’administrateur ;
	L’utilisation du système par les techniciens pour les interventions ;
	L’accès limité des clients pour la consultation et la demande de prestations.

3.	Périmètre technique
Le projet se limite à :
	Une application informatique locale ou web ;
	Une base de données centralisée ;
	Un nombre restreint d’utilisateurs ;
	Des outils de développement standards adaptés au contexte du stage.

III.	Délimitation du système d’information
Le système d’information objet de cette étude concerne la gestion des prestations de services de l’entreprise FGCL. Il vise à organiser, stocker, traiter et diffuser les informations nécessaires au bon fonctionnement des activités liées aux prestations de services.
	Limites fonctionnelles du système d’information
Le système d’information prend en charge les fonctions suivantes :
-	L’authentification et la gestion des utilisateurs ;
-	La gestion des clients ;
-	La gestion des prestations de services ;
-	La gestion des interventions réalisées par les techniciens ;
-	La génération et la consultation des factures ;
-	La production de rapports de suivi et d’aide à la décision.

	Acteurs du système d’information
Les principaux acteurs intervenant dans le système d’information sont :
-	L’Administrateur ;
-	Le Technicien ;
-	Le Client.
Chacun de ces acteurs dispose de droits d’accès spécifiques selon son rôle.
	Frontière du système d’information
Le système d’information commence à partir de la saisie des données par les utilisateurs autorisés et se termine à la production des informations exploitables (rapports, factures, historiques).
Les éléments extérieurs au système d’information sont :
-	Les clients externes ne disposant pas d’accès au système ;
-	Les partenaires et fournisseurs ;
-	Les systèmes comptables et financiers externes ;
-	Les plateformes de paiement.

	Limites techniques
Le système d’information est limité à :
-	Une base de données centralisée ;
-	Un accès sécurisé par identifiant et mot de passe ;
-	Un environnement de développement défini ;
-	Une utilisation dans un cadre interne à FGCL.

IV.	Besoins fonctionnels
 Les besoins fonctionnels définissent les services que le système d’information doit fournir afin de répondre aux exigences de l’entreprise FGCL dans la gestion de ses prestations de services. Il s’agit de produire un système adopté aux utilisateurs :
	De s’authentifier et gérer les profils utilisateurs ;
	De créer un nouveau compte utilisateur ;
	De modifier un compte utilisateur ;
	De supprimer un compte utilisateur ;
	D’enregistrer les clients ;
	De modifier et de supprimer les informations clients ;
	De créer et mettre à jour des prestations ;
	D’affecter des techniciens aux prestations ;
	De suivre l’état des prestations ;
	D’enregistrer les interventions réalisées ;
	De mettre à jour le statut des interventions ;
	De conserver l’historique des interventions ;
	De générer automatiquement des factures après intervention ;
	De consulter et d’imprimer les factures ;
	D’assurer le suivi des factures émises ;
	De produire des rapports sur les prestations réalisées ;
	De produire des rapports sur les interventions des techniciens ;
	Fournir des statistiques pour l’aide à la décision.

V.	Besoins non fonctionnels
Les besoins non fonctionnels définissent les contraintes de qualité, de performance et de fonctionnement auxquelles le système d’information doit répondre afin d’assurer une utilisation efficace et fiable au sein de l’entreprise FGCL.
	Sécurité
-	Le système doit garantir la confidentialité des données.
-	L’accès au système doit être protégé par un mécanisme d’authentification sécurisé.
-	Les mots de passe doivent être stockés de manière sécurisée.
-	Le système doit empêcher les accès non autorisés.

	Performance
-	Le système doit répondre aux requêtes dans un délai raisonnable.
-	Le temps de chargement des interfaces doit être court.
-	Le système doit pouvoir gérer plusieurs utilisateurs simultanément.

	Fiabilité
-	Le système doit assurer la cohérence et l’intégrité des données.
-	Le système doit fonctionner de manière stable sans interruptions fréquentes.
-	Les données ne doivent pas être perdues en cas d’erreur ou de panne.

	Ergonomie et convivialité
-	Le système doit disposer d’interfaces simples et intuitives.
-	Les écrans doivent être clairs et faciles à comprendre.
-	Le système doit être utilisable par des utilisateurs non spécialistes en informatique.

	Disponibilité
-	Le système doit être disponible pendant les heures de travail de l’entreprise.
-	Le système doit permettre une reprise rapide en cas d’arrêt.

	Maintenabilité
-	Le système doit être facile à maintenir et à faire évoluer.
-	Les mises à jour doivent pouvoir être effectuées sans perturber le fonctionnement normal.
-	Le code doit être structuré et documenté.

	Portabilité
-	Le système doit pouvoir fonctionner sur différents environnements matériels.
-	Le système doit être compatible avec les systèmes d’exploitation courants.

VI.	Rôles respectifs des parties prenantes du projet
Lors de la réalisation d’un projet digital ou informatique, il y a toujours deux grandes responsabilités : celui qui définit ce qu’il faut faire c’est le maitre d’ouvrage (MOA) et celui qui définit comment le faire et assurer la réalisation c’est le maitre d’œuvre (MOE). Les parties engagées dans la réalisation de ce projet sont les suivantes :
	Le maître d’ouvrage qui, est l’entreprise FGCL dont la responsabilité est de définir le besoin car en effet il connaît la nature du projet à réaliser. Il a pour rôle de fixer de discuter du budget adapté et des délais nécessaires pour atteindre l'objectif.
	Le maître d’œuvre ici qui est constitué de notre encadreur académique et de nous. Nous sommes responsables du bon déroulement du projet en termes de budget, de délais et de qualité d'exécution. Notre rôle est de réaliser le projet et d’accompagner le client même après la livraison.

VII.	Ressources financières
Comme tout projet informatique nécessite les coûts, ainsi, pour la mise en place de notre projet, nous avons fait recours à plusieurs ressources financières. A l’instar de :
DesignationNbre  i intervenentNbre jourPrix par jourMateriel donner les caracteristiques///350 000                                                                             Analyse des besoins020510 000100 000Conception détaillée021015 000300 000Programmation0130  15 000450 000Test et vérification0205  10 000100 000Déploiement et test d’integration011015 000150 000Formation de l’utilisateur010615 00090 000Connexion internet/90/75 000Transport0130200060 000Tableau 3 : Tableau de répartition des ressources financières

VIII.	Contraintes techniques
Pour développer notre application, nous disposerons d’une architecture a deux niveaux existants sur laquelle nous devons baser notre application. Elle est également une application multi utilisateur. La structure de notre application doit être flexible pour mettre en place facilement dans les ordinateurs de la bibliothèque. De plus, le développeur devra suivre toutes les normes techniques pour une meilleure performance, maintenance et facilitant la mise à jour.





[Rapport de stage.pdf](https://github.com/user-attachments/files/26226650/Rapport.de.stage.pdf)

