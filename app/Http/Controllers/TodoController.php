<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    /**
     * Display a form to input battery level.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function fetchTodo()
    {
        $apiKey = env('AMBER_ELECTRIC_API_KEY');
        $foxApiKey = env('FOX_ESS_API_KEY');
        $deviceSN = env('FOX_ESS_DEVICE_SN');
        $siteId = env('AMBER_ELECTRIC_SITE_ID');
        $client = new Client();
        $electricityPrice = null;
        $solarPrice = null;

        $path = '/op/v2/device/scheduler/get';
        
        // Generate a dynamic, 13-digit millisecond timestamp as a string
        $timestamp = (string)intval(microtime(true) * 1000);

        // The body for the POST request
        $body = json_encode(['deviceSN' => $deviceSN]);

        // As per the official documentation for a POST request, the signature MUST include the body.
        // Rule: md5(path + "\r\n" + token + "\r\n" + timestamp + "\r\n" + body)
        $signature = md5($path . "\r\n" . $foxApiKey . "\r\n" . $timestamp . "\r\n" . $body);

        $response = Http::withHeaders([
            'token' => $foxApiKey,
            'signature' => $signature,
            'timestamp' => $timestamp,
            'lang' => 'en',
        ])->withBody($body, 'application/json')
          ->post('https://www.foxesscloud.com' . $path);

        dd($response->json());
        
        if ($apiKey && $siteId && $siteId !== 'your_site_id') {
        try {
                       $response = $client->request('GET', "https://api.amber.com.au/v1/sites/{$siteId}/prices/current?next=0&previous=0&resolution=5", [
                           'headers' => [
                               'Authorization' => 'Bearer ' . $apiKey,
                               'Accept' => 'application/json',
                           ],
                       ]);
       
                       $data = json_decode($response->getBody(), true);
                       
                        // Find the general and feed-in prices in the response data
                        $currentPriceData = null;
                       foreach ($data as $item) {
                            if ($item['type'] === 'CurrentInterval') {
                                $currentPriceData = $item;
                                break;
                            }
                        }

                        if ($currentPriceData) {
                                              if ($currentPriceData['channelType'] === 'general') {
                                                   $electricityPrice = $currentPriceData['perKwh'];
                                                   $solarPrice = $currentPriceData['spotPerKwh'];
                                               } 
                                       }
        
            } catch (\Exception $e) {
                Log::error('Error fetching Amber Electric data: ' . $e->getMessage());
            }
        }
                    $isForceChargeOn = 'error'; // Default value
                    if ($foxApiKey && $deviceSN) {
                        $foxResponse = Http::withHeaders([
                            'X-Fox-API-Key' => $foxApiKey,
                            'Content-Type' => 'application/json'
                        ])->post('https://www.foxesscloud.com/c/v0/device/getDeviceVariables', [
                            'deviceSN' => $deviceSN,
                            'variables' => ['bat_force_charge']
                        ]);
                        if ($foxResponse->successful()) {
                            $foxData = $foxResponse->json();
                            if (isset($foxData['result']['bat_force_charge']['value'])) {
                                $isForceChargeOn = $foxData['result']['bat_force_charge']['value'];
                            } else {
                                $isForceChargeOn = 'unknown';
                            }
                        }
                    }
                
        
                return view('home', compact('electricityPrice', 'solarPrice','isForceChargeOn'));
        
    }
}
