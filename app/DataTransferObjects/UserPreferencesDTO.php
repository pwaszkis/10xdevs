<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * User Preferences DTO
 *
 * Data Transfer Object for user travel preferences.
 */
readonly class UserPreferencesDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param  array<int, string>  $interestsCategories
     */
    public function __construct(
        public array $interestsCategories = [],
        public ?string $travelPace = null,
        public ?string $budgetLevel = null,
        public ?string $transportPreference = null,
        public ?string $restrictions = null,
    ) {}

    /**
     * Create DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            interestsCategories: $data['interests_categories'] ?? [],
            travelPace: $data['travel_pace'] ?? null,
            budgetLevel: $data['budget_level'] ?? null,
            transportPreference: $data['transport_preference'] ?? null,
            restrictions: $data['restrictions'] ?? null,
        );
    }

    /**
     * Get readable category names.
     *
     * @return array<int, string>
     */
    public function getReadableCategories(): array
    {
        return collect($this->interestsCategories)
            ->map(fn ($cat) => match ($cat) {
                'historia_kultura' => 'Historia i kultura',
                'przyroda_outdoor' => 'Przyroda i outdoor',
                'gastronomia' => 'Gastronomia',
                'nocne_zycie' => 'Nocne życie i rozrywka',
                'plaze_relaks' => 'Plaże i relaks',
                'sporty_aktywnosci' => 'Sporty i aktywności',
                'sztuka_muzea' => 'Sztuka i muzea',
                default => $cat,
            })
            ->toArray();
    }

    /**
     * Get readable travel pace label.
     */
    public function getReadableTravelPace(): ?string
    {
        return match ($this->travelPace) {
            'spokojne' => 'Spokojne',
            'umiarkowane' => 'Umiarkowane',
            'intensywne' => 'Intensywne',
            default => null,
        };
    }

    /**
     * Get readable budget level label.
     */
    public function getReadableBudgetLevel(): ?string
    {
        return match ($this->budgetLevel) {
            'ekonomiczny' => 'Ekonomiczny',
            'standardowy' => 'Standardowy',
            'premium' => 'Premium',
            default => null,
        };
    }

    /**
     * Get readable transport preference label.
     */
    public function getReadableTransportPreference(): ?string
    {
        return match ($this->transportPreference) {
            'pieszo_publiczny' => 'Pieszo i transport publiczny',
            'wynajem_auta' => 'Wynajem auta',
            'mix' => 'Mix',
            default => null,
        };
    }

    /**
     * Get readable restrictions label.
     */
    public function getReadableRestrictions(): ?string
    {
        return match ($this->restrictions) {
            'brak' => 'Brak',
            'dieta' => 'Dieta (wegetariańska/wegańska)',
            'mobilnosc' => 'Mobilność (dostępność)',
            default => null,
        };
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'interests_categories' => $this->interestsCategories,
            'travel_pace' => $this->travelPace,
            'budget_level' => $this->budgetLevel,
            'transport_preference' => $this->transportPreference,
            'restrictions' => $this->restrictions,
        ];
    }
}
