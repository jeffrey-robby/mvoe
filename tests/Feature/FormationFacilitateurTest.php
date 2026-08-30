<?php

namespace Tests\Feature;

use App\Models\Facilitateur;
use App\Models\ModuleFormation;
use App\Models\ProgressionFormation;
use Illuminate\Support\Str;
use Tests\ApiTestCase;

/**
 * Les modules de formation du facilitateur.
 *
 * Deux catalogues distincts cohabitent : celui des parents et celui-ci. Le
 * second existe parce qu'un facilitateur formé il y a deux ans ne se refait pas
 * former — il rouvre ses modules, et ce faisant il rouvre l'application.
 *
 * Deux règles décident de tout ici : **un contenu non validé ne peut pas être
 * diffusé**, et **rouvrir un module est une activité**.
 */
class FormationFacilitateurTest extends ApiTestCase
{
    public function test_un_facilitateur_voit_ses_modules_et_en_ouvre_un(): void
    {
        // Critère d'acceptation n° 4 du brief.
        $jeton = $this->jetonFacilitateur();

        $modules = $this->getJson('/api/facilitateur/formation', $this->entete($jeton))
            ->assertOk()->json('modules');

        $this->assertCount(3, $modules);
        $this->assertSame(['FI-01', 'RN-01', 'CT-01'], array_column($modules, 'code'));

        $module = $this->getJson('/api/facilitateur/formation/CT-01', $this->entete($jeton))
            ->assertOk();

        $this->assertSame('Conduite à tenir', $module->json('type_libelle'));
        $this->assertCount(4, $module->json('sections'));
        $this->assertNotEmpty($module->json('sections.0.contenu_texte'));
    }

    public function test_un_module_non_valide_natteint_jamais_un_facilitateur(): void
    {
        // « Un contenu non validé ne peut pas être diffusé. Contrôle dans le
        // code, pas consigne dans une doc. » Le module RN-02 est en attente de
        // validation : il n'existe pas pour le facilitateur.
        $jeton = $this->jetonFacilitateur();

        $this->assertNotNull(ModuleFormation::where('code', 'RN-02')->first(),
            'Le jeu de démonstration doit contenir un module non validé.');

        $codes = array_column(
            $this->getJson('/api/facilitateur/formation', $this->entete($jeton))
                ->assertOk()->json('modules'),
            'code',
        );

        $this->assertNotContains('RN-02', $codes);

        // Ni par son adresse directe : un identifiant d'URL n'est pas une
        // autorisation.
        $this->getJson('/api/facilitateur/formation/RN-02', $this->entete($jeton))
            ->assertNotFound();
    }

    public function test_un_module_non_valide_nentre_pas_dans_le_paquet_hors_ligne(): void
    {
        // Le paquet est l'autre porte par laquelle un module atteint le
        // terrain. La règle doit y valoir aussi, sinon elle ne vaut rien.
        $paquet = $this->getJson('/api/facilitateur/cohortes/1/paquet',
            $this->entete($this->jetonFacilitateur()))->assertOk();

        $codes = array_column($paquet->json('formation'), 'code');

        $this->assertCount(3, $codes);
        $this->assertNotContains('RN-02', $codes);

        // Et le texte y est : on révise sans réseau.
        $this->assertNotEmpty($paquet->json('formation.0.sections.0.contenu_texte'));
    }

    public function test_rouvrir_un_module_rend_le_facilitateur_actif(): void
    {
        // C'est le point du brief : « il rouvre ses modules. Ce faisant, il
        // rouvre l'application, donc il reste actif dans le registre. »
        $facilitateur = $this->facilitateur();
        $facilitateur->forceFill(['derniere_activite' => null])->save();

        $this->assertFalse($facilitateur->fresh()->estActif());

        $this->envoyer([$this->progression('CT-01', [1])]);

        $this->assertTrue($facilitateur->fresh()->estActif());
    }

    public function test_la_progression_sajoute_et_ne_se_remplace_jamais(): void
    {
        // Une remontée tardive ne doit pas effacer une lecture plus récente :
        // les sections vues fusionnent.
        $this->envoyer([$this->progression('CT-01', [3, 4])]);
        $this->envoyer([$this->progression('CT-01', [1])]);

        $progression = $this->progressionDe('CT-01');

        $this->assertSame([1, 3, 4], $progression->sections_vues);
        $this->assertFalse($progression->estTermine());
    }

    public function test_un_module_est_termine_quand_toutes_ses_sections_sont_lues(): void
    {
        // Terminé se constate, il ne se déclare pas : il les a lues, cela suffit.
        $this->envoyer([$this->progression('RN-01', [1, 2, 3])]);

        $progression = $this->progressionDe('RN-01');

        $this->assertTrue($progression->estTermine());
        $this->assertSame(100, $progression->avancement(3));
    }

    public function test_aucune_progression_ne_sinscrit_sur_un_module_non_valide(): void
    {
        $reponse = $this->envoyer([$this->progression('RN-02', [1])]);

        $this->assertSame([], $reponse->json('acceptes'));
        $this->assertSame(0, ProgressionFormation::whereHas('module',
            fn ($q) => $q->where('code', 'RN-02'))->count());
    }

    public function test_le_superviseur_voit_ou_en_est_son_facilitateur(): void
    {
        // Ce n'est pas de la surveillance : c'est la seule façon de repérer qui
        // décroche avant qu'il ne disparaisse du registre.
        $ligne = collect($this->getJson('/api/superviseur/facilitateurs',
            $this->entete($this->jetonSuperviseurArrondissement()))->assertOk()
            ->json('facilitateurs'))->firstWhere('nom', 'Ndzana Étienne');

        // Un module terminé, un commencé, un jamais ouvert.
        $this->assertSame(2, $ligne['modules_ouverts']);
        $this->assertSame(1, $ligne['modules_termines']);
        $this->assertNotNull($ligne['derniere_formation']);
    }

    public function test_la_progression_remonte_par_la_file_comme_le_reste(): void
    {
        // On révise dans un car, sur un banc : aucune route n'écrit une
        // progression, elle passe par la file d'événements.
        $routes = collect(app('router')->getRoutes())
            ->filter(fn ($r) => str_contains($r->uri(), 'formation'))
            ->map(fn ($r) => implode('|', $r->methods()).' '.$r->uri())
            ->values();

        foreach ($routes as $route) {
            $this->assertStringStartsWith('GET', $route,
                "« $route » : la progression s'écrit par la file, pas par une route.");
        }
    }

    /* ---------------------------------------------------------------------- */

    private function facilitateur(): Facilitateur
    {
        return Facilitateur::where('nom', 'Ndzana Étienne')->firstOrFail();
    }

    private function progressionDe(string $code): ProgressionFormation
    {
        return ProgressionFormation::where('facilitateur_id', $this->facilitateur()->id)
            ->whereHas('module', fn ($q) => $q->where('code', $code))
            ->firstOrFail();
    }

    private function envoyer(array $evenements)
    {
        return $this->postJson('/api/facilitateur/evenements', ['evenements' => $evenements],
            $this->entete($this->jetonFacilitateur()))->assertAccepted();
    }

    private function progression(string $code, array $sections): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => 'progression_formation',
            'seance_uuid' => null,
            'emis_a' => now()->toIso8601String(),
            'charge' => [
                'module_code' => $code,
                'sections_vues' => $sections,
                'ouverte_a' => now()->toIso8601String(),
            ],
        ];
    }
}
