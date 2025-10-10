<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Feedback DTO
 *
 * Data Transfer Object for plan feedback.
 */
readonly class FeedbackDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param  array<int, string>|null  $issues
     */
    public function __construct(
        public bool $satisfied,
        public ?array $issues = null,
        public ?string $otherComment = null,
    ) {}

    /**
     * Create DTO from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            satisfied: $data['satisfied'],
            issues: $data['issues'] ?? null,
            otherComment: $data['other_comment'] ?? null,
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'satisfied' => $this->satisfied,
            'issues' => $this->issues,
            'other_comment' => $this->otherComment,
        ];
    }
}
