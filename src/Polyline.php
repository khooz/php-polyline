<?php
namespace Khooz\Polyline;

/**
 * Polyline class
 * @package khooz\Polyline
 */
class Polyline
{
    private array $points = [];

    /**
     * Hydrate from an array of differential coordinations inf the form of
     * `[[<latitude>,<longitude>],...]`
     * or
     * `[khooz\Polyline\Point,...]`
     *
     * @param array $data input array
     * @param boolean $lat_lng in the case of array of array of floats
     * , wether it is in the form of `[<latitude>,<longitude>]` (true) or `[<longitude>,<latitude>]` (false)
     * @return void
     */
    private function fromDiffs(array $data, bool $lat_lng = true)
    {
        $prev = new Point(0,0);
        foreach ($data as $i => $point) {
            if (is_array($point))
            {
                $point = Point::fromArray($point, $lat_lng);
            }
            if (!($point instanceof Point))
            {
                $type = is_object($point) ? get_class($point) : gettype($point);
                throw new \TypeError("Invalid input type at index {$i}. Expected array or point, got {$type}");
            }
            $this->points[$i] = clone $point;
            $prev = $this->points[$i]->move($prev);
        }
    }

    /**
     * Hydrate from an array of point coordinations in the form of
     * `[[<latitude>,<longitude>],...]`
     * or
     * `[khooz\Polyline\Point,...]`
     *
     * @param array $data input array
     * @param boolean $lat_lng in the case of array of array of floats
     * , wether it is in the form of `[<latitude>,<longitude>]` (true) or `[<longitude>,<latitude>]` (false)
     * @return void
     */
    private function fromPoints(array $data, bool $lat_lng = true)
    {
        $len = count($data);
        $this->points = $data;
        for ($i = 0; $i < $len; $i++)
        {
            if (is_array($this->points[$i]))
            {
                $this->points[$i] = Point::fromArray($this->points[$i], $lat_lng);
            }
            if (!($this->points[$i] instanceof Point))
            {
                $type = is_object($this->points[$i]) ? get_class($this->points[$i]) : gettype($this->points[$i]);
                throw new \TypeError("Invalid input type at index {$i}. Expected array or point, got {$type}");
            }
        }
    }

    /**
     * Make a poluline from an array of data in the form of
     * `[[<latitude>,<longitude>],...]`
     * or
     * `[khooz\Polyline\Point,...]`
     *
     * @param array $data input array
     * @param boolean $is_diff whether input array is differential coordinates (true) or points coordinates (false)
     * @param boolean $lat_lng in the case of array of array of floats
     * , wether it is in the form of `[<latitude>,<longitude>]` (true) or `[<longitude>,<latitude>]` (false)
     */
    public function __construct(array $data, bool $is_diff = false, bool $lat_lng = true) {
        if ($is_diff)
        {
            $this->fromDiffs($data, $lat_lng);
        }
        else
        {
            $this->fromPoints($data, $lat_lng);
        }
    }

    /**
     * String representation of a polyline in the form of `[(<latitude>, <longitude>), ...]`
     *
     * @return string
     */
    public function __toString() : string
    {
        return "[" . implode(', ', array_map(fn($a) => $a->__toString(), $this->points)) . "]";
    }

    /**
     * Convert to array of differential `khooz\Polyline\Point`
     *
     * @return array
     */
    public function diffs() : array
    {
        $res = [];
        $prev = new Point(0,0);
        foreach ($this->points as $value) {
            $value_c = clone $value;
            $res[] = $value_c->rebase($prev);
            $prev = $value;
        }
        return $res;
    }

    /**
     * String representation of a polyline differential coordinates in the form of `[(<latitude>, <longitude>), ...]`
     *
     * @return string
     */
    public function diffStr() : string
    {
        return "[" . implode(', ', array_map(fn($a) => $a->__toString(), $this->diffs())) . "]";
    }

    /**
     * String representation of a polyline differential coordinates in the form of `[(<latitude>, <longitude>), ...]`
     *
     * @return string
     */
    public function toArray() : array
    {
        $points = [];
        foreach ($this->points as $value) {
            $points[] = clone $value;
        }
        return $points;
    }

    /**
     * Encode a polyline into Google's format
     *
     * @return string
     */
    public function encode() : string
    {
        $res = "";
        $prev = new Point(0,0);
        foreach ($this->points as $value) {
            $value_c = clone $value;
            $res .= $value_c->rebase($prev)->encode();
            $prev = $value;
        }
        return $res;
    }

    /**
     * Decode a polyline from Google's format
     *
     * @param string $data
     * @return Polyline
     */
    public static function decode(string $data, bool $is_latlng = true) : Polyline
    {
        $tmp = [];
        $prev = new Point(0,0);
        while (!empty($data))
        {
            $v = Point::decode($data, $is_latlng)->move($prev);
            $tmp[] = $prev = $v;
        }
        return new Polyline($tmp);
    }
}


