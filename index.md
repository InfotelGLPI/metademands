# Besoin initial

Beaucoup de clients souhaitent mettre en place des demandes standards, donc des formulaires personnalisés afin de créer des tickets multiples (affectés à X groupes différents).

La création d’une méta-demande doit générer un ticket contenant les informations provenant du wizard et y lier des tickets liés si besoin.

# Le plugin metademands

Un plugin GLPI « metademands » a été développé pour prendre en charge le processus de création d’une demande standard (méta-demande).

**Dans ce document, demande standard = méta-demande.**

## Saisir des méta-demandes

La saisie des méta-demandes concerne :

- Le choix de la méta-demande à saisir.
- La saisie du formulaire de la méta-demande.
- La création des tickets résultant.

Tout d’abord il faut choisir la méta-demande :

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/9a792af32fffc8b45ff9c7cf720c0c18.png)

ou

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/menu_sans_icone.png)

> Une option dans la configuration permet de choisir l'affichage des méta-demandes : sous forme de liste ou via un icône : **Activer l'affichage des métademandes via un icône** {.is-info}

Ensuite il faut remplir la méta-demande.

Il est possible de revenir en arrière à chaque étape, afin de corriger les éventuelles erreurs.

Après la validation de formulaire, un ticket est créé dans GLPI (les informations du wizard seront présentes dans la description du ticket).

Exemple de wizards générés :

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/84d693b2ba4db4023f376e2b61557dbe.png)

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/03b0bd9d45bb89c453645c84e199eae6.png)

Après chaque validation de formulaire, les tickets enfants sont créés dans GLPI.

> Tant qu’il existe une méta-demande dans les sous-demandes, il sera demandé de remplir le formulaire concerné. Par exemple, une sous-demande est une méta-demande, qui contient elle-même une autre méta-demande etc… {.is-info}



# Configurer des méta-demandes


La configuration des méta-demandes concerne :

- la création de la méta-demande elle-même
- La configuration des champs du formulaire
- La configuration des tickets créés par la méta-demande

Le processus de création d’une méta-demande est le suivant :

## Création de la méta-demande

Il est possible d’ajouter des méta-demandes en cliquant sur + dans le fil d'ariane du menu **Assistance > Meta-Demandes**

Exemple :

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/b82ae03b59479a0fff6399ee1ca1f09b.png)

- **Type** : Il est possible de choisir le type de la méta-demande (incident ou demande).
- **Catégorie** : Le champ « **Catégorie** » est obligatoire car il permet de relier une méta-demande à une catégorie de ticket.
- **URL** : Une URL (liée à chaque méta-demande) sera générée (pour être utilisables directement depuis un lien sur intranet).
- **Icône** : Cette option permet de choisir l'icône lié à la métademandes (librairie https://fontawesome.com/).
- **Utiliser comme panier** : Permet de créer plusieurs métademandes dans une seule demande (mode panier).
- **Nécessite la validation pour créer les tickets enfants** : Ajoute une étape intermédiaire de validation du ticket père pour le lancement de la création des tickets enfants associés.
- **Masquer les valeurs "Non" des champs Oui/Non dans les tickets** : Permet d'envoyer dans les tickets créés et dans le PDF, uniquement les blocs ayant des valeurs et les valeurs Oui des champs du type Oui/Non.
- **Couleur de l'arrière plan** : Permet de définir une couleur sur l'arrière plan du formulaire.
- **Couleur du titre** : Permet de définir une couleur du titre du formulaire.

> Si le plugin Service Catalog est installé, lors de la sélection d'une catégorie dans le catalogue de service, l'utilisateur sera automatiquement redirigé vers la métademande correspondante.
{.is-info}
> 
## Créer le Wizard de la méta-demande

Le plugin permettra de lister les champs proposés / obligatoire de la demande, pour ensuite générer automatiquement le Wizard de création de demande (par l’utilisateur).

Exemple :

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/1e82488c75bac9ae81ea59a530b9e34c.png)

### Champs disponibles

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/add_field_wizard.png)

## type de champs

- **Intitulé** : Accès aux intitulés de GLPI (lieux, statuts...)

- **Objet GLPI** : Accès aux objets de GLPI (ordinateurs, utilisateurs, groupes...)
- **Liste déroulante** : Création d'une liste personnalisée et accès aux listes (Urgence / Impact / Priorité / Mes éléments)
- **Liste déroulante multiple** : Création d'une liste personnalisée multiple et accès aux listes multiples Applicatifs / Utilisateurs
- **Texte** : Zone de texte
- **Case à cocher**
- **Zone de commentaire**
- **Date**
- **Date & heure**
- **Informations** : Ajout d'une information complémentaire
- **Intervalle de dates**
- **Intervalle de date & heure**
- **Oui / Non** : Liste déroulante de type Oui / Non
- **Ajouter un document** : Ajout de fichiers
- **Titre** : Titre du bloc (généralement)
- **Bouton radio**
- **Lien** : URL de type bouton ou Lien
- **Nombre**

