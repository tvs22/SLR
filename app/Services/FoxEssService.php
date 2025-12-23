<?php

namespace App\Services;

use App\BatterySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FoxEssService
{
    public function setForcedChargeorDischarge(bool $enable, int $startHour, string $workMode): void
    {
        $schedulerData = $this->getFoxEssScheduler();

        if (
            !$schedulerData ||
            !isset($schedulerData['result']['groups']) ||
            !is_array($schedulerData['result']['groups'])
        ) {
            throw new RuntimeException('Unable to retrieve FoxESS scheduler groups');
        }

        $groups = $schedulerData['result']['groups'];
        $policyIndex = null;

        foreach ($groups as $index => $policy) {
            if (
                ($policy['workMode'] ?? null) === $workMode &&
                isset($policy['startHour']) &&
                (int) $policy['startHour'] === $startHour
            ) {
                $policyIndex = $index;
                break;
            }
        }

        if ($policyIndex === null) {
            if ($enable) {
                 throw new RuntimeException('No matching ' . $workMode . ' policy found for hour ' . $startHour);
            } else {
                return;
            }
        }

        $currentEnable = (int) ($groups[$policyIndex]['enable'] ?? 0);
        $desiredEnable = $enable ? 1 : 0;

        // Do nothing if already in desired state
        if ($currentEnable === $desiredEnable) {
            return;
        }

        $groups[$policyIndex]['enable'] = $desiredEnable;

        $response = $this->setFoxEssScheduler($groups);

        if (!isset($response['errno']) || $response['errno'] !== 0) {
            Log::error('FoxESS scheduler update failed', $response ?? []);
            throw new RuntimeException('Failed to update FoxESS scheduler');
        }
    }

    private function getFoxEssScheduler(): ?array
    {
        $token = env('FOX_ESS_API_KEY');
        $deviceSN = env('FOX_ESS_DEVICE_SN');

        if (!$token || !$deviceSN) {
            return null;
        }

        $path = '/op/v2/device/scheduler/get';
        $body = json_encode(['deviceSN' => $deviceSN]);
        $headers = $this->getFoxEssSignature($token, $path, $body);

        try {
            $response = Http::withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post('https://www.foxesscloud.com' . $path);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('FoxESS get scheduler error: ' . $e->getMessage());
            return null;
        }
    }

    private function setFoxEssScheduler(array $groups): ?array
    {
        $token = env('FOX_ESS_API_KEY');
        $deviceSN = env('FOX_ESS_DEVICE_SN');

        if (!$token || !$deviceSN) {
            return null;
        }

        $path = '/op/v2/device/scheduler/enable';
        $body = json_encode([
            'deviceSN' => $deviceSN,
            'groups' => $groups,
        ]);

        $headers = $this->getFoxEssSignature($token, $path, $body);

        try {
            $response = Http::withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post('https://www.foxesscloud.com' . $path);

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('FoxESS set scheduler error: ' . $e->getMessage());
            return null;
        }
    }

    private function getFoxEssSignature(
        string $token,
        string $path,
        string $body,
        string $lang = 'en'
    ): array {
        $timestamp = (string) floor(microtime(true) * 1000);
        $signatureRaw = implode('\r\n', [$path, $token, $timestamp]);
        $signature = md5($signatureRaw);

        return [
            'token' => $token,
            'signature' => $signature,
            'timestamp' => $timestamp,
            'lang' => $lang,
            'Content-Type' => 'application/json',
        ];
    }
}
