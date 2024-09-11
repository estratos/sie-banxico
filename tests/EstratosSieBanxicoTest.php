<?php

namespace Estratos\SieBanxico\Tests;

use Estratos\SieBanxico\DependencyInjection\SieBanxicoExtension;
use Estratos\SieBanxico\SieBanxico;
use PHPUnit\Framework\TestCase;

class EstratosSieBanxicoTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new SieBanxico();
        $this->assertInstanceOf(SieBanxicoExtension::class, $bundle->getContainerExtension());
    }
}
