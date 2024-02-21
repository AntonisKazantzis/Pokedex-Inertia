<?php

namespace App\Console\Commands;

use App\Models\Pokemon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncPokemonData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-pokemon-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from the Pokemon API.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Make API request using Laravel HTTP client
            $response = Http::get('https://pokeapi.co/api/v2/pokemon?limit=20&offset=0');

            // Process and use the synced data as needed
            $pokemonList = $response->json()['results'];

            foreach ($pokemonList as $pokemonData) {
                // Chain requests for each Pokemon
                $pokemonDetails = Http::get($pokemonData['url'])->json();
                $pokemonSpecies = Http::get($pokemonDetails['species']['url'])->json();
                $evolutionChain = Http::get($pokemonSpecies['evolution_chain']['url'])->json();

                // Process stats
                $filteredStats = array_map(function ($stat) {
                    return [
                        'base_stat' => $stat['base_stat'],
                        'name' => $stat['stat']['name'],
                    ];
                }, $pokemonDetails['stats']);

                // Prepare data for creation or update
                $pokemonData = [
                    'name' => $pokemonDetails['name'],
                    'sprite_1_path' => $pokemonDetails['sprites']['front_default'],
                    'sprite_2_path' => $pokemonDetails['sprites']['other']['dream_world']['front_default'],
                    'height' => $pokemonDetails['height'],
                    'weight' => $pokemonDetails['weight'],
                    'types' => json_encode(collect($pokemonDetails['types'])->pluck('type.name')->toArray()),
                    'stats' => json_encode($filteredStats),
                    'abilities' => json_encode(collect($pokemonDetails['abilities'])->pluck('ability.name')->toArray()),
                    'egg_groups' => json_encode(collect($pokemonSpecies['egg_groups'])->pluck('name')->toArray()),
                    'genera' => $pokemonSpecies['genera'][7]['genus'] ?? null,
                    'growth_rate' => $pokemonSpecies['growth_rate']['name'],
                    'evolution_chain' => json_encode(collect($evolutionChain['chain']['evolves_to'])->pluck('species.name')->toArray()),
                ];

                Pokemon::updateOrCreate(
                    ['pokemon_id' => $pokemonDetails['id']],
                    $pokemonData
                );
            }

            $this->info('Pokemon data synced successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}