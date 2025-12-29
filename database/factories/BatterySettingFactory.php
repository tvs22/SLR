<?php

namespace Database\Factories;

use App\BatterySetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BatterySettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BatterySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'target_price_cents' => $this->faker->numberBetween(10, 50),
            'longterm_target_price_cents' => $this->faker->numberBetween(5, 25),
            'forced_discharge' => $this->faker->boolean(),
            'target_electric_price_cents' => $this->faker->numberBetween(10, 50),
            'longterm_target_electric_price_cents' => $this->faker->numberBetween(5, 25),
            'forced_charge' => $this->faker->boolean(),
            'battery_level_percent' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['prioritize_charging', 'prioritize_selling', 'self_sufficient']),
        ];
    }
}
