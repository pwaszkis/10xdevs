<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Livewire\Attributes\Prop;
use Livewire\Component;

class PreferenceBadge extends Component
{
    #[Prop]
    public string $category;

    #[Prop]
    public ?string $icon = null;

    /**
     * Get category label.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            'historia_kultura' => 'Historia i kultura',
            'przyroda_outdoor' => 'Przyroda i outdoor',
            'gastronomia' => 'Gastronomia',
            'nocne_zycie' => 'Nocne życie i rozrywka',
            'plaze_relaks' => 'Plaże i relaks',
            'sporty_aktywnosci' => 'Sporty i aktywności',
            'sztuka_muzea' => 'Sztuka i muzea',
            default => $this->category,
        };
    }

    /**
     * Get category icon.
     */
    public function getCategoryIcon(): string
    {
        if ($this->icon) {
            return $this->icon;
        }

        return match ($this->category) {
            'historia_kultura' => '🏛️',
            'przyroda_outdoor' => '🏞️',
            'gastronomia' => '🍴',
            'nocne_zycie' => '🎉',
            'plaze_relaks' => '🏖️',
            'sporty_aktywnosci' => '⚽',
            'sztuka_muzea' => '🎨',
            default => '📍',
        };
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.preference-badge');
    }
}
