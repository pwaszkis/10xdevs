<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <!-- Globe circle -->
    <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="2"/>

    <!-- Latitude lines -->
    <ellipse cx="50" cy="50" rx="45" ry="15" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
    <ellipse cx="50" cy="50" rx="45" ry="30" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>

    <!-- Longitude lines -->
    <ellipse cx="50" cy="50" rx="15" ry="45" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
    <ellipse cx="50" cy="50" rx="30" ry="45" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>

    <!-- Center meridian -->
    <line x1="50" y1="5" x2="50" y2="95" stroke="currentColor" stroke-width="2"/>

    <!-- Equator -->
    <line x1="5" y1="50" x2="95" y2="50" stroke="currentColor" stroke-width="2"/>

    <!-- Decorative continents (simplified) -->
    <path d="M 30 25 Q 35 20, 40 25 T 50 20 L 55 25 Q 50 30, 45 28 T 35 30 Z" fill="currentColor" opacity="0.3"/>
    <path d="M 60 40 Q 65 35, 70 40 L 75 38 Q 72 45, 68 43 T 62 48 Z" fill="currentColor" opacity="0.3"/>
    <path d="M 25 60 Q 30 55, 35 60 L 40 58 L 42 65 Q 35 68, 30 65 T 25 68 Z" fill="currentColor" opacity="0.3"/>
</svg>
