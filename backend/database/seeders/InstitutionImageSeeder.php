<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Associe les photos publiees par les etablissements ou disponibles sur
 * Wikimedia Commons. Les images restent hebergees par leur source publique.
 */
class InstitutionImageSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('etablissements')) {
            return;
        }

        foreach ($this->images() as $name => $image) {
            DB::table('etablissements')
                ->where('nom', $name)
                ->update([
                    'logo' => $image,
                    'photos' => json_encode([$image], JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Une source n'est ajoutee que lorsqu'elle correspond explicitement a l'etablissement.
     */
    private function images(): array
    {
        return [
            'ENAM - Ecole nationale d administration et de magistrature' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/ENAM_CAMEROUN.jpg/1280px-ENAM_CAMEROUN.jpg',
            'ENSPT - Ecole nationale superieure des postes et telecommunications' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Ecole_des_Postes_SUPP_TIC_Yaound%C3%A9_01.jpg/1280px-Ecole_des_Postes_SUPP_TIC_Yaound%C3%A9_01.jpg',
            'ENSTP - Ecole nationale superieure des travaux publics' => 'https://enstp.cm/wp-content/themes/propulus/images/school.jpg',
            'IFORD - Institut de formation et de recherche demographiques' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/IFORD_Yaound%C3%A9.jpg/1280px-IFORD_Yaound%C3%A9.jpg',
            'INJS - Institut national de la jeunesse et des sports' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/01/INJS_Yaound%C3%A9.jpg/1280px-INJS_Yaound%C3%A9.jpg',
            'IRIC - Institut des relations internationales du Cameroun' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5d/IRIC_Yaound%C3%A9.jpg/1280px-IRIC_Yaound%C3%A9.jpg',
            'Universite catholique d Afrique centrale (UCAC) - Yaounde' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8e/Universit%C3%A9_catholique_d%27Afrique_Centrale_Yaound%C3%A9.jpg/1280px-Universit%C3%A9_catholique_d%27Afrique_Centrale_Yaound%C3%A9.jpg',
            'Universite de Bamenda' => 'https://upload.wikimedia.org/wikipedia/commons/c/ce/Bambili%2C_university_of_Bamenda.jpg',
            'Universite de Bertoua' => 'https://univ-bertoua.cm/uploads/gallery/IMG_0854.JPG',
            'Universite de Buea (UB)' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7f/University_of_Buea_Campus_B.jpg/1280px-University_of_Buea_Campus_B.jpg',
            'Universite de Douala' => 'https://univ-douala.cm/images/univ.PNG',
            'Universite de Dschang (UDs)' => 'https://www.univ-dschang.org/wp-content/uploads/2024/09/1-scaled-2.jpg',
            'Universite de Maroua' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Le_rectorat_de_l%27%C3%A9cole_normale_sup%C3%A9rieur_de_Maroua.jpg/1280px-Le_rectorat_de_l%27%C3%A9cole_normale_sup%C3%A9rieur_de_Maroua.jpg',
            'Universite de Ngaoundere' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/dc/Universit%C3%A9_de_Ngaound%C3%A9r%C3%A9.jpg/1280px-Universit%C3%A9_de_Ngaound%C3%A9r%C3%A9.jpg',
        ];
    }
}
