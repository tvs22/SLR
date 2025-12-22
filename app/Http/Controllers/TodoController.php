<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    /**
     * Fetches data from various APIs and displays the home view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function fetchTodo()
    {
        $amberData = $this->getAmberElectricData();
        $schedulerData = $this->getFoxEssScheduler();
        return view('home', [
            'electricityPrice' => $amberData['electricityPrice'],
            'solarPrice' => $amberData['solarPrice'],
            'scheduler' => $schedulerData,
            'deviceSN' => env('FOX_ESS_DEVICE_SN'),
        ]);
    }

    /**
     * Toggles the first scheduler policy on or off.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleScheduler(Request $request)
    {
        $validatedData = $request->validate([
            'deviceSN' => 'required|string',
            'enable' => 'required|boolean',
        ]);

        $schedulerData = $this->getFoxEssScheduler();

        if (!$schedulerData || !isset($schedulerData['result']['groups'])) {
            return response()->json(['message' => 'Could not retrieve scheduler policies to update.'], 500);
        }

        $groups = $schedulerData['result']['groups'];

        if (empty($groups[0][0])) {
             return response()->json(['message' => 'No policies found to update.'], 500);
        }

        // Update the enable flag on the first policy of the first group (0 for disable, 1 for enable)
        $groups[0][0]['enable'] = $validatedData['enable'] ? 1 : 0;

        $response = $this->setFoxEssScheduler(
            $validatedData['deviceSN'],
            $groups
        );

        if ($response && isset($response['errno']) && $response['errno'] === 0) {
            return response()->json(['message' => 'Scheduler updated successfully.']);
        } else {
            $errorMessage = $response['msg'] ?? 'Failed to update scheduler.';
            Log::error('Error setting FoxESS scheduler: ' . json_encode($response));
            return response()->json(['message' => $errorMessage], 500);
        }
    }

    /**
     * Fetches current electricity and solar prices from Amber Electric.
     *
     * @return array
     */
    private function getAmberElectricData(): array
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

     /**
     * Fetches the scheduler data from FoxESS.
     *
     * @return array|null
     */
    private function getFoxEssScheduler(): ?array
    {
        $foxApiKey = env('FOX_ESS_API_KEY');
        $deviceSN = env('FOX_ESS_DEVICE_SN');
        if (!$foxApiKey || !$deviceSN) {
            return null;
        }
        try {
            $path = '/op/v2/device/scheduler/get';
            $body = json_encode(['deviceSN' => $deviceSN]);
            $headers = $this->getFoxEssSignature($foxApiKey, $path, $body);
            $response = Http::withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post('https://www.foxesscloud.com' . $path);
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error fetching FoxESS scheduler: HTTP Status ' . $response->status() . ' Body: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching FoxESS scheduler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sets the scheduler policies for a FoxESS device.
     *
     * @param string $deviceSN
     * @param array $groups
     * @return array|null
     */
    private function setFoxEssScheduler(string $deviceSN, array $groups): ?array
    {
        $foxApiKey = env('FOX_ESS_API_KEY');
        if (!$foxApiKey || !$deviceSN) {
            return null;
        }

        try {
            $path = '/op/v2/device/scheduler/set';
            $body = json_encode(['deviceSN' => $deviceSN, 'groups' => $groups]);
            $headers = $this->getFoxEssSignature($foxApiKey, $path, $body);

            $response = Http::withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post('https://www.foxesscloud.com' . $path);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error setting FoxESS scheduler: HTTP Status ' . $response->status() . ' Body: ' . $response->body());
                return $response->json() ?: null; // Return JSON error body if available
            }
        } catch (\Exception $e) {
            Log::error('Error setting FoxESS scheduler: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Generates the required signature for FoxESS API requests.
     *
     * @param string $token
     * @param string $path
     * @param string $body
     * @param string $lang
     * @return array
     */
    private function getFoxEssSignature(string $token, string $path, string $body, string $lang = 'en'): array
    {
        $timestamp = floor(microtime(true) * 1000);
        $signature = implode('\r\n', [$path, $token, $timestamp]);
        $signatureMd5 = md5($signature);

        return [
            'token' => $token,
            'signature' => $signatureMd5,
            'timestamp' => strval($timestamp),
            'lang' => $lang,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
    }
}
