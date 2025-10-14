<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Livewire\Profile\UpdatePreferencesForm;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_form_loads_existing_data(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura', 'gastronomia'],
            'travel_pace' => 'spokojne',
            'budget_level' => 'ekonomiczny',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        Livewire::actingAs($user)
            ->test(UpdatePreferencesForm::class)
            ->assertSet('interestCategories', ['historia_kultura', 'gastronomia'])
            ->assertSet('travelPace', 'spokojne')
            ->assertSet('budgetLevel', 'ekonomiczny');
    }

    public function test_can_update_preferences(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        UserPreference::create([
            'user_id' => $user->id,
            'interests_categories' => ['historia_kultura'],
            'travel_pace' => 'spokojne',
            'budget_level' => 'ekonomiczny',
            'transport_preference' => 'pieszo_publiczny',
            'restrictions' => 'brak',
        ]);

        Livewire::actingAs($user)
            ->test(UpdatePreferencesForm::class)
            ->set('interestCategories', ['gastronomia', 'nocne_zycie'])
            ->set('travelPace', 'intensywne')
            ->set('budgetLevel', 'premium')
            ->set('transportPreference', 'wynajem_auta')
            ->set('restrictions', 'dieta')
            ->call('updatePreferences')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'travel_pace' => 'intensywne',
            'budget_level' => 'premium',
        ]);
    }

    public function test_validation_requires_at_least_one_interest(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);

        Livewire::actingAs($user)
            ->test(UpdatePreferencesForm::class)
            ->set('interestCategories', [])
            ->set('travelPace', 'spokojne')
            ->set('budgetLevel', 'ekonomiczny')
            ->set('transportPreference', 'pieszo_publiczny')
            ->set('restrictions', 'brak')
            ->call('updatePreferences')
            ->assertHasErrors(['interestCategories' => 'required']);
    }
}
