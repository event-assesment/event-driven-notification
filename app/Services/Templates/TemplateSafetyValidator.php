<?php

namespace App\Services\Templates;

final class TemplateSafetyValidator
{
    /**
     * @return array<int, string>
     */
    public function validate(string $template): array
    {
        $errors = [];

        if (preg_match('/{!!/m', $template) === 1) {
            $errors[] = 'Raw echo tags {!! !!} are not allowed.';
        }

        foreach ($this->forbiddenDirectives() as $directive) {
            if (preg_match('/@'.preg_quote($directive, '/').'\b/i', $template) === 1) {
                $errors[] = "Forbidden directive: @{$directive}.";
            }
        }

        preg_match_all('/@([A-Za-z_][A-Za-z0-9_]*)/', $template, $matches);

        foreach (array_unique($matches[1]) as $directive) {
            $normalized = strtolower($directive);

            if (!in_array($normalized, $this->allowedDirectives(), true)
                && ! in_array($normalized, $this->forbiddenDirectives(), true)) {
                $errors[] = "Unsupported directive: @{$directive}.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<int, string>
     */
    private function allowedDirectives(): array
    {
        return [
            'if',
            'elseif',
            'else',
            'endif',
            'foreach',
            'endforeach',
            'isset',
            'endisset',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function forbiddenDirectives(): array
    {
        return [
            'php',
            'include',
            'extends',
            'component',
            'inject',
            'slot',
        ];
    }
}