## Détail des champs

Pour chaque type de champ, une fois celui-ci créé vous pouvez définir des comportements supplémentaires :

![dropdown_field.png](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/dropdown_field.png)

- **Champ obligatoire** : Définir le champ comme obligatorie à la saisie
- **Cacher le titre** : Cacher le titre du champ / N'afficher que le champ lui même
- **Label supplémentaire** : Permet d'afficher une zone d'information liée au champ
- **Commentaire** : Permet d'afficher une zone commentaire (Tooltip)
- **Prend toute la ligne** : Permet de définir si le champ prend une ligne entière (le prochain champ sera positionné sur la ligne suivante)
- **Bloc** : Permet de définir le bloc où positionner le champ
- **Afficher le champ après** : Permet de définir l'ordonnancement des champs
- **Utiliser ce champ comme champ du ticket** : Permet de définir un champ du ticket père généré avec un champ du formulaire
- **Utiliser ce champ comme champ des tickets enfants** : Permet de définir aussi un champ des tickets enfants générés avec un champ du formulaire
- **Lier ce champ à un champ utilisateur** : Permet de lier le champ à un autre champ de type utilisateur (exemple : Lieu de l'utilisateur / Groupe de l'utilisateur)

## Option des champs

Pour chaque type de champ, une fois celui-ci créé vous pouvez définir des options supplémentaires:

![fields_options.png](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/fields_options.png)

- **Valeur à vérifier ( ----- = Valeur non nulle)** : Sélection de la valeur concernée
- **Lier le champ à une sous demande** : Si la valeur sélectionnée est égale à la valeur à vérifier, la sous-demande est créée
- **Lier le champ à un autre champ** : Si la valeur sélectionnée est égale à la valeur à vérifier, le champ devient obligatoire
- **Lier à un champ caché** : Si la valeur sélectionnée est égale à la valeur à vérifier, le champ devient visible
- **Lier un bloc caché** : Si la valeur sélectionnée est égale à la valeur à vérifier, le bloc devient visible

> TODO INFOTEL : Expliquer les actions massives / Les blocs.
{.is-warning}

## Fonctions avancées du plugin « metademands »


Le plugin « metademands » propose des fonctions supplémentaires.

La configuration avancée des méta-demandes concerne :

- La configuration des champs du ticket père (champs obligatoires du gabarit de ticket)
- La traduction des métademandes
- La configuration de l’enchainement des tickets de la méta-demande
- La limitation de l'utilisation du formulaire à certains groupes

### Champs du ticket père

L’onglet « **Champs du ticket père** » permet de définir les champs à remplir pour le ticket père de la méta-demande.

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/56874d097d01038dcff630518b3fb433.png)

Après avoir saisi et validé une méta-demande, le ticket père sera prérempli avec ces valeurs.

> Les champs du ticket père sont synchronisés en fonction du gabarit de l’entité, ou de la catégorie de la méta-demande. {.is-info}

> Si des champs obligatoires sont définis dans le gabarit, ils seront directement ajoutés dans l’onglet « **Champs du ticket père** » et non supprimables. {.is-info}

### Traduction des métademandes

Il est possible de traduire la métademande dans différentes langues (il faudra le faire aussi au niveau de chaque champ du formulaire)

### Création des sous-demandes

L’onglet « **Création des sous-demandes** » permet de configurer l’enchainement des tickets de la méta-demande.

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/658fe857b0a498a8a6b5632ef7efe98a.png)

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/658fe857b0a498a8a6b5632ef7efe98b.png)

Tout d’abord, il faut choisir le type de sous-demande : « **Ticket** » ou « **Méta-demande** ». Si le type est « **Ticket** », les champs standards de création de ticket apparaissent. Si le type est « **Méta-demande** », une liste deméta-demandes s’affiche.

Il est possible d’ajouter plusieurs sous-demandes de type ticket, par contre une seule sous-demande de type méta-demande peut être ajoutée.

Les sous-demandes sont affichées sous forme de liens cliquable, pour permettre d’accéder à leur fiche et faire d’éventuelles modifications.

> Les données du ticket père sont ajoutées dans le champ de la description. {.is-info}

> La description du ticket père est transmise à la suite de la description de chaque ticket fils (si option de configuration activée : **Tickets enfants récupèrent les informations du ticket parent**). {.is-info}

### Fonctionnement de l’ordre d’enchainement

Il est possible de définir un ordre d’enchainement en choisissant de créer une sous-demande après une autre. Cela est possible en sélectionnant une sous-demande dans le champ « Créer après la sous-demande »

Si le champ « Créer après la sous-demande » est vide, la sous demande ne dépend pas d’un ordre d’exécution précis.
L’ordre s’affiche alors en « Racine », dans la liste des sous-demandes.

