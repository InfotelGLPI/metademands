# Guide utilisateur — Plugin GLPI Metademands

## 1. Présentation

Le plugin **Metademands** est un constructeur de formulaires complexes pour GLPI. Il permet aux administrateurs de définir des formulaires structurés ("méta-demandes") qui, lorsqu'ils sont soumis par les utilisateurs via un assistant, créent automatiquement un ou plusieurs tickets GLPI (ou problèmes, changements), accompagnés de tickets enfants et de tâches dans un workflow défini.

**Concepts clés :**

- Une **Méta-demande** est un modèle de formulaire associé à une ou plusieurs catégories ITIL. Elle contient des champs, des tâches, des conditions et une configuration.
- Les **Champs** sont les questions du formulaire présentées à l'utilisateur (textes, listes déroulantes, cases à cocher, dates, uploads, etc.).
- Les **Tâches** définissent ce qui est créé à la soumission : un ticket, une sous-méta-demande, une tâche sur le ticket parent, ou un e-mail.
- L'**Assistant** (Wizard) est l'interface utilisateur pour sélectionner et remplir une méta-demande.
- Le mode **Pas-à-pas** permet de remplir un formulaire progressivement par différents groupes/utilisateurs en séquence, avec notifications.
- Les **Conditions** contrôlent l'affichage ou le masquage de champs selon les valeurs saisies ailleurs dans le formulaire.
- Les **Options de champ** (FieldOptions) attachent des actions à des valeurs spécifiques : déclencher une tâche, afficher/masquer un bloc, exiger un valideur.
- Les **Brouillons** permettent de sauvegarder un formulaire partiellement rempli pour le reprendre plus tard.
- Le mode **Panier** (Basket) permet des commandes quantitatives à partir d'un catalogue de référence.

---

## 2. Gestion des droits

Chemin : `Administration > Profils > onglet Meta-Demands`

### 2.1 Droits CRUD (matrice standard)

| Droit | Description |
|-------|-------------|
| `plugin_metademands` | Accès principal : voir, créer, modifier, supprimer des méta-demandes + accès à l'assistant |
| `plugin_metademands_followup` | Gestion des suivis inter-tickets |

### 2.2 Droits binaires (activé/désactivé)

| Droit | Description |
|-------|-------------|
| `plugin_metademands_createmeta` | Utiliser l'assistant pour soumettre une méta-demande |
| `plugin_metademands_validatemeta` | Approuver/refuser les validations de méta-demandes en attente |
| `plugin_metademands_fillform` | Remplir une étape d'un formulaire pas-à-pas |
| `plugin_metademands_cancelform` | Annuler ou supprimer un formulaire soumis |
| `plugin_metademands_publicforms` | Marquer un formulaire comme public |
| `plugin_metademands_updatemeta` | Modifier les valeurs d'un formulaire depuis la fiche ticket |
| `plugin_metademands_on_login` | Rediriger automatiquement vers l'assistant à la connexion (interface helpdesk) |
| `plugin_metademands_in_menu` | Masquer le bouton dans le menu helpdesk |

---

## 3. Configuration globale

Chemin : `Configuration > Meta-Demands > Configuration` (requiert le droit `config` UPDATE)

### 3.1 Options principales

| Paramètre | Description |
|-----------|-------------|
| Redirection ticket simple → méta-demande | Redirige automatiquement vers l'assistant quand la catégorie ITIL d'un ticket correspond à une méta-demande |
| Préfixe ticket parent | Texte ajouté en préfixe au titre du ticket parent |
| Préfixe ticket enfant | Texte ajouté en préfixe au titre des tickets enfants |
| Contenu parent dans les enfants | Les tickets enfants héritent du contenu du ticket parent |
| Créer un PDF | Génère automatiquement un récapitulatif PDF à la soumission |
| Utiliser les brouillons | Active la fonctionnalité de sauvegarde de brouillon |
| Mode d'affichage | Afficher les méta-demandes sous forme de tuiles icônes (au lieu d'une liste) |
| Langue technicien | Forcer une langue spécifique pour les notifications côté technicien |
| Voir le top des méta-demandes | Afficher une section "top méta-demandes" |
| Icône type Incident | Icône personnalisée pour les méta-demandes de type Incident |
| Icône type Demande | Icône personnalisée pour les méta-demandes de type Demande |
| Icône type Problème | Icône personnalisée pour les méta-demandes de type Problème |
| Icône type Changement | Icône personnalisée pour les méta-demandes de type Changement |
| Groupes par regex | Activer l'ajout de groupes par expression régulière |

