<?php

declare(strict_types=1);

namespace Pu239\Forum\Requests;

use InvalidArgumentException;
use function array_key_exists;
use function mb_strlen;
use function sprintf;
use function trim;

final class UpdatePostRequest
{
    /**
     * @return array{body_md:string}
     */
    public function validate(array $input): array
    {
        $bodyMd = $this->validateBody($input, 'body_md');

        return ['body_md' => $bodyMd];
    }

    private function validateBody(array $input, string $key): string
    {
        if (! array_key_exists($key, $input)) {
            throw new InvalidArgumentException(sprintf('Missing required field: %s', $key));
        }

        $value = trim((string) $input[$key]);
        if (mb_strlen($value) < 3) {
            throw new InvalidArgumentException(sprintf('Field %s must be at least %d characters.', $key, 3));
        }

        return $value;
    }
}
