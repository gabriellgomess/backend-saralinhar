<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CityGeocode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeocodeCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:geocode {--limit=100 : Limite de cidades a geocodificar nesta execução}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocodifica cidades dos candidatos utilizando a API do OpenStreetMap Nominatim e armazena em cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info("Iniciando geocodificação de cidades...");

        // Busca cidades únicas dos candidatos agrupadas por contagem (mais populosas primeiro)
        $uniqueCities = Candidate::select('city', \DB::raw('count(*) as count'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->get();

        $cachedCities = CityGeocode::pluck('city')->toArray();
        $cachedCitiesMap = array_flip($cachedCities);

        $missingCities = [];
        foreach ($uniqueCities as $item) {
            $city = trim($item->city);
            if (empty($city)) continue;

            if (!isset($cachedCitiesMap[$city])) {
                $missingCities[] = [
                    'city' => $city,
                    'count' => $item->count
                ];
            }
        }

        $totalMissing = count($missingCities);
        if ($totalMissing === 0) {
            $this->info("Todas as cidades já estão geocodificadas no banco!");
            return 0;
        }

        $this->info("Encontradas {$totalMissing} cidades pendentes de geocodificação.");
        $toProcess = array_slice($missingCities, 0, $limit);
        $this->info("Processando as primeiras " . count($toProcess) . " cidades (ordenadas por densidade de candidatos)...");

        $geocodedCount = 0;
        $failedCount = 0;

        foreach ($toProcess as $index => $item) {
            $city = $item['city'];
            $count = $item['count'];
            $progress = $index + 1;
            $totalToProcess = count($toProcess);

            $this->output->write("({$progress}/{$totalToProcess}) Geocodificando: '{$city}' ({$count} candidatos)... ");

            $result = $this->geocodeCity($city);

            if ($result && $result->latitude !== null && $result->longitude !== null) {
                $this->info("Sucesso! ({$result->latitude}, {$result->longitude})");
                $geocodedCount++;
            } else {
                $this->error("Falha (salvo como nulo no cache)");
                $failedCount++;
            }

            // Respeitar o limite de taxa da API Nominatim do OSM (1 requisição por segundo)
            if ($index < $totalToProcess - 1) {
                sleep(1);
            }
        }

        $this->info("\nConcluído! {$geocodedCount} cidades geocodificadas com sucesso, {$failedCount} falhas.");
        return 0;
    }

    /**
     * Auxiliar para geocodificar uma cidade via OpenStreetMap Nominatim
     */
    private function geocodeCity($city)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $city . ', Brazil', // Força busca no Brasil
                    'format' => 'json',
                    'limit' => 1
                ],
                'headers' => [
                    'User-Agent' => 'SaraLinharMap/1.0 (contato@saralinhar.com.br)'
                ],
                'timeout' => 5
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (!empty($data) && isset($data[0])) {
                $lat = (float) $data[0]['lat'];
                $lon = (float) $data[0]['lon'];

                return CityGeocode::create([
                    'city' => $city,
                    'latitude' => $lat,
                    'longitude' => $lon
                ]);
            }

            // Cache negativo
            return CityGeocode::create([
                'city' => $city,
                'latitude' => null,
                'longitude' => null
            ]);
        } catch (\Exception $e) {
            Log::error("Console Geocoding failed for city: {$city}. Error: " . $e->getMessage());
            return null;
        }
    }
}
