<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class AmberService
{
    public function getLatestPrices(): array
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');
        $electricityPrice = null;
        $solarPrice = null;

        if ($apiKey && $siteId && $siteId !== 'your_site_id') {
            try {
                $url = "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=0&previous=0&resolution=5";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ])->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    $currentPriceData = collect($data)->firstWhere('type', 'CurrentInterval');

                    if ($currentPriceData && $currentPriceData['channelType'] === 'general') {
                        $electricityPrice = $currentPriceData['perKwh'];
                        $solarPrice = $currentPriceData['spotPerKwh'];
                    }
                } else {
                    Log::error('Error fetching Amber Electric data: HTTP Status ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching Amber Electric data: ' . $e->getMessage());
            }
        }

        return [
            'electricityPrice' => $electricityPrice,
            'solarPrice' => $solarPrice,
        ];
    }
}