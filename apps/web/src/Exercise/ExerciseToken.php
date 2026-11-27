<?php

declare(strict_types=1);

namespace App\Exercise;

use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Jeton signe, autonome, valable deux heures. Ne contient aucune solution. */
final class ExerciseToken
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
    ) {
    }

    /** @param array<string, int|string> $parameters */
    public function issue(string $slug, int $version, array $parameters): string
    {
        $payload = json_encode([
            'slug' => $slug,
            'version' => $version,
            'parameters' => $parameters,
            'issued' => time(),
            'nonce' => bin2hex(random_bytes(8)),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $data = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

        return $data . '.' . hash_hmac('sha256', $data, $this->secret);
    }

    /** @return array<string, int|string>|null */
    public function verify(string $token, string $slug, int $version): ?array
    {
        if (strlen($token) > 2048 || !preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/D', $token, $parts)) {
            return null;
        }

        if (!hash_equals(hash_hmac('sha256', $parts[1], $this->secret), $parts[2])) {
            return null;
        }

        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        try {
            $value = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($value)
            || ($value['slug'] ?? null) !== $slug
            || ($value['version'] ?? null) !== $version
            || !is_int($value['issued'] ?? null)
            || $value['issued'] < time() - 7200
            || $value['issued'] > time() + 30
            || !is_string($value['nonce'] ?? null)
            || !preg_match('/^[a-f0-9]{16}$/D', $value['nonce'])
            || !is_array($value['parameters'] ?? null)) {
            return null;
        }

        $parameters = $value['parameters'];
        foreach ($parameters as $key => $parameter) {
            if (!is_string($key) || !preg_match('/^[a-z]{2,20}$/D', $key)
                || (!is_int($parameter) && !is_string($parameter))
                || (is_string($parameter) && strlen($parameter) > 40)) {
                return null;
            }
        }

        return $parameters;
    }
}
