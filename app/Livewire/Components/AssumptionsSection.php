<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\DataTransferObjects\UserPreferencesDTO;
use Livewire\Attributes\Prop;
use Livewire\Component;

class AssumptionsSection extends Component
{
    #[Prop]
    public ?string $userNotes = null;

    /**
     * @var array<string, mixed>
     */
    #[Prop]
    public array $preferences = [];

    /**
     * Get preferences DTO.
     */
    public function getPreferencesDto(): ?UserPreferencesDTO
    {
        if (empty($this->preferences)) {
            return null;
        }

        return UserPreferencesDTO::fromArray($this->preferences);
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.assumptions-section');
    }
}
