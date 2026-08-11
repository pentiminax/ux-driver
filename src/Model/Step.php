<?php

declare(strict_types=1);

namespace Pentiminax\UX\Driver\Model;

final readonly class Step
{
    public function __construct(
        private string $element,
        private string $title,
        private ?string $description = null,
        private string $side = 'bottom',
        private string $align = 'start',
    ) {
    }

    public function getElement(): string
    {
        return $this->element;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSide(): string
    {
        return $this->side;
    }

    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'element' => $this->element,
            'popover' => array_filter([
                'title'       => $this->title,
                'description' => $this->description,
                'side'        => $this->side,
                'align'       => $this->align,
            ], static fn ($v) => null !== $v),
        ];
    }
}
