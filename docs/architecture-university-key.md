# University Key - Blueprint Technique

## 1. Architecture complete du projet

University Key est une plateforme web d'orientation post-baccalaureat pour le Cameroun. L'architecture vise une separation claire entre l'interface React et l'API Laravel.

```text
Navigateur
  -> React + Vite + Tailwind CSS
  -> API HTTPS Laravel
  -> PostgreSQL
  -> Stockage fichiers justificatifs
  -> Services mail/SMS
```

### Frontend

- React + Vite pour une SPA rapide.
- Tailwind CSS pour un design mobile-first et coherent.
- Interface bilingue FR/EN des le depart.
- Accessibilite de base: navigation clavier, focus visible, libelles de formulaire, contrastes lisibles.
- Pages publiques pour visiteurs, parents et bacheliers.
- Espaces connectes pour etudiants, conseillers et administrateurs.

### Backend

- Laravel expose une API REST versionnee sous `/api/v1`.
- PostgreSQL stocke les donnees metier.
- Authentification JWT pour les clients web et mobiles.
- Hash natif Laravel pour les mots de passe.
- Protection CSRF pour les routes web et protections XSS/SQL injection via validation, Eloquent et echappement frontend.
- Journalisation des actions sensibles dans `logs_admin` et logs applicatifs Laravel.

### Deploiement

- VPS Linux.
- Nginx comme reverse proxy et serveur statique.
- PHP-FPM pour executer Laravel.
- PostgreSQL local ou serveur separe.
- SSL Let's Encrypt avec renouvellement automatique.
- Sauvegardes automatisees de PostgreSQL et des fichiers uploades.

> Note: Gunicorn est un serveur Python. Pour Laravel, l'equivalent adapte est PHP-FPM derriere Nginx.

## 2. Modele de base de donnees

### Identite et roles

| Table | Role |
| --- | --- |
| `users` | Comptes communs: email, mot de passe, role, statut, telephone, langue. |
| `profils_etudiants` | Profil scolaire, bac, preferences, budget, centres d'interet. |
| `profils_conseillers` | Experience, specialite, documents justificatifs, disponibilites. |
| `validations_conseillers` | Processus de validation manuelle par l'admin. |
| `codes_verification` | Codes OTP email/telephone avec expiration et tentatives. |

### Orientation et catalogue

| Table | Role |
| --- | --- |
| `etablissements` | Ecoles, universites, instituts, centres de formation. |
| `filieres` | Formations disponibles, niveaux, debouches et competences. |
| `etablissement_filiere` | Relation plusieurs-a-plusieurs entre etablissements et filieres. |
| `metiers` | Metiers cibles lies aux filieres. |
| `opportunites` | Concours, bourses, admissions, evenements importants. |

### Tests d'orientation

| Table | Role |
| --- | --- |
| `tests_orientation` | Tests disponibles par version/langue/statut. |
| `questions` | Questions rattachees a un test. |
| `choix_reponses` | Choix possibles pour chaque question. |
| `poids_filieres` | Poids d'un choix sur une filiere ou domaine. |
| `sessions_test` | Session de test d'un etudiant. |
| `reponses_etudiants` | Reponses donnees pendant une session. |
| `recommandations` | Resultats calcules: filieres, ecoles, scores, explications. |

### Communication et moderation

| Table | Role |
| --- | --- |
| `conversations` | Conversation etudiant-conseiller ou support. |
| `messages` | Messages texte/fichiers dans une conversation. |
| `evaluations_conseillers` | Notes et avis apres accompagnement. |
| `presence_users` | Statut en ligne et derniere activite. |
| `signalements` | Signalements de contenu ou comportement. |
| `notifications` | Notifications internes. |

### Administration et conformite

| Table | Role |
| --- | --- |
| `favoris` | Ecoles, filieres ou metiers sauvegardes par l'utilisateur. |
| `documents_legaux` | Conditions, confidentialite, mentions legales par langue/version. |
| `stats_conseillers` | Statistiques agregees par conseiller. |
| `logs_admin` | Audit des actions sensibles faites par les administrateurs. |

## 3. Routes API principales

Toutes les routes metier doivent etre exposees sous `/api/v1`.

### Authentification

```text
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me
POST   /api/v1/auth/verify-email
POST   /api/v1/auth/verify-phone
POST   /api/v1/auth/resend-code
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
```

### Catalogue public

