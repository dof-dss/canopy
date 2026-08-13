<?php

declare(strict_types=1);

namespace Canopy\Security;

final class OutputRedactor
{
    private const REDACTED = '[redacted]';
    private const MAX_TEXT_LENGTH = 500;

    /**
     * @param list<string> $sensitivePaths
     */
    public function text(string $value, array $sensitivePaths = []): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';

        foreach ($this->normalisePaths($sensitivePaths) as $path) {
            $value = str_replace($path, '[path]', $value);
        }

        $patterns = [
            '/\b(Bearer)\s+[^\s,;]+/i' => '$1 ' . self::REDACTED,
            '/\b(ghp_|github_pat_)[A-Za-z0-9_]+\b/' => self::REDACTED,
            '/\b(password|passwd|secret|token|api[_-]?key|authorization|cookie)\b(\s*[:=]\s*)([^\s,;]+)/i' => '$1$2' . self::REDACTED,
            '#\b([a-z][a-z0-9+.-]*://)[^/@\s:]+:[^/@\s]+@#i' => '$1' . self::REDACTED . '@',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value) ?? '';
        }

        if (strlen($value) > self::MAX_TEXT_LENGTH) {
            $value = substr($value, 0, self::MAX_TEXT_LENGTH) . '...[truncated]';
        }

        return $value;
    }

    /**
     * @param list<string> $sensitivePaths
     */
    public function value(mixed $value, array $sensitivePaths = [], ?string $key = null): mixed
    {
        if ($key !== null && preg_match('/(?:password|passwd|secret|token|api[_-]?key|authorization|cookie)/i', $key) === 1) {
            return self::REDACTED;
        }

        if (is_string($value)) {
            return $this->text($value, $sensitivePaths);
        }

        if (!is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $itemKey => $itemValue) {
            $redacted[$itemKey] = $this->value(
                $itemValue,
                $sensitivePaths,
                is_string($itemKey) ? $itemKey : null,
            );
        }

        return $redacted;
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function normalisePaths(array $paths): array
    {
        $home = getenv('HOME');
        if (is_string($home) && $home !== '') {
            $paths[] = $home;
        }

        $paths = array_values(array_unique(array_filter(
            array_map(static fn (string $path): string => rtrim($path, DIRECTORY_SEPARATOR), $paths),
            static fn (string $path): bool => $path !== '' && $path !== DIRECTORY_SEPARATOR,
        )));
        usort($paths, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return $paths;
    }
}
