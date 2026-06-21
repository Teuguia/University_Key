<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralSituationOrientationTestSeeder extends Seeder
{
    /**
     * Installe le second test general, base sur des mises en situation.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $filieres = $this->filieresBySlug();
            $testId = $this->seedTest($now);

            $this->clearExistingQuestions($testId);

            foreach ($this->questions() as $questionData) {
                $questionId = DB::table('questions')->insertGetId([
                    'test_orientation_id' => $testId,
                    'libelle' => $questionData['libelle'],
                    'type' => 'choix_unique',
                    'domaine' => 'general_situation',
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
                        // Metadata conserve le code A/B/C/D et tous les poids, meme ceux a 0.
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
                            'justification' => "Poids {$slug} du test general mises en situation",
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Recupere les filieres creees par le premier seeder general.
     */
    private function filieresBySlug(): array
    {
        $names = [
            'sci' => 'Scientifique',
            'lit' => 'Litteraire',
            'tech' => 'Technique',
            'com' => 'Commercial',
            'sante' => 'Sante',
        ];

        $ids = [];

        foreach ($names as $slug => $name) {
            $ids[$slug] = DB::table('filieres')->where('nom', $name)->value('id');
        }

        return $ids;
    }

    /**
     * Cree ou met a jour la fiche du test publie.
     */
    private function seedTest($now): int
    {
        $values = [
            'description' => 'Test general complementaire de 20 mises en situation pour croiser les preferences et les reactions concretes.',
            'langue' => 'fr',
            'version' => 1,
            'duree_minutes' => 20,
            'statut' => 'publie',
            'cree_par' => null,
            'updated_at' => $now,
        ];

        $id = DB::table('tests_orientation')
            ->where('titre', 'Test general 2 - Mises en situation')
            ->where('langue', 'fr')
            ->value('id');

        if ($id) {
            DB::table('tests_orientation')->where('id', $id)->update($values);
            return $id;
        }

        return DB::table('tests_orientation')->insertGetId([
            'titre' => 'Test general 2 - Mises en situation',
            ...$values,
            'created_at' => $now,
        ]);
    }

    /**
     * Supprime les anciennes questions pour permettre de relancer le seeder sans doublons.
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
     * Banque des 20 mises en situation avec choix et poids par filiere.
     */
    private function questions(): array
    {
        return [
            ['ordre' => 1, 'libelle' => 'Un appareil electronique de la maison tombe en panne. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Essaies de comprendre et reparer toi-meme', 'weights' => ['sci' => 4, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Appelles un professionnel et discutes du probleme', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 2, 'sante' => 0, 'lit' => 3]],
                ['code' => 'C', 'libelle' => "Laisses tomber, ce n'est pas ton truc", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 1, 'lit' => 2]],
                ['code' => 'D', 'libelle' => "Calcules s'il vaut mieux reparer ou racheter", 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 2, 'libelle' => 'Un ami se sent tres mal physiquement. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Cherches les symptomes sur internet methodiquement', 'weights' => ['sci' => 3, 'tech' => 0, 'com' => 0, 'sante' => 4, 'lit' => 0]],
                ['code' => 'B', 'libelle' => "L'ecoutes longuement parler de ce qu'il ressent", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 2, 'lit' => 4]],
                ['code' => 'C', 'libelle' => "L'accompagnes directement chez un medecin", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                ['code' => 'D', 'libelle' => 'Lui proposes une solution pratique rapide', 'weights' => ['sci' => 1, 'tech' => 2, 'com' => 2, 'sante' => 1, 'lit' => 0]],
            ]],
            ['ordre' => 3, 'libelle' => 'Tu dois organiser un evenement pour ta classe. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Crees un tableau Excel avec budget et planning', 'weights' => ['sci' => 2, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Rediges une belle invitation pour convaincre', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 2, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => "Penses d'abord au confort de tout le monde", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 5, 'lit' => 1]],
                ['code' => 'D', 'libelle' => 'Bricoles la decoration toi-meme', 'weights' => ['sci' => 1, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 4, 'libelle' => 'En cours, le professeur pose une question difficile. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Calcules rapidement dans ta tete', 'weights' => ['sci' => 5, 'tech' => 2, 'com' => 2, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Formules une reponse argumentee et nuancee', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Penses a un exemple concret de la vie reelle', 'weights' => ['sci' => 1, 'tech' => 2, 'com' => 2, 'sante' => 3, 'lit' => 1]],
                ['code' => 'D', 'libelle' => 'Preferes ne pas repondre devant tout le monde', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 5, 'libelle' => "Tu visites une usine ou un chantier. Ce qui t'interesse le plus :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Le fonctionnement des machines', 'weights' => ['sci' => 3, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Les conditions de travail des ouvriers', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 1, 'sante' => 3, 'lit' => 3]],
                ['code' => 'C', 'libelle' => 'Le cout et la rentabilite du projet', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ['code' => 'D', 'libelle' => "Rien de special ne t'attire ici", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 2]],
            ]],
            ['ordre' => 6, 'libelle' => 'Un proche te demande conseil pour un probleme financier. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Analyses les chiffres et proposes un budget', 'weights' => ['sci' => 2, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => "L'ecoutes avec empathie sans juger", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 3, 'lit' => 4]],
                ['code' => 'C', 'libelle' => 'Cherches une solution technique (app, outil)', 'weights' => ['sci' => 2, 'tech' => 4, 'com' => 1, 'sante' => 0, 'lit' => 0]],
                ['code' => 'D', 'libelle' => 'Le rassures simplement sans entrer dans le detail', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 2, 'lit' => 2]],
            ]],
            ['ordre' => 7, 'libelle' => "Tu dois rediger un rapport pour l'ecole. Tu :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Structures avec des donnees chiffrees', 'weights' => ['sci' => 4, 'tech' => 2, 'com' => 3, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Soignes le style et les tournures de phrases', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Inclus des exemples humains et des temoignages', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 4, 'lit' => 3]],
                ['code' => 'D', 'libelle' => 'Fais le minimum requis rapidement', 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 1, 'sante' => 1, 'lit' => 1]],
            ]],
            ['ordre' => 8, 'libelle' => "Lors d'une sortie en groupe, tu es plutot celui/celle qui :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Regle les problemes techniques (GPS, appareils)', 'weights' => ['sci' => 2, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Anime les discussions et raconte des histoires', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Verifie que tout le monde va bien', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 1]],
                ['code' => 'D', 'libelle' => 'Gere le budget et les paiements', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 9, 'libelle' => 'Face a un texte de loi ou un reglement complique. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Cherches la logique derriere chaque regle', 'weights' => ['sci' => 3, 'tech' => 1, 'com' => 1, 'sante' => 0, 'lit' => 2]],
                ['code' => 'B', 'libelle' => "L'analyses mot par mot avec precision", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => "Demandes a quelqu'un de te l'expliquer simplement", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 2, 'lit' => 1]],
                ['code' => 'D', 'libelle' => "Cela ne t'interesse pas du tout", 'weights' => ['sci' => 1, 'tech' => 2, 'com' => 1, 'sante' => 1, 'lit' => 0]],
            ]],
            ['ordre' => 10, 'libelle' => 'Tu dois choisir un projet scolaire de groupe. Tu choisis :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Construire un objet ou robot', 'weights' => ['sci' => 3, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Ecrire une piece de theatre ou un journal', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Une campagne de sensibilisation sante', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 5, 'lit' => 1]],
                ['code' => 'D', 'libelle' => "Une simulation d'entreprise", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 11, 'libelle' => "Quelqu'un te montre une nouvelle application mobile. Tu remarques d'abord :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Comment elle a ete codee techniquement', 'weights' => ['sci' => 3, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Le texte et les messages affiches', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Si elle peut aider les gens au quotidien', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 1, 'sante' => 4, 'lit' => 1]],
                ['code' => 'D', 'libelle' => 'Son potentiel commercial', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 12, 'libelle' => 'Pendant les vacances, tu preferes :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Bricoler, monter ou reparer quelque chose', 'weights' => ['sci' => 2, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Lire des livres ou ecrire un journal', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Faire du benevolat ou aider ta communaute', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 1]],
                ['code' => 'D', 'libelle' => 'Monter un petit commerce ou business', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 13, 'libelle' => 'Un debat eclate sur un sujet de societe. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Cherches des donnees factuelles pour trancher', 'weights' => ['sci' => 4, 'tech' => 1, 'com' => 1, 'sante' => 0, 'lit' => 1]],
                ['code' => 'B', 'libelle' => 'Prends position et argumentes avec passion', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => "Penses a l'impact humain de chaque position", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 4, 'lit' => 3]],
                ['code' => 'D', 'libelle' => 'Preferes rester neutre et observer', 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 1, 'sante' => 1, 'lit' => 1]],
            ]],
            ['ordre' => 14, 'libelle' => 'Tu visites un hopital pour accompagner un proche. Tu observes surtout :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Le fonctionnement des equipements medicaux', 'weights' => ['sci' => 3, 'tech' => 2, 'com' => 0, 'sante' => 3, 'lit' => 0]],
                ['code' => 'B', 'libelle' => "L'attitude et les paroles du personnel", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 3, 'lit' => 4]],
                ['code' => 'C', 'libelle' => "Le niveau de soin et d'attention apporte", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                ['code' => 'D', 'libelle' => "L'organisation et la gestion de l'etablissement", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 1, 'lit' => 0]],
            ]],
            ['ordre' => 15, 'libelle' => 'Tu dois vendre un produit a un client difficile. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Expliques techniquement ses avantages', 'weights' => ['sci' => 2, 'tech' => 3, 'com' => 3, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Racontes une histoire convaincante', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 3, 'sante' => 0, 'lit' => 4]],
                ['code' => 'C', 'libelle' => "Comprends d'abord ses besoins reels", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 2, 'sante' => 3, 'lit' => 2]],
                ['code' => 'D', 'libelle' => 'Negocies fermement le prix', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 16, 'libelle' => 'Face a une machine ou un logiciel complique. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Explores tous les boutons par curiosite', 'weights' => ['sci' => 3, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => "Lis attentivement le mode d'emploi", 'weights' => ['sci' => 1, 'tech' => 2, 'com' => 1, 'sante' => 0, 'lit' => 3]],
                ['code' => 'C', 'libelle' => "Demandes a quelqu'un de t'aider", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 2, 'lit' => 1]],
                ['code' => 'D', 'libelle' => "L'evites completement si possible", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 1, 'lit' => 1]],
            ]],
            ['ordre' => 17, 'libelle' => 'Tu accompagnes un enfant malade chez le medecin. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Poses des questions precises sur le diagnostic', 'weights' => ['sci' => 3, 'tech' => 0, 'com' => 0, 'sante' => 4, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Le rassures avec des mots doux', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 3, 'lit' => 4]],
                ['code' => 'C', 'libelle' => 'Restes calme et pratique', 'weights' => ['sci' => 1, 'tech' => 1, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                ['code' => 'D', 'libelle' => "Te sens mal a l'aise dans ce contexte", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 1]],
            ]],
            ['ordre' => 18, 'libelle' => 'Tu dois choisir entre deux stages : Tu choisis :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Stage dans un laboratoire ou atelier technique', 'weights' => ['sci' => 4, 'tech' => 5, 'com' => 0, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => "Stage dans un journal ou une maison d'edition", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Stage dans un centre de sante ou ONG', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 0]],
                ['code' => 'D', 'libelle' => 'Stage dans une banque ou une entreprise', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
            ['ordre' => 19, 'libelle' => "On te confie la gestion d'un petit budget familial. Tu :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Crees un tableau precis de toutes les depenses', 'weights' => ['sci' => 3, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Rediges des explications claires pour la famille', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 2, 'sante' => 0, 'lit' => 4]],
                ['code' => 'C', 'libelle' => "Priorises les besoins de sante d'abord", 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 5, 'lit' => 0]],
                ['code' => 'D', 'libelle' => 'Trouves ca stressant et delegues si possible', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 1, 'lit' => 1]],
            ]],
            ['ordre' => 20, 'libelle' => 'Si tu devais resumer ta personnalite en un mot :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Logique', 'weights' => ['sci' => 5, 'tech' => 3, 'com' => 1, 'sante' => 0, 'lit' => 0]],
                ['code' => 'B', 'libelle' => 'Expressif', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 1, 'sante' => 0, 'lit' => 5]],
                ['code' => 'C', 'libelle' => 'Bienveillant', 'weights' => ['sci' => 0, 'tech' => 0, 'com' => 0, 'sante' => 5, 'lit' => 1]],
                ['code' => 'D', 'libelle' => 'Ambitieux', 'weights' => ['sci' => 0, 'tech' => 1, 'com' => 5, 'sante' => 0, 'lit' => 0]],
            ]],
        ];
    }
}
