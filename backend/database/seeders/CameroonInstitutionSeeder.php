<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CameroonInstitutionSeeder extends Seeder
{
    /**
     * Installe un premier catalogue d'etablissements camerounais et de filieres rattachees.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->institutions() as $institution) {
                $schoolId = $this->upsertInstitution($institution);
                $this->retireLegacyAliases($schoolId, $institution);
                $this->replaceAttachedPrograms($schoolId, $institution);
            }
        });
    }

    /**
     * Cree ou met a jour l'etablissement.
     */
    private function upsertInstitution(array $institution): int
    {
        $now = now();
        $values = [
            'type' => $institution['type'],
            'ville' => $institution['ville'],
            'region' => $institution['region'],
            'site_web' => $institution['site_web'],
            'description' => $institution['description'],
            'statut' => 'valide',
            'valide' => true,
            'updated_at' => $now,
        ];

        if (array_key_exists('logo', $institution)) {
            $values['logo'] = $institution['logo'];
        }

        if (array_key_exists('photos', $institution)) {
            $values['photos'] = json_encode($institution['photos'], JSON_UNESCAPED_SLASHES);
        }

        $id = DB::table('etablissements')->where('nom', $institution['nom'])->value('id');

        if (! $id && ! empty($institution['aliases'])) {
            $id = DB::table('etablissements')->whereIn('nom', $institution['aliases'])->value('id');
        }

        if ($id) {
            DB::table('etablissements')->where('id', $id)->update([
                'nom' => $institution['nom'],
                ...$values,
            ]);
            return (int) $id;
        }

        return DB::table('etablissements')->insertGetId([
            'nom' => $institution['nom'],
            ...$values,
            'created_at' => $now,
        ]);
    }

    /**
     * Rend invisibles les anciennes fiches mal orthographiees sans supprimer
     * des donnees qui pourraient etre visees par un historique utilisateur.
     */
    private function retireLegacyAliases(int $schoolId, array $institution): void
    {
        if (empty($institution['aliases'])) {
            return;
        }

        $legacyIds = DB::table('etablissements')
            ->whereIn('nom', $institution['aliases'])
            ->where('id', '!=', $schoolId)
            ->pluck('id');

        if ($legacyIds->isEmpty()) {
            return;
        }

        DB::table('etablissement_filiere')->whereIn('etablissement_id', $legacyIds)->delete();

        DB::table('etablissements')->whereIn('id', $legacyIds)->update([
            'statut' => 'rejete',
            'valide' => false,
            'updated_at' => now(),
        ]);
    }

    /**
     * Remplace les rattachements de filieres pour garder le seeder idempotent.
     */
    private function replaceAttachedPrograms(int $schoolId, array $institution): void
    {
        DB::table('etablissement_filiere')->where('etablissement_id', $schoolId)->delete();

        // Une ancienne filiere propre a cet etablissement ne doit plus etre
        // proposee dans les recherches apres une mise a jour du catalogue.
        // Elle reste en base (et donc dans un historique de recommandations),
        // mais les filieres presentes ci-dessous sont les seules reactives.
        DB::table('filieres')
            ->where('nom', 'like', $institution['code'] . ' - %')
            ->update(['active' => false, 'updated_at' => now()]);

        foreach ($institution['filieres'] as $program) {
            $programId = $this->upsertProgram($institution['code'], $program);

            DB::table('etablissement_filiere')->updateOrInsert(
                ['etablissement_id' => $schoolId, 'filiere_id' => $programId],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    /**
     * Cree une filiere lisible dans le catalogue et visible dans les recherches.
     */
    private function upsertProgram(string $schoolCode, string $program): int
    {
        $now = now();
        $name = "{$schoolCode} - {$program}";
        $details = $this->programDetails($program);
        $values = [
            'domaine' => $this->domainForProgram($program),
            'description' => $program,
            'niveau' => $details['niveau'],
            'duree_annees' => $details['duree_annees'],
            'diplome_obtenu' => $details['diplome_obtenu'],
            'conditions_acces' => 'Baccalaureat ou diplome equivalent selon la filiere.',
            'debouches' => 'Poursuite academique, concours, insertion professionnelle selon la specialite.',
            'metiers_associes' => json_encode([]),
            'competences_requises' => json_encode([]),
            'active' => true,
            'updated_at' => $now,
        ];

        $id = DB::table('filieres')->where('nom', $name)->value('id');

        if ($id) {
            DB::table('filieres')->where('id', $id)->update($values);
            return (int) $id;
        }

        return DB::table('filieres')->insertGetId([
            'nom' => $name,
            ...$values,
            'created_at' => $now,
        ]);
    }

    /**
     * Rend les informations de diplome coherentes avec l'intitule publie.
     * Les etablissements indiquent parfois une composante plutot qu'un diplome
     * de sortie : dans ce cas, le catalogue ne pretend pas connaitre le
     * diplome exact et invite l'etudiant a consulter les admissions annuelles.
     */
    private function programDetails(string $program): array
    {
        $text = mb_strtolower($program);

        return match (true) {
            str_contains($text, 'doctorat') || str_contains($text, 'phd') => [
                'niveau' => 'Doctorat',
                'duree_annees' => 3,
                'diplome_obtenu' => 'Doctorat / PhD',
            ],
            str_contains($text, 'master') => [
                'niveau' => 'Master',
                'duree_annees' => 2,
                'diplome_obtenu' => 'Master',
            ],
            str_contains($text, 'cycle ingenieur') || str_contains($text, 'ingenierie') => [
                'niveau' => 'Master',
                'duree_annees' => 3,
                'diplome_obtenu' => 'Diplome d ingenieur',
            ],
            str_contains($text, 'formation continue') || str_contains($text, 'stage diplomatique') || str_contains($text, 'suivi-evaluation') || str_contains($text, 'donnees spatiales') => [
                'niveau' => 'Formation_professionnelle',
                'duree_annees' => 1,
                'diplome_obtenu' => 'Attestation ou certificat selon le parcours',
            ],
            default => [
                'niveau' => 'Licence',
                'duree_annees' => 3,
                'diplome_obtenu' => 'Licence ou diplome universitaire selon le parcours',
            ],
        };
    }

    /**
     * Classe rapidement les programmes pour faciliter la recherche par domaine.
     */
    private function domainForProgram(string $program): string
    {
        $text = mb_strtolower($program);

        return match (true) {
            str_contains($text, 'informatique') || str_contains($text, 'technolog') || str_contains($text, 'telecom') || str_contains($text, 'reseau') || str_contains($text, 'mecatron') => 'Technologie',
            str_contains($text, 'genie') || str_contains($text, 'mecanique') || str_contains($text, 'geotech') || str_contains($text, 'geometre') || str_contains($text, 'mines') => 'Ingenierie',
            str_contains($text, 'mathematique') || str_contains($text, 'physique') || str_contains($text, 'chimie') || str_contains($text, 'biologie') || str_contains($text, 'sciences de la vie') => 'Sciences',
            str_contains($text, 'medecine') || str_contains($text, 'sante') || str_contains($text, 'pharm') || str_contains($text, 'biomedical') => 'Sante',
            str_contains($text, 'droit') || str_contains($text, 'juridique') || str_contains($text, 'politique') => 'Droit',
            str_contains($text, 'commerce') || str_contains($text, 'gestion') || str_contains($text, 'econom') || str_contains($text, 'finance') => 'Commerce et gestion',
            str_contains($text, 'agronomie') || str_contains($text, 'agric') || str_contains($text, 'veterinaire') || str_contains($text, 'environnement') => 'Agriculture et environnement',
            default => 'Enseignement superieur',
        };
    }

    /**
     * Donnees fournies pour alimenter le tableau administrateur des etablissements.
     */
    private function institutions(): array
    {
        return [
            [
                'code' => 'UY1',
                'nom' => 'Universite de Yaounde I (UY1)',
                'type' => 'universite_publique',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://uy1.uninet.cm',
                'logo' => 'images/etablissements/uy1-entree.png',
                'photos' => [
                    'images/etablissements/uy1-entree.png',
                    'images/etablissements/uy1-amphitheatre.png',
                ],
                'description' => 'Universite publique camerounaise proposant des facultes et grandes ecoles scientifiques, litteraires, educatives et medicales.',
                'filieres' => [
                    'Mathematiques',
                    'Physique',
                    'Chimie',
                    'Informatique',
                    'Sciences de la vie',
                    'Faculte des arts, lettres et sciences humaines',
                    'Faculte des sciences de l education',
                    'Faculte de medecine et des sciences biomedicales',
                    'Ecole normale superieure (ENS)',
                    'Ecole nationale superieure polytechnique',
                    'Institut universitaire de technologie du bois (IUT)',
                ],
            ],
            [
                'code' => 'UY2',
                'nom' => 'Universite de Yaounde II (Soa)',
                'type' => 'universite_publique',
                'ville' => 'Soa',
                'region' => 'Centre',
                'site_web' => 'https://site.univ-yaounde2.org',
                'description' => 'Universite publique orientee droit, economie, gestion, relations internationales, demographie et communication.',
                'filieres' => [
                    'Faculte des sciences juridiques et politiques (FSJP)',
                    'Faculte des sciences economiques et de gestion (FSEG)',
                    'Institut des relations internationales du Cameroun (IRIC)',
                    'Institut de formation et de recherche demographiques (IFORD)',
                    'Ecole superieure des sciences et techniques de l information et de la communication (ESSTIC)',
                ],
            ],
            [
                'code' => 'UDLA',
                'nom' => 'Universite de Douala',
                'type' => 'universite_publique',
                'ville' => 'Douala',
                'region' => 'Littoral',
                'site_web' => 'https://univ-douala.cm',
                'description' => 'Universite publique du Littoral avec facultes, instituts et grandes ecoles en sciences, droit, gestion, sante, commerce et technique.',
                'filieres' => [
                    'Faculte des sciences',
                    'Faculte de droit et sciences politiques',
                    'Faculte des lettres et sciences humaines',
                    'Faculte des sciences economiques et gestion appliquee',
                    'Faculte de medecine et sciences pharmaceutiques',
                    'Institut universitaire de technologie',
                    'Institut des sciences halieutiques de Yabassi',
                    'Institut des beaux-arts de Nkongsamba',
                    'ESSEC commerce',
                    'ENSET technique',
                    'Ecole nationale superieure polytechnique de Douala',
                ],
            ],
            [
                'code' => 'UDS',
                'nom' => 'Universite de Dschang (UDs)',
                'type' => 'universite_publique',
                'ville' => 'Dschang',
                'region' => 'Ouest',
                'site_web' => 'https://univ-dschang.org',
                'description' => 'Universite publique de l Ouest, connue pour les sciences, l agronomie, le droit, la gestion, les arts et l IUT Fotso Victor.',
                'filieres' => [
                    'Faculte des lettres et sciences humaines (FLSH)',
                    'Faculte des sciences economiques et gestion (FSEG)',
                    'Faculte des sciences juridiques et politiques (FSJP)',
                    'Faculte des sciences: biochimie, physique, mathematiques, informatique, biologie',
                    'Faculte d agronomie et sciences agricoles (FASA)',
                    'IUT Fotso Victor Bandjoun: genie civil, electricite, informatique, telecommunications, maintenance',
                    'Institut des beaux-arts de Foumban',
                    'Faculte de medecine et sciences pharmaceutiques',
                ],
            ],
            [
                'code' => 'UND',
                'nom' => 'Universite de Ngaoundere',
                'type' => 'universite_publique',
                'ville' => 'Ngaoundere',
                'region' => 'Adamaoua',
                'site_web' => 'https://univ-ndere.cm',
                'description' => 'Universite publique de l Adamaoua avec facultes, ecoles agro-industrielles, veterinaire, genie chimique, mines et technologie.',
                'filieres' => [
                    'Faculte des arts, lettres et sciences humaines',
                    'Faculte des sciences: physique, mathematiques, informatique, biologie, geologie, chimie, environnement, energie renouvelable',
                    'Faculte des sciences economiques et gestion',
                    'Faculte de droit et sciences politiques',
                    'Faculte de medecine et sciences biomedicales',
                    'Ecole de geologie et exploitation miniere',
                    'Ecole nationale des sciences agro-industrielles (ENSAI)',
                    'Ecole de sciences et medecine veterinaire (ESMV)',
                    'Ecole de genie chimique et industries minerales',
                    'Institut universitaire de technologie',
                ],
            ],
            [
                'code' => 'UMAR',
                'nom' => 'Universite de Maroua',
                'type' => 'universite_publique',
                'ville' => 'Maroua',
                'region' => 'Nord',
                'site_web' => 'https://univ-maroua.cm',
                'description' => 'Universite publique du septentrion avec facultes et grandes ecoles en sciences, droit, economie, lettres, enseignement et mines.',
                'filieres' => [
                    'Faculte des sciences economiques et gestion (FSEG)',
                    'Faculte des sciences (FS)',
                    'Faculte des sciences juridiques et politiques (FSJP)',
                    'Faculte des arts, lettres et sciences humaines (FALSH)',
                    'Ecole normale superieure de Maroua',
                    'Ecole nationale superieure polytechnique de Maroua',
                    'Ecole nationale superieure des mines et des industries petrolieres (ENSMIP)',
                    'Departements de chimie, energies renouvelables, mathematiques-informatique, sciences biologiques, sciences de la Terre et physiques',
                ],
            ],
            [
                'code' => 'UBAM',
                'nom' => 'Universite de Bamenda',
                'type' => 'universite_publique',
                'ville' => 'Bamenda',
                'region' => 'Nord-Ouest',
                'site_web' => 'https://uniba.cm',
                'description' => 'Universite publique anglophone avec facultes, college de technologie, instituts de commerce, transport et formation des enseignants.',
                'filieres' => [
                    'Faculty of Arts',
                    'Faculty of Law and Political Science',
                    'Faculty of Economics and Management Science',
                    'Faculty of Education',
                    'Faculty of Science',
                    'Faculty of Health Science',
                    'College of Technology',
                    'Higher Institute of Commerce and Management',
                    'Higher Institute of Transport and Logistics',
                    'Higher Teachers Training College (HTTC)',
                    'Higher Technical Teachers Training College (HTTTC)',
                    'National Higher Polytechnic Institute (NAHPI)',
                ],
            ],
            [
                'code' => 'UBUEA',
                'nom' => 'Universite de Buea (UB)',
                'type' => 'universite_publique',
                'ville' => 'Buea',
                'region' => 'Sud-Ouest',
                'site_web' => 'https://ubuea.cm',
                'description' => 'Universite publique anglophone avec facultes et ecoles en arts, sciences, education, sante, ingenierie, droit, gestion et agriculture.',
                'filieres' => [
                    'Faculty of Arts (FA)',
                    'Faculty of Science (FS)',
                    'Faculty of Education (FED)',
                    'Faculty of Health Sciences (FHS)',
                    'Faculty of Engineering and Technology (FET)',
                    'Faculty of Laws and Political Science (FLPS)',
                    'Faculty of Social and Management Sciences (FSMS)',
                    'Faculty of Agriculture and Veterinary Medicine (FAVM)',
                    'College of Technology (COLTECH)',
                    'Advanced School of Translators and Interpreters (ASTI)',
                    'Higher Technical Teachers Training College (HTTTC)',
                    'Higher Teachers Training College (HTTC)',
                ],
            ],
            [
                'code' => 'UBTA',
                'nom' => 'Universite de Bertoua',
                'type' => 'universite_publique',
                'ville' => 'Bertoua',
                'region' => 'Est',
                'site_web' => 'https://www.univ-bertoua.cm',
                'description' => 'Universite publique de l Est avec facultes et ecoles specialisees en agriculture, bois, eau, urbanisme, tourisme, mines et energie.',
                'filieres' => [
                    'Faculte des arts, lettres et sciences humaines',
                    'Faculte des sciences',
                    'Faculte des sciences economiques et gestion',
                    'Faculte de droit et sciences politiques',
                    'Ecole normale superieure de Bertoua',
                    'Institut superieur d agriculture, du bois, de l eau et de l environnement (ISABEE)',
                    'Ecole superieure des sciences de l urbanisme et du tourisme (ESSUT)',
                    'Ecole superieure de transformation des mines et des ressources energetiques (ESTM)',
                ],
            ],
            [
                'code' => 'UEBO',
                'nom' => 'Universite d Ebolowa',
                'type' => 'universite_publique',
                'ville' => 'Ebolowa',
                'region' => 'Sud',
                'site_web' => 'https://univ-ebolowa.cm',
                'description' => 'Universite publique du Sud avec facultes et ecoles en sciences, lettres, gestion, droit, sante, technique, maritime, agriculture et logistique.',
                'filieres' => [
                    'Faculte des sciences',
                    'Faculte des arts, lettres et sciences humaines',
                    'Faculte des sciences economiques et gestion',
                    'Faculte des sciences juridiques et politiques',
                    'Faculte de medecine et sciences pharmaceutiques de Sangmelima',
                    'ENSET enseignement technique',
                    'Ecole nationale superieure des technologies maritimes et oceaniques (ENSTMO)',
                    'Institut superieur d agriculture, de l eau et de l environnement (ISABEE)',
                    'Ecole superieure de transport, logistique et commerce (ESTLC)',
                ],
            ],
            [
                'code' => 'UGAR',
                'nom' => 'Universite de Garoua',
                'type' => 'universite_publique',
                'ville' => 'Garoua',
                'region' => 'Nord',
                'site_web' => 'https://univ-garoua.cm',
                'description' => 'Universite publique du Nord avec facultes, medecine, commerce et innovation artistique.',
                'filieres' => [
                    'Faculte des sciences juridiques et politiques (FSJP)',
                    'Faculte des sciences economiques et gestion (FSEG)',
                    'Faculte des sciences de l education (FSE)',
                    'Faculte des arts, lettres et sciences humaines (FALSH)',
                    'Faculte des sciences (FS)',
                    'Faculte de medecine et sciences biomedicales (FMSB)',
                    'Ecole superieure des sciences economiques et commerciales (ESSEC)',
                    'Institut des beaux-arts et de l innovation (IBAI)',
                ],
            ],
            [
                'code' => 'UDM',
                'nom' => 'Universite des Montagnes (UdM) - Bangangte',
                'type' => 'universite_privee',
                'ville' => 'Bangangte',
                'region' => 'Ouest',
                'site_web' => 'https://udesmontagnes.org',
                'description' => 'Universite privee reconnue, orientee sciences de la sante, sciences et technologies.',
                'filieres' => [
                    'Medecine',
                    'Chirurgie dentaire',
                    'Pharmacie',
                    'Sciences infirmieres',
                    'Kinesitherapie',
                    'Agronomie',
                    'Medecine veterinaire',
                    'Informatique et reseaux',
                    'Reseaux et telecommunications',
                    'Instrumentation biomedicale',
                    'Master en genie informatique',
                    'Master en genie biomedical',
                ],
            ],
            [
                'code' => 'UCAC',
                'nom' => 'Universite catholique d Afrique centrale (UCAC) - Yaounde',
                'type' => 'universite_privee',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://ucac-icy.net',
                'description' => 'Universite privee catholique reconnue, orientee gestion, droit, sciences sociales, philosophie et theologie.',
                'filieres' => [
                    'Sciences sociales et de gestion',
                    'Sciences juridiques et politiques',
                    'Theologie',
                    'Philosophie',
                    'Droit canonique',
                ],
            ],
            [
                'code' => 'UPAC',
                'nom' => 'Universite protestante d Afrique centrale (UPAC) - Yaounde',
                'type' => 'universite_privee',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://www.upac.cm',
                'description' => 'Universite privee protestante reconnue, proposant theologie, sciences sociales, TIC et sante.',
                'filieres' => [
                    'Theologie protestante et sciences religieuses (FTPSR)',
                    'Sciences sociales et relations internationales (FSSRI)',
                    'Technologies de l information et de la communication (FTIC)',
                    'Sciences de la sante (FSS)',
                ],
            ],
            [
                'code' => 'AUC',
                'nom' => 'Universite adventiste Cosendai - Nanga-Eboko',
                'type' => 'universite_privee',
                'ville' => 'Nanga-Eboko',
                'region' => 'Centre',
                'site_web' => 'https://aucadventist.edu',
                'description' => 'Universite adventiste proposant theologie, education, sciences sociales, gestion, informatique et sciences de la sante.',
                'filieres' => [
                    'Theologie',
                    'Education',
                    'Sciences sociales et gestion',
                    'Gestion, finances et informatique',
                    'Sciences de la sante',
                ],
            ],
            [
                'code' => 'UEQ',
                'nom' => 'Universite de l Equateur - Ebolowa',
                'type' => 'universite_privee',
                'ville' => 'Ebolowa',
                'region' => 'Sud',
                'site_web' => 'https://udeuniv-edu.com',
                'description' => 'Universite privee a vocation agricole et environnementale.',
                'filieres' => [
                    'Agriculture',
                    'Elevage',
                    'Foresterie',
                    'Environnement',
                    'Gestion',
                ],
            ],
            [
                'code' => 'UIJP2',
                'nom' => 'Universite internationale Jean-Paul II - Bafang',
                'type' => 'universite_privee',
                'ville' => 'Bafang',
                'region' => 'Ouest',
                'site_web' => 'https://polytechnique.cm',
                'description' => 'Universite privee d inspiration catholique; site officiel non disponible dans les donnees fournies.',
                'filieres' => [
                    'Sciences sociales',
                    'Economie',
                    'Gestion',
                    'Humanites',
                    'Droit',
                ],
            ],
            [
                'code' => 'ENAM',
                'nom' => 'ENAM - Ecole nationale d administration et de magistrature',
                'type' => 'ecole_professionnelle',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://enam.cm',
                'description' => 'Grande ecole publique de formation administrative et judiciaire.',
                'filieres' => [
                    'Carrieres administratives',
                    'Carrieres financieres',
                    'Douanes',
                    'Magistrature',
                    'Diplomatie',
                ],
            ],
            [
                'code' => 'ENSPT',
                'nom' => 'ENSPT - Ecole nationale superieure des postes et telecommunications',
                'type' => 'ecole_professionnelle',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => null,
                'description' => 'Grande ecole specialisee en telecommunications, technologies de l information, postes et reseaux.',
                'filieres' => [
                    'Telecommunications et technologies de l information',
                    'Gestion des postes et reseaux',
                ],
            ],
            [
                'code' => 'ENSTP',
                'nom' => 'ENSTP - Ecole nationale superieure des travaux publics',
                'type' => 'ecole_professionnelle',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://enstp.cm',
                'description' => 'Grande ecole specialisee en travaux publics et genie civil.',
                'filieres' => [
                    'Genie civil',
                    'Travaux publics',
                    'Genie urbain',
                    'Geotechnique',
                    'Geometre-topographe',
                ],
            ],
            [
                'code' => 'ENSPY',
                'nom' => 'ENSPY - Ecole nationale superieure polytechnique de Yaounde',
                'type' => 'ecole_professionnelle',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => null,
                'description' => 'Grande ecole polytechnique orientee ingenierie.',
                'filieres' => [
                    'Genie civil',
                    'Ingenierie mecanique',
                    'Genie electrique',
                    'Genie informatique',
                    'Genie industriel',
                    'Genie chimique et environnement',
                    'Sciences et genie des materiaux',
                ],
            ],
            [
                'code' => 'INJS',
                'nom' => 'INJS - Institut national de la jeunesse et des sports',
                'type' => 'institut',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://injs.cm',
                'description' => 'Institut public de formation en activites sportives, education physique et management du sport.',
                'filieres' => [
                    'Sciences et techniques des activites sportives',
                    'Education physique',
                    'Management du sport',
                ],
            ],
            [
                'code' => 'IRIC',
                'nom' => 'IRIC - Institut des relations internationales du Cameroun',
                'type' => 'institut',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://www.iricuy2.com',
                'description' => 'Institut specialise en relations internationales, diplomatie et securite internationale.',
                'filieres' => [
                    'Commerce international et diplomatie economique',
                    'Francophonie, relations internationales et strategiques',
                    'Integration regionale et management des institutions communautaires',
                    'Communication internationale et action publique',
                    'Diplomatie et cooperation internationale',
                    'Paix, securite et integration regionale',
                    'Cycle de stage diplomatique et protocolaire',
                ],
            ],
            [
                'code' => 'IFORD',
                'nom' => 'IFORD - Institut de formation et de recherche demographiques',
                'type' => 'institut',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://iford-cm.org',
                'description' => 'Institut specialise en demographie et statistiques appliquees.',
                'filieres' => [
                    'Master professionnel en demographie',
                    'Doctorat/PhD en population, developpement et ressources humaines',
                    'Suivi-evaluation des projets et programmes',
                    'Donnees spatiales appliquees a la population et au developpement',
                ],
            ],
            [
                'code' => 'IUTFV',
                'nom' => 'IUT Fotso Victor - Bandjoun',
                'type' => 'institut',
                'ville' => 'Bandjoun',
                'region' => 'Ouest',
                'site_web' => 'https://iutfv.univ-dschang.org',
                'description' => 'Institut universitaire de technologie rattache a l Universite de Dschang.',
                'filieres' => [
                    'Genie civil',
                    'Genie electrique',
                    'Informatique',
                    'Genie mecanique et productique',
                    'Mecatronique automobile',
                    'Maintenance industrielle et productique',
                    'Soudure industrielle',
                    'Techniques de commercialisation',
                    'Comptabilite et gestion des entreprises',
                ],
            ],
            [
                'code' => 'ENSAI',
                'nom' => 'ENSAI - Ecole nationale superieure des sciences agro-industrielles',
                'aliases' => ['ENSAI- Ecole nationale superieure des sciences agro-industrielles'],
                'type' => 'ecole_professionnelle',
                'ville' => 'Ngaoundere',
                'region' => 'Adamaoua',
                'site_web' => null,
                'description' => 'Grande ecole agro-industrielle rattachee a l Universite de Ngaoundere.',
                'filieres' => [
                    'Genie alimentaire',
                    'Genie des procedes',
                    'Industries agroalimentaires',
                ],
            ],
            [
                'code' => 'ENSPM',
                'nom' => 'ENSPM - Ecole nationale superieure polytechnique de Maroua',
                'type' => 'ecole_professionnelle',
                'ville' => 'Maroua',
                'region' => 'Nord',
                'site_web' => null,
                'description' => 'Grande ecole d ingenierie et de mines a Maroua.',
                'filieres' => [
                    'Genie civil',
                    'Genie electrique',
                    'Genie mecanique',
                    'Mines',
                    'Industries petrolieres',
                ],
            ],
            [
                'code' => 'IAI',
                'nom' => 'IAI-Cameroun - Institut Africain d Informatique',
                'type' => 'institut',
                'ville' => 'Yaounde',
                'region' => 'Centre',
                'site_web' => 'https://iaicameroun.com',
                'logo' => 'images/etablissements/iai-cameroun.png',
                'photos' => [
                    'images/etablissements/iai-cameroun.png',
                ],
                'description' => 'Representation camerounaise de l Institut Africain d Informatique, etablissement inter-Etats d enseignement superieur specialise en informatique.',
                'filieres' => [
                    'Licence professionnelle en informatique',
                    'Master professionnel en informatique',
                    'Cycle ingenieur des travaux informatiques',
                    'Cycle ingenieur de conception informatique',
                    'Formation continue en informatique',
                ],
            ],
        ];
    }
}