```text
GET    /api/v1/etablissements
GET    /api/v1/etablissements/{id}
GET    /api/v1/filieres
GET    /api/v1/filieres/{id}
GET    /api/v1/metiers
GET    /api/v1/opportunites
GET    /api/v1/search
```

### Etudiant

```text
GET    /api/v1/student/profile
PUT    /api/v1/student/profile
POST   /api/v1/student/tests/{test}/start
POST   /api/v1/student/test-sessions/{session}/answers
POST   /api/v1/student/test-sessions/{session}/complete
GET    /api/v1/student/recommendations
POST   /api/v1/student/favoris
DELETE /api/v1/student/favoris/{id}
```

### Conseiller

```text
GET    /api/v1/counselor/profile
PUT    /api/v1/counselor/profile
POST   /api/v1/counselor/validation-request
POST   /api/v1/counselor/documents
GET    /api/v1/counselor/conversations
GET    /api/v1/counselor/stats
```

### Messagerie

```text
GET    /api/v1/conversations
POST   /api/v1/conversations
GET    /api/v1/conversations/{id}/messages
POST   /api/v1/conversations/{id}/messages
POST   /api/v1/signalements
```

### Administration

```text
GET    /api/v1/admin/dashboard
GET    /api/v1/admin/users
PATCH  /api/v1/admin/users/{id}/status
GET    /api/v1/admin/counselor-validations
PATCH  /api/v1/admin/counselor-validations/{id}/approve
PATCH  /api/v1/admin/counselor-validations/{id}/reject
CRUD   /api/v1/admin/etablissements
CRUD   /api/v1/admin/filieres
CRUD   /api/v1/admin/tests-orientation
GET    /api/v1/admin/messages
GET    /api/v1/admin/stats
GET    /api/v1/admin/logs
CRUD   /api/v1/admin/documents-legaux
```

## 4. Structure des dossiers

### Frontend

```text
frontend/
  src/
    app/
      App.jsx
      routes.js
    components/
      layout/
      ui/
      cards/
    data/
      mockData.js
    i18n/
      copy.js
    pages/
      public/
        HomePage.jsx
        ProgramsPage.jsx
        SchoolsPage.jsx
        CounselorsPage.jsx
        LegalPage.jsx
      auth/
        LoginPage.jsx
        RegisterPage.jsx
        VerifyAccountPage.jsx
      student/
        StudentDashboardPage.jsx
        OrientationTestPage.jsx
        RecommendationsPage.jsx
      counselor/
        CounselorDashboardPage.jsx
        CounselorValidationPage.jsx
      admin/
        AdminDashboardPage.jsx
        UsersAdminPage.jsx
        ContentAdminPage.jsx
    services/
      apiClient.js
      authService.js
    styles/
      globals.css
```

### Backend

```text
backend/
  app/
    Http/
      Controllers/
        Api/V1/
          Auth/
          Public/
          Student/
          Counselor/
          Admin/
      Middleware/
      Requests/
      Resources/
    Models/
    Policies/
    Services/
      Auth/
      Orientation/
      Verification/
      Notifications/
    Observers/
  database/
    migrations/
    seeders/
    factories/
  routes/
    api.php
    web.php
  storage/
    app/private/justificatifs/
```

## 5. Premieres pages UI essentielles

### Public

- Accueil avec recherche globale, CTA test d'orientation, statistiques et mise en avant des filieres.
- Liste des filieres avec filtres: domaine, niveau, duree, budget, debouches.
- Liste des etablissements avec filtres: region, ville, type, concours, frais.
- Detail etablissement avec filieres proposees, concours, frais, contacts, conditions.
- Liste des conseillers verifies avec specialite, region, note, disponibilite.
- Pages legales: confidentialite, conditions d'utilisation, mentions legales.

### Authentification

- Inscription avec choix du role et verification email/telephone.
- Connexion JWT.
- Verification de compte par code OTP.
- Mot de passe oublie.

### Etudiant

- Tableau de bord avec profil, progression et recommandations.
- Test d'orientation.
- Resultats et recommandations expliquees.
- Favoris.
- Messagerie avec conseiller.

### Conseiller

- Tableau de bord conseiller.
- Soumission des justificatifs.
- Suivi du statut de validation.
- Conversations et disponibilites.

### Admin

- Vue d'ensemble: utilisateurs, conseillers en attente, tests, messages, logs.
- Validation des conseillers avec documents.
- Gestion des ecoles, filieres, opportunites et tests.
- Gestion des pages legales.
- Statistiques et logs d'activite.

