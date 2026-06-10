<?php

namespace App\Services\Wizard;

use InvalidArgumentException;

/*
|--------------------------------------------------------------------------
| WizardFlow
|--------------------------------------------------------------------------
| Immutable view over a single applicant flow defined in config/wizard.php.
| Answers structural questions (step order, neighbours, completeness) so
| the controller never pokes at raw config arrays.
*/

class WizardFlow
{
    /** @param array<int,array{key:string,label:string,view:string}> $steps */
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly array $steps,
    ) {}

    /** Build a flow from the wizard config, or fail for an unknown type. */
    public static function make(string $type): self
    {
        $config = config("wizard.$type");

        if (! $config) {
            throw new InvalidArgumentException("Unknown applicant type [$type].");
        }

        return new self($type, $config['label'], $config['steps']);
    }

    /** All valid applicant type slugs. */
    public static function types(): array
    {
        return array_keys(config('wizard', []));
    }

    public static function isValidType(?string $type): bool
    {
        return $type !== null && in_array($type, self::types(), true);
    }

    public function count(): int
    {
        return count($this->steps);
    }

    /** Ordered list of step keys. */
    public function keys(): array
    {
        return array_column($this->steps, 'key');
    }

    /** Ordered list of step labels (for the stepper). */
    public function labels(): array
    {
        return array_column($this->steps, 'label');
    }

    /** 1-indexed position of a step key, or null if absent. */
    public function positionOf(string $key): ?int
    {
        $idx = array_search($key, $this->keys(), true);
        return $idx === false ? null : $idx + 1;
    }

    public function hasStep(string $key): bool
    {
        return in_array($key, $this->keys(), true);
    }

    /** Step definition at a 1-indexed position. */
    public function stepAt(int $position): ?array
    {
        return $this->steps[$position - 1] ?? null;
    }

    public function stepByKey(string $key): ?array
    {
        foreach ($this->steps as $step) {
            if ($step['key'] === $key) {
                return $step;
            }
        }
        return null;
    }

    public function firstKey(): string
    {
        return $this->steps[0]['key'];
    }

    public function lastKey(): string
    {
        return $this->steps[$this->count() - 1]['key'];
    }

    public function isLast(string $key): bool
    {
        return $key === $this->lastKey();
    }

    public function isFirst(string $key): bool
    {
        return $key === $this->firstKey();
    }

    /** Key of the step before $key (null if first / unknown). */
    public function previousKey(string $key): ?string
    {
        $pos = $this->positionOf($key);
        if ($pos === null || $pos <= 1) {
            return null;
        }
        return $this->steps[$pos - 2]['key'];
    }

    /** Key of the step after $key (null if last / unknown). */
    public function nextKey(string $key): ?string
    {
        $pos = $this->positionOf($key);
        if ($pos === null || $pos >= $this->count()) {
            return null;
        }
        return $this->steps[$pos]['key'];
    }
}
