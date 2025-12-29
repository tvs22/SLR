<?php

namespace Tests\Feature;

use App\User;
use App\BatterySetting;
use App\Models\BatteryStrategy;
use App\Services\AmberService;
use App\Services\FoxEssService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Carbon\Carbon;
use App\SolarForecast;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function sell_strategy_is_calculated_with_amber_api_data()
    {
        // 1. Arrange
        Carbon::setTestNow(Carbon::create(2023, 10, 27, 18, 0, 0));

        $user = User::factory()->create();

        // Mock services
        $amberServiceMock = Mockery::mock(AmberService::class);
        $foxEssServiceMock = Mockery::mock(FoxEssService::class);

        $this->app->instance(AmberService::class, $amberServiceMock);
        $this->app->instance(FoxEssService::class, $foxEssServiceMock);

        // Create database records
        BatterySetting::factory()->create([
            'longterm_target_price_cents' => 25,
            'longterm_target_electric_price_cents' => 15,
        ]);

        BatteryStrategy::create([
            'name' => 'Evening Peak',
            'soc_lower_bound' => 50,
            'soc_upper_bound' => 100,
            'sell_start_time' => '17:00:00',
            'sell_end_time' => '21:00:00',
            'is_active' => true,
        ]);

        BatteryStrategy::create([
            'name' => 'Flexible Evening',
            'soc_lower_bound' => 30,
            'soc_upper_bound' => 80,
            'sell_start_time' => '17:00:00',
            'sell_end_time' => '23:00:00',
            'is_active' => true,
        ]);

        BatteryStrategy::create([
            'name' => 'Overnight',
            'soc_lower_bound' => 10,
            'soc_upper_bound' => 50,
            'sell_start_time' => '23:00:00',
            'sell_end_time' => '03:00:00',
            'is_active' => true,
        ]);

        BatteryStrategy::create([
            'name' => 'Flexible Late',
            'soc_lower_bound' => 20,
            'soc_upper_bound' => 70,
            'sell_start_time' => '21:00:00',
            'sell_end_time' => '23:00:00',
            'is_active' => true,
        ]);

        SolarForecast::factory()->create(['date' => '2023-10-27', 'hour' => 18, 'kwh' => 10]);

        // Define mock behavior
        $foxEssServiceMock->shouldReceive('getSoc')->andReturn(80);

        $expectedSellPlan = [
            'total_kwh_sold' => 5.0,
            'total_revenue' => 125,
            'highest_sell_price' => 30,
            'lowest_sell_price' => 20,
            'highest_sell_price_time' => '2023-10-27T19:00:00+11:00',
            'sell_plan' => [
                ['time' => '2023-10-27T19:00:00+11:00', 'price' => 30, 'kwh' => 2.5],
                ['time' => '2023-10-27T19:30:00+11:00', 'price' => 20, 'kwh' => 2.5],
            ],
            'error' => null,
            'message' => null,
        ];

        $amberServiceMock->shouldReceive('calculateOptimalDischarging')
            ->once()
            ->andReturn($expectedSellPlan);

        $amberServiceMock->shouldReceive('calculateOptimalCharging')->andReturn(['buy_plan' => []]);


        // 2. Act
        $response = $this->actingAs($user)->getJson('/api/price/predicted-prices');

        // 3. Assert
        $response->assertStatus(200);
        
        $response->assertJson([
            'sell_strategy' => $expectedSellPlan
        ]);
    }
}
