<?php

declare(strict_types=1);

namespace Pu239\Forum\Requests;

use InvalidArgumentException;
use function array_key_exists;
use function mb_strlen;
use function sprintf;
use function trim;

final class StoreTopicRequest
{
    /**
     * @return array{title:string,body_md:string}
     */
    public function validate(array $input): array
    {
        $title = $this->validateString($input, 'title', 3, 140);
        $bodyMd = $this->validateString($input, 'body_md', 3, null);

        return [
            'title' => $title,
            'body_md' => $bodyMd,
        ];
    }

    private function validateString(array $input, string $key, int $min, ?int $max): string
    {
        if (! array_key_exists($key, $input)) {
            throw new InvalidArgumentException(sprintf('Missing required field: %s', $key));
        }

        $value = trim((string) $input[$key]);
        $length = mb_strlen($value);

        if ($length < $min) {
            throw new InvalidArgumentException(sprintf('Field %s must be at least %d characters.', $key, $min));
        }

        if ($max !== null && $length > $max) {
            throw new InvalidArgumentException(sprintf('Field %s must be at most %d characters.', $key, $max));
        }

        return $value;
    }
}
