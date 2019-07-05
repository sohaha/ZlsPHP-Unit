<?php

namespace Zls\Unit;

use Z;

/**
 * Class Templates
 * @package Zls\Unit
 */
class Templates
{
    public function unit()
    {
        return "public function testBool()
    {
        \$this->assertEquals(\"hello Unit\", \"hello Unit\");
    }";
    }
}
