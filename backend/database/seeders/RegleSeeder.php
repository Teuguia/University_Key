<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegleSeeder extends Seeder
{
    /**
     * Insere les textes legaux initiaux de University Key.
     */
    public function run(): void
    {
        $conditions = <<<'TEXT'
# CONDITIONS D'UTILISATION DE UNIVERSITY KEY

Dernière mise à jour : 27 Juin 2026

## 1. Présentation de la plateforme

University Key est une plateforme numérique d'orientation académique et professionnelle destinée aux élèves, étudiants, parents, conseillers d'orientation et établissements d'enseignement.

L'utilisation de la plateforme implique l'acceptation pleine et entière des présentes Conditions d'Utilisation.

## 2. Création d'un compte

Pour accéder à certaines fonctionnalités, l'utilisateur doit créer un compte personnel.

L'utilisateur s'engage à :

* Fournir des informations exactes et à jour ;
* Maintenir la confidentialité de ses identifiants ;
* Ne pas usurper l'identité d'une autre personne ;
* Signaler toute utilisation non autorisée de son compte.

University Key se réserve le droit de suspendre ou supprimer tout compte contenant des informations frauduleuses ou trompeuses.

## 3. Utilisation des services

L'utilisateur s'engage à utiliser la plateforme de manière responsable et conforme aux lois applicables au Cameroun.

Sont notamment interdits :

* Les propos injurieux, diffamatoires ou discriminatoires ;
* La publication de fausses informations ;
* Le piratage ou toute tentative d'accès non autorisé ;
* L'utilisation commerciale non autorisée de la plateforme ;
* Le partage de contenus illégaux.

## 4. Conseillers d'orientation

Les conseillers présents sur University Key peuvent être soumis à une procédure de vérification de leurs diplômes, expériences professionnelles et qualifications.

University Key se réserve le droit :

* D'approuver ou refuser une candidature ;
* De suspendre un conseiller ;
* De retirer un badge de vérification.

## 5. Disponibilité du service

University Key s'efforce d'assurer une disponibilité continue de ses services.

Toutefois, la plateforme ne garantit pas une disponibilité ininterrompue et peut être temporairement inaccessible pour maintenance, mise à jour ou raisons techniques.

## 6. Propriété intellectuelle

Les contenus, logos, interfaces, bases de données, textes et éléments graphiques de University Key sont protégés par les lois relatives à la propriété intellectuelle.

Toute reproduction ou exploitation sans autorisation écrite est interdite.

## 7. Limitation de responsabilité

Les recommandations d'orientation fournies par University Key sont données à titre indicatif.

La décision finale concernant le choix d'une filière, d'un établissement ou d'un métier demeure sous la responsabilité de l'utilisateur.

University Key ne saurait être tenue responsable des décisions prises sur la base des recommandations fournies.

## 8. Suspension ou suppression de compte

University Key peut suspendre ou supprimer un compte en cas de :

* Violation des présentes conditions ;
* Activité frauduleuse ;
* Utilisation abusive de la plateforme ;
* Atteinte à la sécurité du système.

## 9. Modification des conditions

University Key se réserve le droit de modifier les présentes Conditions d'Utilisation à tout moment.

Les utilisateurs seront informés de toute modification importante.

## 10. Droit applicable

Les présentes Conditions d'Utilisation sont régies par les lois et règlements en vigueur en République du Cameroun.

Tout litige relèvera de la compétence des juridictions camerounaises.
TEXT;

        $politique = <<<'TEXT'
# POLITIQUE DE CONFIDENTIALITÉ DE UNIVERSITY KEY

Dernière mise à jour : 24 Juin 2026

## 1. Engagement de confidentialité

University Key accorde une importance particulière à la protection des données personnelles de ses utilisateurs.

Cette politique explique quelles informations sont collectées, pourquoi elles sont collectées et comment elles sont protégées.

## 2. Données collectées

Nous pouvons collecter les informations suivantes :

### Informations d'identification

* Nom
* Prénom
* Adresse e-mail
* Numéro de téléphone
* Date de naissance

### Informations académiques

* Type de baccalauréat
* Résultats scolaires
* Centres d'intérêt
* Parcours académique

### Informations techniques

* Adresse IP
* Navigateur utilisé
* Appareil utilisé
* Données de connexion

## 3. Finalité de la collecte

Les données collectées servent à :

* Créer et gérer votre compte ;
* Fournir des recommandations d'orientation ;
* Assurer la sécurité de la plateforme ;
* Améliorer les services proposés ;
* Communiquer avec les utilisateurs ;
* Produire des statistiques anonymisées.

## 4. Partage des données

University Key ne vend jamais les données personnelles des utilisateurs.

Les données peuvent être partagées uniquement :

* Avec les établissements partenaires lorsque l'utilisateur y consent ;
* Avec les autorités compétentes lorsque la loi l'exige ;
* Avec les prestataires techniques participant à l'hébergement ou à la maintenance de la plateforme.

## 5. Sécurité des données

University Key met en œuvre des mesures de sécurité adaptées :

* Chiffrement des communications SSL/TLS ;
* Protection contre les accès non autorisés ;
* Sauvegardes régulières ;
* Surveillance des accès ;
* Mises à jour de sécurité.

## 6. Conservation des données

Les données sont conservées uniquement pendant la durée nécessaire aux finalités pour lesquelles elles ont été collectées.

Les comptes inactifs peuvent être supprimés après une période prolongée d'inactivité conformément à la réglementation applicable.

## 7. Droits des utilisateurs

Chaque utilisateur dispose du droit :

* D'accéder à ses données ;
* De corriger ses informations ;
* De demander la suppression de son compte ;
* De retirer son consentement lorsque cela est applicable ;
* De demander des informations sur l'utilisation de ses données.

## 8. Cookies

University Key utilise des cookies et technologies similaires pour :

* Assurer le bon fonctionnement du site ;
* Améliorer l'expérience utilisateur ;
* Mesurer les performances de la plateforme.

L'utilisateur peut gérer les cookies depuis son navigateur.

## 9. Protection des mineurs

University Key accorde une attention particulière à la protection des utilisateurs mineurs.

Les parents ou représentants légaux peuvent contacter la plateforme concernant les données d'un utilisateur mineur.

## 10. Contact

Pour toute question relative à la confidentialité ou à la protection des données :

E-mail : [privacy@universitykey.cm](mailto:privacy@universitykey.cm)

Adresse : MINESEC

Téléphone : +237690232871

## 11. Modifications de la politique

University Key peut modifier cette Politique de Confidentialité afin de refléter les évolutions légales, réglementaires ou techniques.

La date de mise à jour sera affichée en haut du document.
TEXT;

        DB::table('regles')->updateOrInsert(
            ['id' => 1],
            [
                'conditions' => $conditions,
                'politique' => $politique,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
