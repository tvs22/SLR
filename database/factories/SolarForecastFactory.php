<?php

namespace Database\Factories;

use App\SolarForecast;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SolarForecastFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SolarForecast::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->date(),
            'hour' => $this->faker->numberBetween(0, 23),
            'kwh' => $this->faker->randomFloat(2, 0, 10),
        ];
    }
}
