<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralOrientationTestSeeder extends Seeder
{
    /**
     * Installe le test general et ses poids par grande famille de filieres.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $filieres = $this->seedFilieres($now);
            $testId = $this->seedTest($now);

            $this->clearExistingQuestions($testId);

            foreach ($this->questions() as $questionData) {
                $questionId = DB::table('questions')->insertGetId([
                    'test_orientation_id' => $testId,
                    'libelle' => $questionData['libelle'],
                    'type' => 'choix_unique',
                    'domaine' => 'general',
                    'ordre' => $questionData['ordre'],
                    'obligatoire' => true,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($questionData['choices'] as $choiceIndex => $choiceData) {
                    $choiceId = DB::table('choix_reponses')->insertGetId([
                        'question_id' => $questionId,
                        'libelle' => $choiceData['libelle'],
                        'ordre' => $choiceIndex + 1,
                        'valeur' => (int) max($choiceData['weights']),
                        // Les poids sont aussi conserves en metadata pour audit et affichage futur.
                        'metadata' => json_encode([
                            'code' => $choiceData['code'],
                            'weights' => $choiceData['weights'],
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    foreach ($choiceData['weights'] as $slug => $weight) {
                        if ((float) $weight <= 0) {
                            continue;
                        }

                        DB::table('poids_filieres')->insert([
                            'choix_reponse_id' => $choiceId,
                            'filiere_id' => $filieres[$slug],
                            'poids' => $weight,
                            'justification' => "Poids {$slug} du test general",
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Cree ou met a jour les cinq familles evaluees par ce test.
     */
    private function seedFilieres($now): array
    {
        $definitions = [
            'sci' => [
                'nom' => 'Scientifique',
                'domaine' => 'Sciences',
                'description' => 'Parcours orientes mathematiques, physique, sciences et analyse.',
                'diplome_obtenu' => 'Licence scientifique',
                'metiers_associes' => ['Chercheur', 'Ingenieur', 'Analyste scientifique'],
                'competences_requises' => ['Logique', 'Calcul', 'Rigueur'],
            ],
            'lit' => [
                'nom' => 'Litteraire',
                'domaine' => 'Lettres et sciences humaines',
                'description' => 'Parcours orientes langues, droit, communication, ecriture et argumentation.',
                'diplome_obtenu' => 'Licence en lettres ou sciences humaines',
                'metiers_associes' => ['Juriste', 'Journaliste', 'Enseignant', 'Ecrivain'],
                'competences_requises' => ['Expression', 'Analyse', 'Argumentation'],
            ],
            'tech' => [
                'nom' => 'Technique',
                'domaine' => 'Technologie',
                'description' => 'Parcours orientes pratique, informatique, maintenance, construction et innovation.',
                'diplome_obtenu' => 'BTS ou Licence professionnelle technique',
                'metiers_associes' => ['Developpeur', 'Technicien', 'Electronicien'],
                'competences_requises' => ['Pratique', 'Resolution de problemes', 'Precision'],
            ],
            'com' => [
                'nom' => 'Commercial',
                'domaine' => 'Commerce et gestion',
                'description' => 'Parcours orientes gestion, vente, finance, entrepreneuriat et organisation.',
                'diplome_obtenu' => 'Licence en commerce ou gestion',
                'metiers_associes' => ['Commercial', 'Entrepreneur', 'Gestionnaire', 'Banquier'],
                'competences_requises' => ['Negociation', 'Organisation', 'Leadership'],
            ],
            'sante' => [
                'nom' => 'Sante',
                'domaine' => 'Sante et social',
                'description' => 'Parcours orientes soins, biologie, accompagnement humain et service social.',
                'diplome_obtenu' => 'Diplome en sciences de la sante',
                'metiers_associes' => ['Medecin', 'Infirmier', 'Pharmacien', 'Assistant social'],
                'competences_requises' => ['Empathie', 'Observation', 'Patience'],
            ],
        ];

        $ids = [];

        foreach ($definitions as $slug => $definition) {
            $values = [
                ...$definition,
                'niveau' => $slug === 'tech' ? 'BTS' : 'Licence',
                'duree_annees' => $slug === 'tech' ? 2 : 3,
                'conditions_acces' => 'Baccalaureat ou diplome equivalent.',
                'debouches' => implode(', ', $definition['metiers_associes']),
                'metiers_associes' => json_encode($definition['metiers_associes'], JSON_UNESCAPED_UNICODE),
                'competences_requises' => json_encode($definition['competences_requises'], JSON_UNESCAPED_UNICODE),
                'active' => true,
                'updated_at' => $now,
            ];

            $id = DB::table('filieres')->where('nom', $definition['nom'])->value('id');

            if ($id) {
                DB::table('filieres')->where('id', $id)->update($values);
            } else {
                $id = DB::table('filieres')->insertGetId([
                    ...$values,
                    'created_at' => $now,
                ]);
            }

            $ids[$slug] = $id;
        }

        return $ids;
    }

    /**
     * Cree ou met a jour la fiche du test publie.
     */
    private function seedTest($now): int
    {
        $values = [
            'description' => 'Test general de 20 questions evaluant les affinites scientifique, litteraire, technique, commerciale et sante.',
            'langue' => 'fr',
            'version' => 1,
            'duree_minutes' => 20,
            'statut' => 'publie',
            'cree_par' => null,
            'updated_at' => $now,
        ];

        $id = DB::table('tests_orientation')
            ->where('titre', 'Test general')
            ->where('langue', 'fr')
            ->value('id');

        if ($id) {
            DB::table('tests_orientation')->where('id', $id)->update($values);
            return $id;
        }

        return DB::table('tests_orientation')->insertGetId([
            'titre' => 'Test general',
            ...$values,
            'created_at' => $now,
        ]);
    }

    /**
     * Supprime les anciennes questions du test pour rendre le seeder idempotent.
     */
    private function clearExistingQuestions(int $testId): void
    {
        $questionIds = DB::table('questions')
            ->where('test_orientation_id', $testId)
            ->pluck('id');

        if ($questionIds->isEmpty()) {
            return;
        }

        $choiceIds = DB::table('choix_reponses')
            ->whereIn('question_id', $questionIds)
            ->pluck('id');

        if ($choiceIds->isNotEmpty()) {
            DB::table('poids_filieres')->whereIn('choix_reponse_id', $choiceIds)->delete();
            DB::table('choix_reponses')->whereIn('id', $choiceIds)->delete();
        }

        DB::table('questions')->whereIn('id', $questionIds)->delete();
    }

    /**
     * Banque des 20 questions, avec choix et poids par code de filiere.
     */
    private function questions(): array
    {
        return [
            [
                'ordre' => 1,
                'libelle' => 'Quelle activite te passionne le plus pendant ton temps libre ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Demonter, reparer, construire des objets', 'weights' => ['sci' => 3, 'tech' => 5, 'com' => 1, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => "Lire, ecrire, debattre d'idees", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 1, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Aider, soigner, prendre soin des autres', 'weights' => ['sci' => 1, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => 'Vendre, negocier, organiser un evenement', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 2,
                'libelle' => 'Ta matiere preferee au lycee ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Mathematiques / Physique-Chimie', 'weights' => ['sci' => 5, 'tech' => 3, 'com' => 2, 'sante' => 2, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Philosophie / Litterature', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'SVT (Sciences de la Vie et de la Terre)', 'weights' => ['sci' => 3, 'tech' => 1, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Economie / Comptabilite', 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 3,
                'libelle' => 'Face a un probleme, tu preferes :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Calculer et chercher une solution logique', 'weights' => ['sci' => 5, 'tech' => 3, 'com' => 2, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => "Discuter et argumenter avec d'autres", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 2, 'sante' => 1, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Observer les symptomes et diagnostiquer', 'weights' => ['sci' => 1, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Reparer ou fabriquer quelque chose', 'weights' => ['sci' => 1, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 4,
                'libelle' => "Quel metier t'attire le plus naturellement ?",
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Ingenieur / Developpeur informatique', 'weights' => ['sci' => 5, 'tech' => 4, 'com' => 1, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Avocat / Journaliste / Ecrivain', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Medecin / Infirmier / Pharmacien', 'weights' => ['sci' => 2, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Entrepreneur / Commercial / Banquier', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 5,
                'libelle' => 'Dans un groupe de travail, ton role naturel est :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Celui qui resout les problemes techniques', 'weights' => ['sci' => 4, 'tech' => 5, 'com' => 0, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Celui qui redige et communique les idees', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 2, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => "Celui qui s'occupe du bien-etre de l'equipe", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 5, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => 'Celui qui gere le budget et les negociations', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 6,
                'libelle' => 'Quel type de lecture preferes-tu ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Articles scientifiques, manuels techniques', 'weights' => ['sci' => 5, 'tech' => 4, 'com' => 1, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Romans, essais, poesie', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Magazines sante, biologie', 'weights' => ['sci' => 1, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Actualite economique, business', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 7,
                'libelle' => 'Tu preferes un environnement de travail :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Laboratoire ou bureau technique', 'weights' => ['sci' => 5, 'tech' => 4, 'com' => 0, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Bibliotheque, tribunal, redaction', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Hopital, clinique, terrain', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Entreprise, marche, bureau commercial', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 8,
                'libelle' => 'Quelle qualite te decrit le mieux ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Logique et methodique', 'weights' => ['sci' => 5, 'tech' => 3, 'com' => 1, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Creatif et expressif', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Empathique et patient', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => 'Persuasif et dynamique', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 9,
                'libelle' => 'Quel film/documentaire te captive le plus ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Science-fiction, technologie, espace', 'weights' => ['sci' => 5, 'tech' => 3, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Drame, biographie, societe', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Medical, urgences, sante', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Business, startups, finance', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 10,
                'libelle' => 'Tu preferes apprendre par :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Experimentation et calcul', 'weights' => ['sci' => 5, 'tech' => 4, 'com' => 1, 'sante' => 2, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Lecture et discussion', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Pratique sur le terrain avec les gens', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 1, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Etude de cas concrets en entreprise', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 11,
                'libelle' => 'Ce qui te motive le plus dans une carriere :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Innover, creer des technologies', 'weights' => ['sci' => 5, 'tech' => 4, 'com' => 1, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Exprimer des idees, influencer la societe', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Sauver des vies, soulager la souffrance', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => "Gagner de l'argent, reussir financierement", 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 12,
                'libelle' => "Tu es plutot quelqu'un qui :",
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Aime les chiffres et la precision', 'weights' => ['sci' => 5, 'tech' => 3, 'com' => 3, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Aime les mots et les nuances', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Aime le contact humain direct', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 5, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => 'Aime convaincre et vendre une idee', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 1]],
                ],
            ],
            [
                'ordre' => 13,
                'libelle' => 'Face a une panne ou un bug, tu :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Analyses methodiquement la cause', 'weights' => ['sci' => 5, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => "Demandes l'avis d'un expert", 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 1, 'sante' => 1, 'lit' => 1]],
                    ['code' => 'C', 'libelle' => "T'inquietes pour les personnes affectees", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 4, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => 'Calcules le cout de la reparation', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 14,
                'libelle' => "Ton budget annuel d'etudes approximatif ?",
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Moins de 500 000 FCFA', 'weights' => ['sci' => 1, 'tech' => 3, 'com' => 2, 'sante' => 0, 'lit' => 2]],
                    ['code' => 'B', 'libelle' => '500 000 a 1 000 000 FCFA', 'weights' => ['sci' => 3, 'tech' => 2, 'com' => 3, 'sante' => 1, 'lit' => 3]],
                    ['code' => 'C', 'libelle' => 'Plus de 1 000 000 FCFA', 'weights' => ['sci' => 2, 'tech' => 0, 'com' => 2, 'sante' => 5, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => 'Je ne sais pas encore', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 15,
                'libelle' => "Quelle activite scolaire t'a le plus plu ?",
                'choices' => [
                    ['code' => 'A', 'libelle' => 'TP de physique/chimie ou montage electronique', 'weights' => ['sci' => 4, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Redaction, dissertation, debat', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Sortie scolaire en milieu medical/social', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => "Simulation d'entreprise, projet economique", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 16,
                'libelle' => 'Tu acceptes de changer de ville pour etudier ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Oui sans hesiter', 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 1, 'sante' => 1, 'lit' => 1]],
                    ['code' => 'B', 'libelle' => 'Seulement si necessaire', 'weights' => ['sci' => 0.5, 'tech' => 0.5, 'com' => 0.5, 'sante' => 0.5, 'lit' => 0.5]],
                    ['code' => 'C', 'libelle' => 'Je prefere rester dans ma ville', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 17,
                'libelle' => 'Ton objectif professionnel dans 10 ans ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Creer ma propre entreprise technologique', 'weights' => ['sci' => 4, 'tech' => 4, 'com' => 3, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Etre reconnu pour mes ecrits ou plaidoiries', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Diriger un service de sante ou une clinique', 'weights' => ['sci' => 1, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Diriger une grande entreprise ou banque', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 18,
                'libelle' => 'Quelle activite manuelle te plait le plus ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Assembler des circuits, programmer', 'weights' => ['sci' => 3, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Aucune en particulier, je prefere reflechir', 'weights' => ['sci' => 1, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 4]],
                    ['code' => 'C', 'libelle' => "Aider quelqu'un physiquement (premiers secours)", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Organiser, planifier un evenement', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 19,
                'libelle' => 'Quelle phrase te correspond le mieux ?',
                'choices' => [
                    ['code' => 'A', 'libelle' => "J'aime comprendre comment les choses fonctionnent.", 'weights' => ['sci' => 5, 'tech' => 4, 'com' => 0, 'sante' => 1, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => "J'aime exprimer ce que je pense et ressens.", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => "J'aime prendre soin des autres.", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 1]],
                    ['code' => 'D', 'libelle' => "J'aime relever des defis et gagner.", 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
            [
                'ordre' => 20,
                'libelle' => 'Si tu devais choisir un seul domaine pour le reste de ta vie :',
                'choices' => [
                    ['code' => 'A', 'libelle' => 'Sciences et technologie', 'weights' => ['sci' => 5, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                    ['code' => 'B', 'libelle' => 'Lettres et sciences humaines', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                    ['code' => 'C', 'libelle' => 'Sante et social', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                    ['code' => 'D', 'libelle' => 'Commerce et gestion', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ],
            ],
        ];
    }
}
