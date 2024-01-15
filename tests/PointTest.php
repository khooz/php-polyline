<?php
namespace Khooz\Polyline\Test;

require_once __DIR__ . '/../vendor/autoload.php';

use ReflectionMethod;
use LengthException;
use PHPUnit\Framework\TestCase;
use Khooz\Polyline\Point;

/**
 * Test class for Point
 *
 * @uses \Khooz\Polyline\Point
 * @covers \Khooz\Polyline\Point::__construct
 */
class PointTest extends TestCase
{

    /**
     * Testing normalizing coordinates to their respective boundries
     *
     * @covers \Khooz\Polyline\Point::normalize
     * @covers \Khooz\Polyline\Point::fromArray
     * @covers \Khooz\Polyline\Point::__toString
     *
     * @return void
     */
    public function testBasics()
    {
        [$lat, $lng] = [0, 0];
        $method = new ReflectionMethod(Point::class, 'normalize');
        @$method->setAccessible(true);
        $this->assertEquals([$lat, $lng], $method->invoke(null, $lat, $lng), "Testing already normal coordinates");
        [$lat, $lng] = [120, 230];
        $this->assertEquals([-60, -130], $method->invoke(null, $lat, $lng), "Testing out of bound coordinates");
        $this->assertEquals(new Point($lat, $lng), Point::fromArray([$lat, $lng]), "Testing fromArray [lat, long]");
        $this->assertEquals(
            new Point($lat, $lng),
            Point::fromArray([$lng, $lat], false), "Testing fromArray [long, lat]"
        );
        $this->assertEquals(
            "(-60, -130)",
            new Point($lat, $lng), "Testing implicit casting to string"
        );
        $this->expectException(LengthException::class);
        Point::fromArray([1]);
        Point::fromArray([1,2,3]);

    }

    /**
     * Testing encoding a Point object
     *
     * @covers \Khooz\Polyline\Point::encode
     * @uses \Khooz\Polyline\Point
     *
     * @return void
     */
    public function testEncoding()
    {
        $p = new Point(38.5, -120.2);
        $this->assertEquals("_p~iF~ps|U", $p->encode(), "Testing encoding a whole point");
    }

    /**
     * Testing encoding one coordinate
     *
     * @covers \Khooz\Polyline\Point::encodeAttr
     *
     * @return void
     */
    public function testEncodeAttr()
    {
        $method = new ReflectionMethod(Point::class, 'encodeAttr');
        @$method->setAccessible(true);
        $p = [
            ["`~oia@", -179.9832104],
            ["?", 0],
        ];
        foreach ($p as $v) {
            $this->assertEquals($v[0], $method->invoke(null, $v[1]), "Testing encoding one coordinate");
        }
    }

    /**
     * Testing decoding from a string
     *
     * @covers \Khooz\Polyline\Point::decode
     *
     * @return void
     */
    public function testDecode()
    {
        $data = "_p~iF~ps|U";
        $this->assertEquals(
            new Point(38.5, -120.2)
            , Point::decode($data)
            , "Testing decoding in [lat, long] format"
        );
        $data = "~ps|U_p~iF";
        $this->assertEquals(
            new Point(38.5, -120.2),
            Point::decode($data, false),
            "Testing decoding in [long, lat] format"
        );
        $this->assertEmpty($data, "");
        $data = "_p~iF";
        $this->expectException(LengthException::class);
        Point::decode($data);
        $this->assertEmpty($data, "");
    }
    
    /**
     * Testing Point calculations
     *
     * @covers \Khooz\Polyline\Point::add
     * @covers \Khooz\Polyline\Point::sub
     * @covers \Khooz\Polyline\Point::move
     * @covers \Khooz\Polyline\Point::rebase
     *
     * @return void
     */
    public function testCalculations()
    {
        $points = [
            [new Point(-55, 130), new Point(75, -130), new Point(50, -100)],
        ];
        foreach ($points as $point) {
            $this->assertEquals($point[0], Point::add($point[1], $point[2]));
            $this->assertEquals($point[1], Point::sub($point[0], $point[2]));
            $this->assertEquals($point[2], Point::sub($point[0], $point[1]));
            $a = clone $point[2];
            $a->move($point[1]);
            $this->assertEquals($point[0], $a);
            $a = clone $point[1];
            $a->move($point[2]);
            $this->assertEquals($point[0], $a);
            $a = clone $point[0];
            $a->rebase($point[2]);
            $this->assertEquals($point[1], $a);
            $a = clone $point[0];
            $a->rebase($point[1]);
            $this->assertEquals($point[2], $a);
        }
    }
}

