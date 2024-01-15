<?php
namespace Khooz\Polyline;

use LengthException;

/**
 * Point class
 * @package khooz\Polyline
 */
class Point
{
    /**
     * Latitude
     *
     * @var float
     */
    private float $latitude = 0;

    /**
     * Longitude
     *
     * @var float
     */
    private float $longitude = 0;

    /**
     * Normalizes latitudes and longitudes to their respective [-180, 180] and [-90, 90] boundaries
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private static function normalize (float $latitude, float $longitude) : array
    {
        return [
            $latitude - (intdiv($latitude < 0 ? ceil ($latitude - 90) : floor($latitude + 90), 180) * 180),
            $longitude - (intdiv($longitude < 0 ? ceil ($longitude - 180) : floor($longitude + 180), 360) * 360)
        ];
    }

    /**
     * String representation of a point in the form of `(<latitude>, <longitude>)`
     *
     * @return string
     */
    public function __toString() : string
    {
        return "({$this->latitude}, {$this->longitude})";
    }
    
    /**
     * Makes a point from latitude and longitude
     *
     * @param float|null $latitude
     * @param float|null $longitude
     */
    public function __construct(?float $latitude = null, ?float $longitude = null)
    {
        [$this->latitude, $this->longitude] = static::normalize($latitude, $longitude);
    }

    /**
     * Makes a point from array of `[<latitude>, <longitude>]`
     *
     * @param array $data
     * @param boolean $lat_lng
     * @return Point
     */
    public static function fromArray(array $data, $lat_lng = true) : Point
    {
        $count = count($data);
        if ($count !== 2)
        {
            throw new LengthException("Expected array length of 2, got {$count}");
        }
        return $lat_lng ? new Point($data[0], $data[1])
            : new Point($data[1], $data[0]);
    }

    /**
     * Adds two point coordinates together
     *
     * @param Point $a
     * @param Point $b
     * @return Point
     */
    public static function add(Point $a, Point $b) : Point
    {
        return new Point($a->latitude + $b->latitude, $a->longitude + $b->longitude);
    }
    
    /**
     * Subtracts two point coordinates from eachother
     *
     * @param Point $a
     * @param Point $b
     * @return Point
     */
    public static function sub(Point $a, Point $b) : Point
    {
        return new Point($a->latitude - $b->latitude, $a->longitude - $b->longitude);
    }

    /**
     * Moves a point by another coordinate differentials to another point
     *
     * @param Point $other
     * @return Point
     */
    public function move(Point $other) : Point
    {
        [$this->latitude, $this->longitude] = Point::normalize(
            $this->latitude + $other->latitude,
            $this->longitude + $other->longitude
        );
        return $this;
    }

    /**
     * Calculates coordinate differentials from a base coordinate
     *
     * @param Point $base
     * @return Point
     */
    public function rebase(Point $base) : Point
    {
        [$this->latitude, $this->longitude] = Point::normalize(
            $this->latitude - $base->latitude,
            $this->longitude - $base->longitude
        );
        return $this;
    }

    /**
     * Encodes one attribute of a coordinate to Google's polyline format
     *
     * @param integer $val
     * @return string
     */
    private static function encodeAttr(float $val) : string
    {
        $x = intval(round($val * 100000)) << 1; // Shift left one bit
        // Calculate chunks of 5 bits
        $x = str_split(
            // Left pad to 30 bits with 0
            str_pad(
                // Convert to binary string in base 2
                decbin(
                    // 1st complement if negative and keep the lower 32 bit on 64-bit machines
                    (($x & 0x80000000) === 0x80000000 ? ~$x : $x) & 0xffffffff
            ), 30, '0', STR_PAD_LEFT), 5);
        $res = "";
        foreach ($x as $v) {
            // Remove 0 chunks from high positions
            if (empty($res) && $v === "00000")
            {
                continue;
            }
            // Append to the resulting character first of the string (big-endian)
            $res = chr(
                // Convert base 2 binary string to integer and shift 63 characters in ascii to be printable
                bindec(
                    // If it's the first chunk, append 0 to the 6th bit position as terminater,
                    // else append 1 to continue
                    (empty($res) ? '0' : '1')
                    // Left pad the chunk to 5 bits with 0
                    . str_pad($v, 5, "0", STR_PAD_LEFT)
                ) + 63
            ) . $res;
        }
        return empty($res)? "?" : $res;
    }

    /**
     * Encodes a point to Google's polyline format
     *
     * @param integer $val
     * @return string
     */
    public function encode() : string
    {
        return static::encodeAttr($this->latitude) . static::encodeAttr($this->longitude);
    }

    public static function decode(string &$data, bool $is_latlng = true) : Point
    {
        $latlng = [];
        $tmp_coord = $shift = 0;
        while (!empty($data) && count($latlng) < 2) {
            $value = ord(substr($data, $shift, 1));
            $tmp_coord |= (($value - 63) & 0x1f) << (5 * $shift);
            $shift++;
            if (!(($value - 63) & 0x20))
            {
                $latlng[] = (($tmp_coord & 1 ? ~$tmp_coord : $tmp_coord) >> 1) * 0.00001;
                $data = substr($data, $shift);
                $tmp_coord = $shift = 0;
            }
        }
        $c = count($latlng);
        if ($c !== 2)
        {
            throw new LengthException("Expected 2 coordinates for a point, got {$c}");
        }
        if ($is_latlng) {
            return new Point($latlng[0], $latlng[1]);
        }
        else {
            return new Point($latlng[1], $latlng[0]);
        }
    }
}
