<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaginasLegalesTest extends TestCase
{
    public function test_las_paginas_legales_cargan_sin_error(): void
    {
        foreach (['terminos', 'privacidad', 'cookies', 'encargo-tratamiento'] as $pagina) {
            $this->get('/legal/'.$pagina)->assertOk();
        }
    }
}
