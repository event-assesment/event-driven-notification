<?php

namespace App\Services\Templates;

use Illuminate\Support\Facades\Blade;

final class TemplateRenderer
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function render(string $template, array $variables = []): string
    {
        return Blade::render($template, $variables);
    }
}