### 3.2 Options Catalogue de Services

| Paramètre | Description |
|-----------|-------------|
| Afficher la liste dans le Service Catalog | Montrer les méta-demandes dans le widget ServiceCatalog |
| Titre widget Service Catalog | Titre affiché dans le widget |
| Commentaire widget Service Catalog | Description affichée dans le widget |
| Icône widget Service Catalog | Icône Tabler pour le widget |

### 3.3 Onglets de la configuration

| Onglet | Contenu |
|--------|---------|
| **Principal** | Options ci-dessus |
| **Outils** | Actions de maintenance administrative |
| **Vérification du schéma** | Contrôle d'intégrité des tables de la base de données |

---

## 4. Structure d'une méta-demande

### 4.1 Propriétés générales

| Champ | Description |
|-------|-------------|
| Nom | Nom du formulaire affiché dans l'assistant |
| Commentaire | Description courte (visible dans l'assistant) |
| Description | Description longue (texte riche) |
| Entité / Récursif | Périmètre entité |
| Actif | Disponible pour les utilisateurs |
| Mode maintenance | Désactive temporairement le formulaire |
| Objet à créer | `Ticket`, `Problème` ou `Changement` |
| Type | Filtre type de demande (Incident, Demande, etc.) |
| Catégories ITIL | Catégories liées (tableau JSON, plusieurs possibles) |
| Catégorie de formulaire | Regroupement dans le catalogue de services GLPI 11 |
| Icône | Classe d'icône Tabler pour l'affichage |
| Illustration | Image pour le catalogue de services |
| Épinglé | Afficher en tête de liste dans l'assistant |
| Modèle | Marquer comme modèle (non visible aux utilisateurs) |

### 4.2 Options avancées

| Option | Description |
|--------|-------------|
| Mode pas-à-pas | Remplissage séquentiel par groupes différents |
| Créer un seul ticket | Créer un ticket parent unique (vs. un par tâche) |
| Forcer la création de tâches | Créer des tâches GLPI plutôt que des tickets enfants |
| Validation avant tickets enfants | Exiger une approbation avant la création des tickets enfants |
| Autoriser la mise à jour | Permettre aux utilisateurs de modifier les valeurs après soumission |
| Autoriser le clonage | Permettre de cloner/re-soumettre le formulaire |
| Masquer les blocs vides | Cacher les blocs sans champs visibles |
| Masquer le titre | Ne pas afficher le titre de la méta-demande dans l'assistant |
| Couleurs | Personnalisation visuelle (titre, fond) |
| Afficher les règles | Montrer les règles de conditions aux utilisateurs |
| Demandeur initial dans les enfants | Copier le demandeur vers les tickets enfants |
| Mode panier | Activer le mode commande/panier |
| Étape de confirmation | Afficher une étape de confirmation avant soumission finale |

### 4.3 Onglets de la fiche méta-demande

| Onglet | Contenu |
|--------|---------|
| **Principal** | Propriétés générales et options avancées |
| **Champs** | Gestion des champs du formulaire |
| **Aperçu de l'assistant** | Prévisualisation du formulaire |
| **Configuration pas-à-pas** | Paramètres du mode pas-à-pas (si activé) |
| **Blocs pas-à-pas** | Assignation des blocs aux groupes (si activé) |
| **Champs ticket** | Champs GLPI prédéfinis/obligatoires pour le ticket parent |
| **Traductions** | Noms et descriptions multilingues |
| **Création de tâches** | Définir les tickets enfants/tâches/e-mails |
| **Droits groupes** | Restreindre la visibilité aux groupes autorisés |
| **Affichages conditionnels** | Règles de conditions sur les champs |
| **Export** | Export XML du formulaire |
| **Journal** | Historique des modifications (interface centrale) |

---

## 5. Types de champs

### 5.1 Champs d'affichage (sans saisie)

| Type | Description |
|------|-------------|
| `title` | Titre de section (affichage uniquement) |
| `title-block` | Titre de bloc |
| `informations` | Panneau d'information/notice |

### 5.2 Champs texte

| Type | Description |
|------|-------------|
| `text` | Texte sur une ligne. Supporte la validation par regex. Peut être auto-rempli depuis le profil utilisateur (téléphone, mobile, matricule). |
| `textarea` | Texte multi-lignes. Supporte le texte enrichi (TinyMCE). |
| `tel` | Numéro de téléphone |
| `email` | Adresse e-mail |
| `url` | URL |

### 5.3 Champs de choix

| Type | Description |
|------|-------------|
| `yesno` | Bouton Oui/Non |
| `checkbox` | Cases à cocher multiples (valeurs personnalisées). Peut déclencher des tâches ou afficher/masquer des blocs par valeur. |
| `radio` | Boutons radio (sélection unique, valeurs personnalisées). |

### 5.4 Listes déroulantes

| Type | Description |
|------|-------------|
| `dropdown` | Liste déroulante depuis une table GLPI (Localisation, Catégorie utilisateur, etc.). Modes : classique, séparé, bloc. |
| `dropdown_object` | Sélecteur d'objet GLPI (Utilisateur, Groupe, actif GLPI). Auto-remplissage des infos demandeur si Utilisateur sélectionné. |
| `dropdown_meta` | Liste déroulante avec valeurs personnalisées définies par l'admin. Supporte les options de champ. |
| `dropdown_multiple` | Sélection multiple avec valeurs personnalisées. |
| `dropdown_ldap` | Liste déroulante alimentée par une requête LDAP (serveur, attribut et filtre configurables). |
| `parent_field` | Hérite des valeurs d'un champ de la méta-demande parente. |

### 5.5 Champs numériques

| Type | Description |
|------|-------------|
| `number` | Champ numérique |
| `range` | Curseur de plage (slider) |

### 5.6 Champs date/heure

| Type | Description |
|------|-------------|
| `date` | Sélecteur de date. Options : dates futures uniquement, date du jour par défaut, décalage de N jours. |
| `time` | Sélecteur d'heure |
| `datetime` | Sélecteur date + heure |
| `date_interval` | Deux sélecteurs de date (intervalle début/fin) |
| `datetime_interval` | Deux sélecteurs date+heure |

### 5.7 Champs fichier/média

| Type | Description |
|------|-------------|
| `upload` | Upload de fichier. Nombre maximum de fichiers configurable. |
| `signature` | Pavé de signature (canvas). Sauvegardé comme image. |
| `link` | Lien hypertexte cliquable (affichage uniquement, URL configurable) |

### 5.8 Types spéciaux

| Type | Description |
|------|-------------|
| `basket` | Panier d'articles. Référence le catalogue `Basketobject`. Permet la sélection de quantités. |
| `freetable` | Tableau libre avec colonnes configurables. Les utilisateurs ajoutent des lignes dynamiquement. |

### 5.9 Objets spéciaux disponibles dans `dropdown_object`

- `urgency` — Urgence du ticket
- `impact` — Impact du ticket
- `priority` — Priorité du ticket
- `mydevices` — Équipements de l'utilisateur

---

## 6. Configuration d'un champ

### 6.1 Paramètres de base

| Paramètre | Description |
|-----------|-------------|
| Libellé | Texte affiché à côté du champ |
| Obligatoire | Le formulaire ne peut pas être soumis si le champ est vide |
| Regex | Expression régulière de validation |
| Affichage pleine largeur | Le champ occupe toute la largeur du bloc |
| Couleur | Couleur d'affichage |
| Icône | Icône associée |
| Lecture seule | Valeur affichée mais non modifiable |
| Masqué | Champ invisible (valeur transmise en arrière-plan) |

### 6.2 Paramètres avancés

| Paramètre | Description |
|-----------|-------------|
| Valeur par défaut | Valeur pré-remplie à l'ouverture du formulaire |
| Auto-remplissage depuis le demandeur | Pré-remplir depuis le profil GLPI du demandeur (téléphone, mobile, etc.) |
| Auto-remplissage depuis le superviseur | Pré-remplir depuis le profil du superviseur du demandeur |
| Mapper vers ticket parent | La valeur du champ alimente un champ du ticket GLPI parent |
| Mapper vers ticket enfant | La valeur du champ alimente un champ du ticket enfant |
| Lier à un champ Utilisateur | Pré-remplir depuis l'utilisateur sélectionné dans un autre champ |
| Dates futures uniquement | (champ date) Interdire les dates passées |
| Utiliser la date du jour | (champ date) Utiliser aujourd'hui comme valeur par défaut |
| Décalage en jours | (champ date) Ajouter N jours à la date par défaut |
| Texte enrichi | (textarea) Activer l'éditeur TinyMCE |
| Max fichiers | (upload) Nombre maximum de fichiers autorisés |
| Mode affichage | (dropdown) Classique, séparé ou bloc |
| Serveur LDAP / Attribut / Filtre | (ldapdropdown) Configuration de la requête LDAP |
| Racine | (dropdown Location) Nœud racine de l'arborescence |

---

## 7. Affichage conditionnel

Les conditions permettent d'afficher ou masquer des champs selon les valeurs saisies ailleurs dans le formulaire.

### 7.1 Visibilité initiale d'un champ

| Mode | Description |
|------|-------------|
| Toujours visible | Le champ est toujours affiché |
| Masqué par défaut | Le champ n'est affiché que si une condition est vraie |
| Visible par défaut | Le champ est affiché sauf si une condition le masque |

### 7.2 Configuration d'une condition

| Paramètre | Description |
|-----------|-------------|
| Champ contrôlé | Le champ dont la visibilité est affectée |
| Champ déclencheur | Le champ dont la valeur est évaluée |
| Valeur de vérification | La valeur à comparer |
| Opérateur | EQ (=), NE (≠), LT (<), GT (>), LE (≤), GE (≥), REGEX, VIDE, NON VIDE |
| Logique | ET / OU (pour combiner plusieurs conditions) |
| Ordre | Ordre d'évaluation des conditions |

Les types de champs pouvant servir de déclencheurs : `dropdown`, `dropdown_object`, `dropdown_meta`, `dropdown_multiple`, `dropdown_ldap`, `text`, `tel`, `email`, `url`, `checkbox`, `textarea`, `date`, `datetime`, `number`, `range`, `yesno`, `radio`.

---

## 8. Options de champ (FieldOptions)

Les options de champ permettent d'attacher des actions à des valeurs spécifiques d'un champ (liste déroulante, case à cocher, radio, oui/non).

Pour chaque valeur d'un champ, il est possible de configurer :

| Action | Description |
|--------|-------------|
| Tâche liée | Déclencher une tâche spécifique quand cette valeur est sélectionnée |
| Afficher/masquer un champ | Contrôler la visibilité d'un autre champ |
| Afficher/masquer un bloc | Contrôler la visibilité d'un bloc entier |
| Valideur | Affecter un utilisateur valideur spécifique quand cette valeur est sélectionnée |
| Blocs enfants | JSON des blocs à afficher comme enfants |

---

## 9. Tâches et workflow de création de tickets

### 9.1 Types de tâches

| Type | Description |
|------|-------------|
| **Ticket** | Créer un ticket enfant dans GLPI |
| **Sous-méta-demande** | Déclencher une autre méta-demande |
| **Tâche** | Créer une tâche sur le ticket parent |
| **E-mail** | Envoyer un e-mail à des destinataires configurés |

### 9.2 Configuration d'une tâche ticket

Chaque tâche ticket est associée à un gabarit de ticket enfant (`TicketTask`) contenant :

| Champ | Description |
|-------|-------------|
| Catégorie | Catégorie ITIL du ticket enfant |
| Contenu | Description du ticket enfant |
| Technicien assigné | Technicien par défaut |
| Groupe assigné | Groupe par défaut |
| Demandeur | Demandeur du ticket enfant |
| Observateur | Observateur |
| Statut | Statut initial |
| Type de demande | Incident / Demande |
| Formaté comme tableau | Le contenu est présenté sous forme de tableau HTML |

### 9.3 Contrôle par blocs

Chaque tâche dispose d'un tableau `block_use` (JSON) listant les blocs du formulaire dont la saisie déclenche cette tâche. Si l'utilisateur n'a rempli aucun de ces blocs, la tâche n'est pas créée.

### 9.4 Hiérarchie des tâches

Les tâches sont organisées en arborescence (parent-enfant). Le niveau (`level`) détermine l'ordre de création :
- Les tâches de niveau 1 sont créées à la soumission du formulaire.
- Les tâches de niveau suivant sont créées lorsque le ticket parent est résolu/clôturé (`addSonTickets()`).

### 9.5 Bloquer la clôture du ticket parent

L'option `block_parent_ticket_resolution` sur une tâche empêche la fermeture du ticket parent tant que cette tâche/ce ticket enfant n'est pas résolu.

---

## 10. Processus de soumission (assistant)

### Étape 1 — Sélection

L'utilisateur voit la liste des méta-demandes disponibles (ou la grille de tuiles si `display_type=1`). Les méta-demandes sont filtrées par :
- Entité active de l'utilisateur
- Droits de groupe (si des groupes sont configurés sur la méta-demande)
- Statut actif / mode maintenance

La liste peut afficher les méta-demandes les plus utilisées ("top") et les méta-demandes épinglées. Une recherche par texte est disponible.

### Étape 2 — Remplissage du formulaire

L'utilisateur remplit les champs du formulaire, organisés en blocs numérotés et en rangées.

- Les champs conditionnels s'affichent/se masquent dynamiquement en Ajax.
- Les blocs peuvent être masqués si aucun champ n'est visible (`hide_no_field=1`).
- L'utilisateur peut sauvegarder un brouillon à tout moment (`use_draft=1`).

### Étape 3 — Confirmation (optionelle)

Si `use_confirm=1`, une page de récapitulatif est affichée avant la soumission finale.

### Étape 4 — Soumission

1. Le **ticket parent** est créé avec le gabarit configuré. Le titre est préfixé par `parent_ticket_tag`. Le contenu inclut un tableau récapitulatif de tous les champs.
2. Si `validation_subticket=1` : une demande de validation est créée. Les tickets enfants ne sont pas créés tant que le valideur n'a pas approuvé.
3. Les **tickets enfants** sont créés selon les tâches définies et les blocs remplis.
4. Si `create_pdf=1` : un PDF récapitulatif est généré et joint au ticket parent.

### Redirection automatique

Si `plugin_metademands_on_login=1` (droit) : les utilisateurs de l'interface helpdesk sont redirigés directement vers l'assistant à la connexion.

Si `simpleticket_to_metademand=1` (config) : la création d'un ticket standard avec une catégorie liée à une méta-demande redirige vers l'assistant.

---

## 11. Mode pas-à-pas

### Principe

Le mode pas-à-pas (`step_by_step_mode=1`) divise un formulaire en blocs séquentiels remplis par différents groupes. Chaque groupe reçoit une notification lorsque c'est son tour.

### Configuration des blocs

Chemin : Onglet **Blocs pas-à-pas** de la méta-demande

Pour chaque bloc numéroté :
- **Groupe assigné** : groupe habilité à remplir ce bloc
- **Superviseur uniquement** : restreindre au superviseur du groupe
- **Délai de rappel** : délai avant envoi d'une notification de rappel
- **Message** : message personnalisé pour la notification

### Options Configstep

| Option | Description |
|--------|-------------|
| Voir les blocs comme onglets | Afficher chaque bloc comme un onglet séparé |
| Lier utilisateur-bloc | Associer un utilisateur à son bloc |
| Groupes multiples par bloc | Permettre plusieurs associations groupe-bloc |
| Ajouter l'utilisateur comme demandeur | Ajouter l'utilisateur qui remplit le bloc comme demandeur du ticket |
| Validation superviseur | Exiger la validation du superviseur par étape |
| Interface pas-à-pas | Restreindre à : helpdesk (1), centrale (2), ou les deux (0) |
| Modifier les options | Permettre de changer les options au moment du remplissage |

### Flux

1. Le formulaire est soumis (ticket parent créé).
2. Une notification est envoyée au groupe du bloc 1.
3. Le groupe 1 remplit son bloc et valide.
4. Une notification est envoyée au groupe 2, et ainsi de suite.
5. Une fois tous les blocs remplis, les tickets enfants sont créés.

---

## 12. Validation avant création des tickets enfants

Quand `validation_subticket=1` sur la méta-demande :

1. Après soumission, le ticket parent est créé mais les tickets enfants ne le sont **pas encore**.
2. Un enregistrement `MetademandValidation` est créé avec le statut **À valider**.
3. Le valideur désigné voit une action de validation dans la chronologie du ticket.
4. **Sur approbation** : les tickets enfants sont créés.
5. **Sur refus** : la méta-demande peut être annulée.

Le valideur peut être :
- Configuré globalement sur la méta-demande.
- Déterminé dynamiquement via une **FieldOption** (valeur de champ → valideur spécifique).

---

## 13. Suivi des statuts

### Statuts d'une méta-demande (sur le ticket)

| Statut | Description |
|--------|-------------|
| **En cours** | Des tickets enfants sont encore ouverts |
| **À clôturer** | Tous les enfants sont résolus, en attente de clôture |
| **Clôturé** | Entièrement fermé |

### Champs de recherche ajoutés dans les tickets

| ID | Champ | Description |
|----|-------|-------------|
| 9499 | Approbateur méta-demande | Utilisateur valideur de la MetademandValidation |
| 9500 | Statut méta-demande | En cours / À clôturer / Clôturé |
| 9501 | Statut de validation | À valider / Tickets créés / etc. |
| 9502 | Groupe ticket enfant | Groupe assigné au ticket enfant |
| 9503 | Lien vers méta-demandes | Nombre de tickets enfants liés |
| 9504 | Technicien ticket enfant | Technicien assigné au ticket enfant |

### Action automatique

| Action | Fréquence | Description |
|--------|-----------|-------------|
| `MetademandsGlobalStatus` | Quotidienne | Vérifie et met à jour le statut global de toutes les méta-demandes actives. Clôture celles dont tous les enfants sont résolus. |

---

## 14. Notifications

### Suivi inter-tickets

| Événement | Description |
|-----------|-------------|
| `add_interticketfollowup` | Déclenché lors de l'ajout d'un suivi inter-tickets (lien entre deux tickets dans la chronologie) |

### Formulaires pas-à-pas

| Événement | Description |
|-----------|-------------|
| `new_step_form` | Nouveau formulaire complété (étape pas-à-pas) |
| `update_step_form` | Formulaire mis à jour (étape pas-à-pas) |

### Variables disponibles dans les modèles de notification (pas-à-pas)

| Variable | Description |
|----------|-------------|
| `##pluginmetademandsmetademand.title##` | Nom de la méta-demande |
| `##pluginmetademandsstepform.date##` | Date de complétion |
| `##pluginmetademandsstepform.user_editor##` | Utilisateur ayant rempli l'étape |
| `##pluginmetademandsstepform.nextgroup##` | Prochain groupe à remplir |
| `##pluginmetademandsstepform.users_id_dest##` | Utilisateur(s) destinataire(s) |

### Widgets de tableau de bord (GLPI native)

Quatre widgets de comptage disponibles pour les tableaux de bord GLPI :

| Widget | Description |
|--------|-------------|
| Méta-demandes en cours | Nombre de méta-demandes au statut « En cours » |
| Méta-demandes à clôturer | Nombre au statut « À clôturer » |
| Méta-demandes à valider | Nombre en attente de validation |
| En cours (groupes de l'utilisateur) | Méta-demandes en cours filtrées sur les groupes de l'utilisateur courant |

---

## 15. Droits d'accès par groupe

Chemin : Onglet **Droits groupes** de la méta-demande

Si des groupes sont configurés sur une méta-demande, seuls les utilisateurs appartenant à l'un de ces groupes peuvent voir et utiliser ce formulaire dans l'assistant.

Sans restriction de groupe, le formulaire est accessible à tous les utilisateurs ayant le droit `plugin_metademands_createmeta`.

---

## 16. Champs ticket prédéfinis

Chemin : Onglet **Champs ticket** de la méta-demande

Permet de définir les champs GLPI du ticket parent qui seront :
- **Prédéfinis** : valeur fixe pré-remplie
- **Obligatoires** : l'utilisateur doit obligatoirement les renseigner

Ces champs se synchronisent avec les gabarits de tickets GLPI via le hook `tickettemplate`.

---

## 17. Export / Import

### Export XML

Chemin : Onglet **Export** de la méta-demande → `Outils > Importer des méta-demandes`

Exporte l'intégralité d'une méta-demande en fichier XML, comprenant :
- Tous les champs et leurs paramètres
- Toutes les tâches et gabarits de tickets
- Toutes les conditions
- Les options de champ
- La configuration générale

### Import XML

Chemin : `Configuration > Meta-Demands > Importer des méta-demandes`

Importe une méta-demande depuis un fichier XML exporté précédemment.

### Conversion depuis un formulaire GLPI 11

L'onglet **Export** est également disponible sur les formulaires GLPI 11 natifs. Il permet de convertir un formulaire GLPI 11 en méta-demande, en mappant les types de questions vers les types de champs correspondants.

### Import de valeurs personnalisées

Chemin : `front/importcustomvalues.php`

Import en masse de valeurs personnalisées pour les listes déroulantes (`dropdown_meta`).

---

## 18. Mode Panier (Basket)

Activé via `is_basket=1` sur la méta-demande.

### Concept

Le mode panier permet aux utilisateurs de commander des articles depuis un catalogue de référence avec des quantités. Le champ de type `basket` dans le formulaire affiche le catalogue et permet d'ajouter des articles.

### Catalogue de référence (Basketobject)

Chemin : `Gestion > Catalogue de référence`

| Champ | Description |
|-------|-------------|
| Nom | Nom de l'article |
| Description | Description |
| Référence | Identifiant unique |
| Type | Catégorie de l'article (Basketobjecttype) |

Les types d'articles (catégories) sont configurables dans `Configuration > Intitulés > My Dashboard > Types d'articles`.

---

## 19. Brouillons

Activé via `use_draft=1` dans la configuration.

- Les utilisateurs peuvent sauvegarder un formulaire partiellement rempli.
- Les brouillons sont privés à l'utilisateur créateur.
- Accessibles dans **Mes méta-demandes** → section Brouillons.
- Deux modes : formulaire standard et mode panier.

---

## 20. Traductions

### Traductions de la méta-demande

Chemin : Onglet **Traductions** de la méta-demande

Nom, commentaire et description peuvent être traduits dans plusieurs langues. La langue active de l'utilisateur GLPI détermine quelle traduction est affichée.

### Traductions des champs

Chaque champ peut avoir son libellé et son info-bulle traduits indépendamment.

### Traductions des articles du catalogue

Les `Basketobject` et `Basketobjecttype` supportent également les traductions.

---

## 21. Intégrations avec d'autres plugins

### Plugin Resources

- Lien entre une méta-demande et un **Type de contrat** Resources.
- Lorsqu'une ressource est consultée, l'utilisateur est redirigé vers la méta-demande associée à son type de contrat.

### Plugin ServiceCatalog (legacy)

- Les méta-demandes apparaissent dans le widget ServiceCatalog.
- Titre, commentaire et icône configurables dans la configuration du plugin.

### Plugin Fields

- Un champ de méta-demande peut être lié à un champ personnalisé créé par le plugin Fields.
- Configuration via les paramètres avancés du champ.

### Plugin DataInjection

- Import d'articles du catalogue (Basketobject) via le plugin DataInjection.


### GLPI 11 — Catalogue de services natif

- Les méta-demandes s'affichent dans le catalogue de services natif GLPI 11 comme des éléments de formulaire.
- Filtrage par catégorie, entité, accès groupe, recherche textuelle.

### GLPI 11 — Tuile helpdesk

- Une tuile configurable sur la page helpdesk GLPI 11 renvoie vers l'assistant méta-demandes.
- Titre, description, illustration et traductions configurables.

---

## 22. Suivi inter-tickets

Le suivi inter-tickets (`Interticketfollowup`) est un type de suivi qui crée un lien entre deux tickets dans la chronologie.

- Accessible depuis la chronologie d'un ticket (actions de suivi).
- Génère une notification "Nouveau suivi inter-tickets".
- Requiert le droit `plugin_metademands_followup`.

---

## 23. Bonnes pratiques

- **Organiser les formulaires par catégories ITIL** pour que le moteur de redirection (`simpleticket_to_metademand`) puisse orienter automatiquement les utilisateurs vers le bon formulaire
- **Utiliser les conditions d'affichage** pour simplifier les formulaires longs : n'afficher que les champs pertinents selon les choix de l'utilisateur
- **Définir des droits de groupe** sur les formulaires sensibles pour limiter leur accès aux équipes concernées
- **Activer la validation avant tickets enfants** (`validation_subticket=1`) pour les processus nécessitant une approbation intermédiaire (changements, accès sensibles)
- **Nommer les tâches clairement** et configurer le `block_use` avec précision pour éviter la création de tickets non pertinents
- **Utiliser le mode pas-à-pas** pour les processus impliquant plusieurs équipes (onboarding, demandes multi-services)
- **Activer les brouillons** pour les formulaires complexes afin d'éviter que les utilisateurs perdent leur saisie
- **Configurer les préfixes** (`parent_ticket_tag`, `son_ticket_tag`) pour identifier visuellement les tickets issus de méta-demandes dans les files d'attente
- **Surveiller l'action automatique** `MetademandsGlobalStatus` dans `Configuration > Actions automatiques` pour s'assurer qu'elle s'exécute bien quotidiennement
- **Exporter régulièrement** les méta-demandes importantes en XML comme sauvegarde avant toute modification majeure
