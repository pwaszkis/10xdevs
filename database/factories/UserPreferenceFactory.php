<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UserPreference>
     */
    protected $model = UserPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'language' => 'pl',
            'timezone' => 'Europe/Warsaw',
            'currency' => 'PLN',
            'notifications_enabled' => true,
            'email_notifications' => true,
            'push_notifications' => false,
            'theme' => 'auto',
            'interests_categories' => fake()->randomElements(['historia_kultura', 'przyroda_outdoor', 'gastronomia', 'nocne_zycie', 'plaze_relaks', 'sporty_aktywnosci', 'sztuka_muzea'], fake()->numberBetween(2, 4)),
            'travel_pace' => fake()->randomElement(['spokojne', 'umiarkowane', 'intensywne']),
            'budget_level' => fake()->randomElement(['ekonomiczny', 'standardowy', 'premium']),
            'transport_preference' => fake()->randomElement(['pieszo_publiczny', 'wynajem_auta', 'mix']),
            'restrictions' => fake()->randomElement(['brak', 'dieta', 'mobilnosc']),
        ];
    }
}
