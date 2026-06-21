<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalityOrientationTestSeeder extends Seeder
{
    /**
     * Installe le test de personnalite, dont les reponses alimentent des axes psychologiques.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $testId = $this->seedTest($now);

            $this->clearExistingQuestions($testId);

            foreach ($this->questions() as $questionData) {
                $questionId = DB::table('questions')->insertGetId([
                    'test_orientation_id' => $testId,
                    'libelle' => $questionData['libelle'],
                    'type' => 'choix_unique',
                    'domaine' => 'personnalite',
                    'ordre' => $questionData['ordre'],
                    'obligatoire' => true,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($questionData['choices'] as $choiceIndex => $choiceData) {
                    DB::table('choix_reponses')->insert([
                        'question_id' => $questionId,
                        'libelle' => $choiceData['libelle'],
                        'ordre' => $choiceIndex + 1,
                        'valeur' => (int) max($choiceData['axes']),
                        // Le test personnalite garde les axes ici: la conversion filiere se fait ensuite par service.
                        'metadata' => json_encode([
                            'code' => $choiceData['code'],
                            'scoring_type' => 'personality_axes',
                            'axes' => $choiceData['axes'],
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    /**
     * Cree ou met a jour la fiche du test publie.
     */
    private function seedTest($now): int
    {
        $values = [
            'description' => 'Test de 20 questions qui mesure les axes logique, social, creatif et leadership avant conversion vers les filieres.',
            'langue' => 'fr',
            'version' => 1,
            'duree_minutes' => 20,
            'statut' => 'publie',
            'cree_par' => null,
            'updated_at' => $now,
        ];

        $id = DB::table('tests_orientation')
            ->where('titre', 'Test de personnalite - Connaissance de soi')
            ->where('langue', 'fr')
            ->value('id');

        if ($id) {
            DB::table('tests_orientation')->where('id', $id)->update($values);
            return $id;
        }

        return DB::table('tests_orientation')->insertGetId([
            'titre' => 'Test de personnalite - Connaissance de soi',
            ...$values,
            'created_at' => $now,
        ]);
    }

    /**
     * Supprime les anciennes questions pour relancer le seeder sans doublons.
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
     * Banque des 20 questions avec poids par axe log/soc/crea/lead.
     */
    private function questions(): array
    {
        return [
            ['ordre' => 1, 'libelle' => 'Dans un groupe, tu es plutot celui/celle qui :', 'choices' => [
                ['code' => 'A', 'libelle' => "Analyse froidement la situation avant d'agir", 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => "Ressent l'ambiance et le moral du groupe", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 1, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Propose des idees originales', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 1]],
                ['code' => 'D', 'libelle' => 'Prend les decisions et organise', 'axes' => ['log' => 1, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 2, 'libelle' => 'Face a un echec, ta premiere reaction est :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Comprendre rationnellement ce qui a mal fonctionne', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => 'Ressentir et exprimer tes emotions', 'axes' => ['log' => 0, 'soc' => 3, 'crea' => 4, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Chercher une approche differente, creative', 'axes' => ['log' => 1, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Rebondir immediatement vers un nouvel objectif', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 3, 'libelle' => "Tes amis te decriraient comme quelqu'un de :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Rationnel et reflechi', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => "Chaleureux et a l'ecoute", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 1, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Original et imaginatif', 'axes' => ['log' => 0, 'soc' => 1, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Confiant et determine', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 4, 'libelle' => 'Quand tu dois prendre une decision importante, tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Listes les avantages et inconvenients', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => "Demandes l'avis de personnes proches", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Suis ton intuition et ton ressenti', 'axes' => ['log' => 0, 'soc' => 1, 'crea' => 4, 'lead' => 1]],
                ['code' => 'D', 'libelle' => 'Decides vite et assumes les consequences', 'axes' => ['log' => 1, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 5, 'libelle' => 'Dans une dispute entre deux amis, tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Analyses objectivement qui a raison', 'axes' => ['log' => 5, 'soc' => 1, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'Cherches a apaiser et reconcilier', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => "Evites le conflit, mal a l'aise", 'axes' => ['log' => 0, 'soc' => 2, 'crea' => 3, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Prends position fermement', 'axes' => ['log' => 1, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 6, 'libelle' => 'Ce qui te stresse le plus :', 'choices' => [
                ['code' => 'A', 'libelle' => "L'incertitude et le manque d'information", 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'Les tensions et conflits entre personnes', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 1, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'La routine et le manque de nouveaute', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 1]],
                ['code' => 'D', 'libelle' => "Perdre le controle d'une situation", 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 7, 'libelle' => 'Ton style de travail prefere :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Seul, concentre, avec des donnees precises', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'En equipe, dans une bonne ambiance', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 1, 'lead' => 1]],
                ['code' => 'C', 'libelle' => 'Libre, sans trop de contraintes', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'En dirigeant un projet ou une equipe', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 8, 'libelle' => 'Une personne te raconte un probleme personnel. Tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Lui proposes une solution logique', 'axes' => ['log' => 5, 'soc' => 1, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => "L'ecoutes sans juger ni interrompre", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Compatis et partages une histoire similaire', 'axes' => ['log' => 0, 'soc' => 3, 'crea' => 3, 'lead' => 0]],
                ['code' => 'D', 'libelle' => "La motives a passer a l'action", 'axes' => ['log' => 0, 'soc' => 1, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 9, 'libelle' => 'Tu preferes les activites ou :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Il faut resoudre des enigmes ou problemes', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 1, 'lead' => 0]],
                ['code' => 'B', 'libelle' => "Il faut aider ou accompagner quelqu'un", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Il faut imaginer ou creer quelque chose de nouveau', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Il faut convaincre ou motiver les autres', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 10, 'libelle' => 'Face a une nouvelle regle imposee que tu trouves injuste, tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Cherches des arguments logiques pour la contester', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 2]],
                ['code' => 'B', 'libelle' => "T'inquietes de l'impact sur les autres", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Imagines des facons de la contourner', 'axes' => ['log' => 1, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Organises une mobilisation pour la changer', 'axes' => ['log' => 0, 'soc' => 1, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 11, 'libelle' => 'Ton plus grand moteur dans la vie :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Comprendre comment fonctionne le monde', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'Avoir un impact positif sur les gens', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 1]],
                ['code' => 'C', 'libelle' => 'Exprimer qui tu es vraiment', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Reussir et accomplir de grandes choses', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 12, 'libelle' => 'Dans une nouvelle situation inconnue, tu :', 'choices' => [
                ['code' => 'A', 'libelle' => "Observes et analyses avant d'agir", 'axes' => ['log' => 5, 'soc' => 1, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => "Demandes de l'aide aux autres", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => "Improvises et t'adaptes naturellement", 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 1]],
                ['code' => 'D', 'libelle' => 'Prends les choses en main directement', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 13, 'libelle' => 'Ce que tu detestes le plus :', 'choices' => [
                ['code' => 'A', 'libelle' => "L'incoherence et le manque de logique", 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => "L'indifference envers les autres", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'La monotonie et les regles trop strictes', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => "L'inaction et la passivite", 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 14, 'libelle' => 'Tu te sens le plus fier quand :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Tu resous un probleme complexe', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => "Quelqu'un te dit que tu l'as aide", 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => "Tu crees quelque chose d'unique", 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Tu atteins un objectif ambitieux', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 15, 'libelle' => 'Comment geres-tu la pression ?', 'choices' => [
                ['code' => 'A', 'libelle' => 'Tu restes calme en suivant un raisonnement clair', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => 'Tu cherches du soutien aupres des autres', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Tu trouves des solutions inattendues', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Tu fonces sans trop reflechir', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 1, 'lead' => 5]],
            ]],
            ['ordre' => 16, 'libelle' => 'Dans tes relations, tu valorises le plus :', 'choices' => [
                ['code' => 'A', 'libelle' => "L'honnetete intellectuelle", 'axes' => ['log' => 5, 'soc' => 1, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'La proximite emotionnelle', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => "La liberte d'etre soi-meme", 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => "Le respect et l'admiration mutuelle", 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 17, 'libelle' => 'Si on te confiait un budget important, tu :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Ferais un plan detaille et rigoureux', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 1]],
                ['code' => 'B', 'libelle' => 'Consulterais les besoins de chacun avant de decider', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Chercherais une utilisation originale et impactante', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Investirais pour maximiser le resultat final', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 18, 'libelle' => "Tu preferes qu'on te dise :", 'choices' => [
                ['code' => 'A', 'libelle' => 'Tu es quelqu un de tres rationnel.', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'Tu es quelqu un de tres genereux.', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Tu es quelqu un de tres original.', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Tu es quelqu un de tres inspirant.', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 19, 'libelle' => "Face a l'echec d'un ami, tu :", 'choices' => [
                ['code' => 'A', 'libelle' => "L'aides a analyser objectivement ses erreurs", 'axes' => ['log' => 5, 'soc' => 1, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'Le soutiens emotionnellement avant tout', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => "L'encourages a voir les choses differemment", 'axes' => ['log' => 0, 'soc' => 1, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Le pousses a se relever et continuer', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
            ['ordre' => 20, 'libelle' => 'En un mot, tu te definis comme :', 'choices' => [
                ['code' => 'A', 'libelle' => 'Rationnel', 'axes' => ['log' => 5, 'soc' => 0, 'crea' => 0, 'lead' => 0]],
                ['code' => 'B', 'libelle' => 'Empathique', 'axes' => ['log' => 0, 'soc' => 5, 'crea' => 0, 'lead' => 0]],
                ['code' => 'C', 'libelle' => 'Creatif', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 5, 'lead' => 0]],
                ['code' => 'D', 'libelle' => 'Leader', 'axes' => ['log' => 0, 'soc' => 0, 'crea' => 0, 'lead' => 5]],
            ]],
        ];
    }
}
