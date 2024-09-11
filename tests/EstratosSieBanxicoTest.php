<?php

namespace Estratos\SieBanxico\Tests;

use Estratos\SieBanxico\DependencyInjection\EstratosHowToExtension;
use Estratos\SieBanxico\EstratosSieBanxico;
use PHPUnit\Framework\TestCase;

class EstratosSieBanxicoTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new EstratosSieBanxico();
        $this->assertInstanceOf(EstratosSieBanxicoExtension::class, $bundle->getContainerExtension());
    }
}