**Attention** : Si la sous-demande est une méta-demande, elle ne dépend d’aucun ordre d’exécution, son ordre est en «Racine ».

### Limitation de l'utilisation du formulaire à certains groupes

On peut limiter l'utilisation du formulaire à certains groupes. Seuls les groupes déclarés seront habilités à créer ces méta-demandes.

## Gestion des tickets enfants créés

Les tickets enfants créés dans l’enchainement de la méta-demande doivent respecter certaines contraintes.

### Choix ticket simple/méta-demande

Le ticket enfant créé lors de l’enchainement de la méta-demande peut être une autre méta-demande ou un ticket simple.
Voir le paragraphe *Ajout des sous-demandes*

### Création de ticket suivant à la volée

Lorsque le ticket est résolu ou clos, le suivant est créé à la volée.

### Gestion de clôture du ticket

La fermeture d’un ticket doit vérifier plusieurs points :

- Un ticket « père » ne sera pas clôturé sans que l’ensemble de ses tickets « fils » ne soient déjà clôturés.
- La fermeture du ticket « fils » ne doit pas entrainer de notification.

### Blocage de la configuration des sous-demandes/duplication

Lorsque les tickets de la méta-demande sont créés dans GLPI, la configuration de la méta-demande concernée est limitée.

Il est alors impossible de modifier l’ordre ou d’ajouter des sous-demandes. Cela est mis en place pour garder l’intégrité de l’enchainement des tickets en cours dans GLPI.

Pour pallier cela il est possible de dupliquer la méta-demande. Cela a pour effet de la copier à l’identique, il est ainsi possible faire les modifications souhaitées.

> Si les tickets en cours de la méta-demande sont tous en corbeille ou purgés, les sous-demandes deviennent de nouveau configurables. {.is-info}

### Onglet de suivi d’avancement des tickets

Un onglet sera visible au niveau du ticket « pères »de GLPI pour le suivi de l’avancement des tickets « fils » :

- **Statut** : correspond au statut du ticket. Il peut prendre les 5 valeurs natives dans l’outil :
    - « Nouveau »
    - « En cours »
    - « En attente »
    - « Résolu »
    - « Clos »
- **Date SLA** : correspond à la date d’engagement de réalisation de la tâche du ticket « fils » vis-à-vis du SLA du
  ticket « père ». Cette date correspond dans l’outil à la « Date d’échéance ».
- **Etat SLA** : Indicateur de suivi du ticket. Il s’agit d’un champ automatique, calculé en fonction de la SLA
  configurée dans GLPI. Il peut prendre les valeurs suivantes :
    - « En retard » si le ticket n’est pas fermé et que la Date SLA est dépassée.
    - « A faire » si le ticket n’est pas fermé et que 75% de la durée de l’engagement est passée.

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/8686e9726f2cd048c830be44c9995cba.png)

Au niveau de l’enchaînement des tickets, si le suivant n’est pas encore créé, la ligne apparait en gris. Si l’état SLA est « En retard », la ligne apparait en rouge.

Si l’état SLA est « A faire », la ligne apparait en jaune.

# Configuration du plugin

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/3e12202fb053452c6555d04e7c27f249.png)

Le plugin peut être configuré en cliquant sur l’icône

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/8ac353c6c38c03bea53384f481ada295.png)

Plusieurs éléments peuvent être configurés :

- **Activer la transformation d'un ticket en méta-demande** :

Depuis l'interface complète ou simplifiée, lors de la sélection de la catégorie sur le formulaire de création de ticket, l'utilisateur sera redirigé automatiquement vers la métademande correspondante.

- **Activer l'affichage des métademandes via un icône**

Permet de choisir l'affichage des méta-demandes : sous forme de liste ou via un icône

- **Balise du ticket père**

Une balise s’affiche au début du titre du ticket père de la méta-demande, pour qu’il soit reconnaissable dans la liste de tickets.

- **Balise du ticket enfant**

Une balise s’affiche au début du titre du ticket fils de la méta-demande, pour qu’il soit reconnaissable dans la liste de tickets.

- **Voir les informations du demandeur dans le formulaire**

Cette option permet de voir les informations du demandeur lors de la saisie du formulaire de la méta-demande.

- **Générer un PDF**

Cette option active la génération d'un PDF au moment de la création du ticket père.

- **Tickets enfants récupèrent les informations du ticket parent**

Cette option permet d'envoyer dans les tickets enfants générés la description du ticket père (On peut d'ailleurs sélectionner les blocs à afficher).

- **Montrer la liste des méta-demande dans le plugin ServiceCatalog**

Cette option permet d'afficher dans le plugin [Service Catalog](https://github.com/InfotelGLPI/servicecatalog) la liste des méta-demandes disponibles.

![](https://raw.githubusercontent.com/InfotelGLPI/metademands/master/wiki/servicecatalog.png)
