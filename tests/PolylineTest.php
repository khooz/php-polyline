<?php
namespace Khooz\Polyline\Test;

require_once __DIR__ . '/../vendor/autoload.php';

use Khooz\Polyline\Point;
use Khooz\Polyline\Polyline;
use LengthException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TypeError;

/**
 * Test class for Point
 *
 * @uses \Khooz\Polyline\Point
 * @covers \Khooz\Polyline\Point::__construct
 */
class PolylineTest extends TestCase
{

    /**
     * Testing normalizing coordinates to their respective boundries
     *
     * @covers \Khooz\Polyline\Polyline::__construct
     * @covers \Khooz\Polyline\Polyline::__toString
     * @covers \Khooz\Polyline\Polyline::fromPoints
     * @covers \Khooz\Polyline\Polyline::fromDiffs
     * @covers \Khooz\Polyline\Polyline::diffStr
     * @covers \Khooz\Polyline\Polyline::diffs
     * @covers \Khooz\Polyline\Polyline::toArray
     *
     * @return void
     */
    public function testBasics()
    {
        $pp = [new Point(38.5, -120.2), new Point(40.7, -120.95), [43.252, -126.453]];
        $pd = [new Point(38.5, -120.2), new Point(2.2, -0.75), new Point(2.552, -5.503)];
        $pda = [new Point(38.5, -120.2), [2.2, -0.75], new Point(2.552, -5.503)];
        $pol = new Polyline($pp);
        $pold = new Polyline($pda, true);
        $this->assertEquals("[(38.5, -120.2), (40.7, -120.95), (43.252, -126.453)]", $pol);
        $this->assertEquals($pol, $pold);
        $this->assertEqualsWithDelta($pd, $pol->diffs(), 0.000001);
        $this->assertEquals("[(38.5, -120.2), (2.2, -0.75), (2.552, -5.503)]", $pol->diffStr());
        $this->assertEquals($pol->toArray(), $pold->toArray());
        $rightException = false;
        try {
            $pda[2] = [];
            @new Polyline($pda);
        }
        catch (LengthException $e) {
            $rightException = true;
        }
        $this->assertTrue($rightException);
        $rightException = false;
        try {
            $pp[2] = [];
            @new Polyline($pp, false);
        }
        catch (LengthException $e) {
            $rightException = true;
        }
        $this->assertTrue($rightException);
        $rightException = false;
        try {
            $pda[2] = 145;
            @new Polyline($pda, true);
        }
        catch (TypeError $e) {
            $rightException = true;
        }
        $this->assertTrue($rightException);
        $rightException = false;
        try {
            $pp[2] = 145;
            @new Polyline($pp);
        }
        catch (TypeError $e) {
            $rightException = true;
        }
        $this->assertTrue($rightException);
    }

    /**
     * Testing encoding
     *
     * @covers \Khooz\Polyline\Polyline::encode
     * @uses \Khooz\Polyline\Polyline
     *
     * @return void
     */
    public function testEncode() {
        $pp = [new Point(38.5, -120.2), new Point(40.7, -120.95), [43.252, -126.453]];
        $pol = new Polyline($pp);
        $this->assertEquals("_p~iF~ps|U_ulLnnqC_mqNvxq`@", $pol->encode());
    }

    /**
     * Testing decoding
     *
     * @covers \Khooz\Polyline\Polyline::decode
     * @uses \Khooz\Polyline\Polyline
     *
     * @return void
     */
    public function testDecode() {
        $pp = [new Point(38.5, -120.2), new Point(40.7, -120.95), [43.252, -126.453]];
        $pol = new Polyline($pp);
        $this->assertEquals($pol, Polyline::decode("_p~iF~ps|U_ulLnnqC_mqNvxq`@"));
    }
}

