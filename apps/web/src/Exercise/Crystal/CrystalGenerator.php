<?php

declare(strict_types=1);

namespace App\Exercise\Crystal;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class CrystalGenerator
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
    ) {
    }

    /** @return array<string, int|string> */
    public function generate(): array
    {
        $start = random_int(7, 28);
        $gain = random_int(5, 14);
        $loss = random_int(2, $gain - 1);

        $challenge = [
            'gain' => $gain,
            'loss' => $loss,
            'end' => ($start + $gain - $loss) * 2,
            'issued' => time(),
            'nonce' => bin2hex(random_bytes(8)),
        ];
        $challenge['signature'] = $this->sign($challenge);

        return $challenge;
    }

    /** @param array<string, mixed> $challenge */
    public function isValid(array $challenge): bool
    {
        foreach (['gain', 'loss', 'end', 'issued'] as $name) {
            if (!isset($challenge[$name]) || !is_scalar($challenge[$name])
                || !preg_match('/^[0-9]{1,12}$/D', (string) $challenge[$name])) {
                return false;
            }
        }

        if (!isset($challenge['nonce'], $challenge['signature'])
            || !is_string($challenge['nonce']) || !is_string($challenge['signature'])
            || !preg_match('/^[a-f0-9]{16}$/D', $challenge['nonce'])
            || !preg_match('/^[a-f0-9]{64}$/D', $challenge['signature'])) {
            return false;
        }

        $gain = (int) $challenge['gain'];
        $loss = (int) $challenge['loss'];
        $end = (int) $challenge['end'];
        $issued = (int) $challenge['issued'];
        $start = intdiv($end, 2) - $gain + $loss;

        if ($gain < 5 || $gain > 14
            || $loss < 2 || $loss >= $gain
            || $end % 2 !== 0 || $start < 7 || $start > 28
            || $issued < time() - 7200 || $issued > time() + 30) {
            return false;
        }

        return hash_equals($this->sign($challenge), $challenge['signature']);
    }

    /** @param array<string, mixed> $challenge */
    private function sign(array $challenge): string
    {
        $data = implode('|', array_map(
            static fn (string $key): string => (string) $challenge[$key],
            ['gain', 'loss', 'end', 'issued', 'nonce'],
        ));

        return hash_hmac('sha256', $data, $this->secret);
    }
}
