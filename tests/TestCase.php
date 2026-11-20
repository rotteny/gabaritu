<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected string $pastaDeImagens;

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pastaDeImagens);

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pastaDeImagens = sys_get_temp_dir().'/gabaritu-img-'.bin2hex(random_bytes(6));
        config(['gabaritu.imagens.pasta' => $this->pastaDeImagens]);
        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
