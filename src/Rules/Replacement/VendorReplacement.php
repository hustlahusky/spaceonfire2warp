<?php

declare(strict_types=1);

namespace App\Rules\Replacement;

final class VendorReplacement implements ReplacementInterface
{
    public function replace(string $input): string
    {
        if (!\str_starts_with($input, 'spaceonfire\\')) {
            return $input;
        }

        $out = 'Warp\\';
        $str = \substr($input, 12);

        if (
            \str_starts_with($str, 'Collection\\')
            || \str_starts_with($str, 'CommandBus\\')
            || \str_starts_with($str, 'Common\\')
            || \str_starts_with($str, 'Container\\')
            || \str_starts_with($str, 'Criteria\\')
            || \str_starts_with($str, 'DataSource\\')
            || \str_starts_with($str, 'LaminasHydratorBridge\\')
            || \str_starts_with($str, 'Type\\')
            || \str_starts_with($str, 'ValueObject\\')
            || \str_starts_with($str, 'Clock\\')
            || \str_starts_with($str, 'Exception\\')
        ) {
            return $out . $str;
        }

        if (\str_starts_with($str, 'Bridge\\')) {
            $out .= 'Bridge\\';
            $str = \substr($input, 7);

            if (\str_starts_with($str, 'Cycle\\')) {
                return $out . $str;
            }

            if (\str_starts_with($str, 'LaminasHydrator\\')) {
                return $out . $str;
            }
        }

        return $input;
    }
}
