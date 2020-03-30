<?php
/**
* This class is intended to replace PHP's DateInterval class.
* DateInterval contains a bug which makes it unusable for ISO 8601 rev:2000 and later:
* Here we add support for decimals in any field (so long as it's the last field).
*/
class My_DateInterval
{
	public $y = 0;

	public $m = 0;

	public $d = 0;

	public $h = 0;

	public $i = 0;

	public $s = 0;

	public $f = 0;

	public $w = 0;

	public $negate = false;

	public function __construct($iso = 'PT0S')
	{
		$this->parse_iso($iso);
	}

	protected function part($prop, $nextprop, $factor, $last)
	{
		if ($last != $prop)
		{
			$y = intval($this->$prop);

			if ($y == $this->$prop)
			{
				// use the int
				return $y;
			}
			else
			{
				$coef = $this->$prop - $y;
				$this->$prop = $y;
				$this->$nextprop += $factor * $coef;
			}
		}

		return round($this->$prop, 6) + 0;
	}

	public function value()
	{
		$iso = '';

		// if all entries are negative:
		// if the iso is negative, make everything positive
		// if the iso is positive, make the iso negative and entries positive

		$count = 0;
		$swap = 0;
		$all = array('y', 'm', 'd', 'h', 'i', 's', 'w', 'f');

		foreach ($all AS $p)
		{
			if ($this->$p)
			{
				$count++;
			}

			if ($this->negate)
			{
				if ($this->$p < 0)
				{
					$swap++;
				}
			}
			else if ($this->$p < 0)
			{
				$swap++;
			}
		}

		if ($swap == $count)
		{
			foreach ($all AS $p)
			{
				$this->$p *= -1;
			}

			$this->negate = !$this->negate;
		}

		if ($this->negate)
		{
			$iso .= '-';
		}

		$iso .= 'P';

		$date = array();
		$time = array();

		$y = 0;
		$m = 0;
		$d = 0;
		$h = 0;
		$i = 0;
		$s = 0;

		foreach (array('y', 'm', 'd', 'h', 'i', 's') AS $p)
		{
			if ($this->$p)
			{
				$last = $p;
			}
		}

		if ($this->w AND $last)
		{
			$this->d += $this->w * 7;
			$this->w = 0;

			switch ($last)
			{
				case 'y':
				case 'm':
					$last = 'd';
					break;
			}
		}

		foreach (array(
			'y' => array('m', 12),
			'm' => array('d', 30),
			'd' => array('h', 24),
			'h' => array('i', 60),
			'i' => array('s', 60)
		) AS $p => $pi)
		{
			if ($this->$p)
			{
				$$p = $this->part($p, $pi[0], $pi[1], $last);
			}
		}

		$s = $this->s;

		if ($this->f)
		{
			$s += $this->f / 1000000;
		}

		if ($s)
		{
			$s = round($s, 6) + 0;
		}

		foreach (array(
			's' => array('S', 'i', 60),
			'i' => array('M', 'h', 60),
			'h' => array('H', 'd', 24)
		) AS $v => $vi)
		{
			$this->push($vi[0], $$v, ${$vi[1]}, $vi[2], $time);
		}

		foreach (array(
			'd' => array('D', 'm', 30),
			'm' => array('M', 'y', 12),
		) AS $v => $vi)
		{
			$this->push($vi[0], $$v, ${$vi[1]}, $vi[2], $date);
		}

		$n = false;
		$this->push('Y', $y, $n, false, $date);

		if (!$date AND !$time AND $this->w)
		{
			$w = round($this->w, 6) + 0;
			$date[] = $w . 'W';
		}

		$iso .= implode('', $date);

		if ($time)
		{
			$iso .= 'T';

			$iso .= implode('', $time);
		}
		else if (!$date)
		{
			$iso .= '0S';
		}

		return $iso;
	}

	protected function push($delim, &$val, &$nextval, $coef, &$arr)
	{
		if ($val)
		{
			if ($nextval AND $coef)
			{
				$floor = floor($val / $coef);

				if ($floor)
				{
					$val -= $floor * $coef;
					$nextval += $floor;
				}
			}

			array_unshift($arr, $val . $delim);
		}
	}

	protected function exception($iso)
	{
		return new Exception('Duration could not be parsed as ISO 8601: ' . htmlspecialchars($iso));
	}

	protected function parse_iso($iso)
	{
		$p = substr($iso, 0, 1);

		if ($p AND $p == '-')
		{
			$this->negate = true;
			$p = substr($iso, 1, 1);
		}

		if (!$p OR $p != 'P')
		{
			throw $this->exception($iso);
		}

		$dt = substr($iso, 1);
		$parts = explode('T', $dt, 2);
		$units = array();

		if (isset($parts[0]))
		{
			foreach (array('Y', 'M', 'D') AS $k)
			{
				if ($parts[0] === '')
				{
					break;
				}

				/** @var array $m */
				if (preg_match('/^(\-?\d+(?:[\.\,]\d+)?)' . $k . '/', $parts[0], $m))
				{
					$units[strtolower($k)] = str_replace(',', '.', $m[1]);
					$parts[0] = substr($parts[0], strlen($m[0]));
				}
			}

			if ($parts[0] !== '')
			{
				throw $this->exception($iso);
			}
		}

		if (isset($parts[1]))
		{
			if ($parts[1] === '')
			{
				throw $this->exception($iso);
			}

			foreach (array('H', 'M', 'S') AS $k)
			{
				if ($parts[1] === '')
				{
					break;
				}

				/** @var array $m */
				if (preg_match('/^(\-?\d+(?:[\.\,]\d+)?)' . $k . '/', $parts[1], $m))
				{
					if ($k == 'M')
					{
						$k = 'i';
					}

					$units[strtolower($k)] = str_replace(',', '.', $m[1]);
					$parts[1] = substr($parts[1], strlen($m[0]));
				}
			}

			if ($parts[1] !== '')
			{
				throw $this->exception($iso);
			}
		}

		if (!$units)
		{
			throw $this->exception($iso);
		}

		$floated = false;

		foreach ($units AS $unit => $value)
		{
			if ($floated)
			{
				throw $this->exception($iso);
			}

			if (!isset($this->$unit))
			{
				throw $this->exception($iso);
			}

			if (intval($value) != $value)
			{
				$floated = true;
				$this->$unit = floatval(str_replace(',', '.', $value));
			}
			else
			{
				$this->$unit = intval($value);
			}
		}

		if ($this->s AND intval($this->s) != $this->s)
		{
			$ms = $this->s - intval($this->s);
			$this->s = intval($this->s);
			$this->f = $ms * 1000000;
		}
	}
}
