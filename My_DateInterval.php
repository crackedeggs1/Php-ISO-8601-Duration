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

	public function __construct($iso = 'PT0S')
	{
		$this->parse_iso($iso);
	}

	protected function part($delim, $prop, $nextprop, $factor, $last)
	{
		if ($last != $prop)
		{
			$y = intval($this->$prop);

			if ($y != $this->$prop)
			{
				$coef = $this->$prop - $y;
				$this->$prop = $y;
				$this->$nextprop += $factor * $coef;
			}
		}

		$y = round($this->$prop, 6) + 0;

		return $y . $delim;
	}

	public function value()
	{
		$iso = '';
		$iso .= 'P';

		$date = array();
		$time = array();

		foreach (array('y', 'm', 'd', 'h', 'i', 's') AS $p)
		{
			if ($this->$p)
			{
				$last = $p;
			}
		}

		if ($this->y)
		{
			$date[] = $this->part('Y', 'y', 'm', 12, $last);
		}

		if ($this->m)
		{
			$date[] = $this->part('M', 'm', 'd', 30, $last);
		}

		if ($this->d)
		{
			$date[] = $this->part('D', 'd', 'h', 24, $last);
		}

		if ($this->h)
		{
			$time[] = $this->part('H', 'h', 'i', 60, $last);
		}

		if ($this->i)
		{
			$time[] = $this->part('M', 'i', 's', 60, $last);
		}

		$s = $this->s;

		if ($this->f)
		{
			$s += $this->f / 1000000;
		}

		if ($s)
		{
			$s = round($s, 6) + 0;
			$time[] = $s . 'S';
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

	protected function exception($iso)
	{
		return new Exception('Duration could not be parsed as ISO 8601: ' . htmlspecialchars($iso));
	}

	protected function parse_iso($iso)
	{
		$p = substr($iso, 0, 1);

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
				if (preg_match('/^(\d+(?:[\.\,]\d+)?)' . $k . '/', $parts[0], $m))
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
				if (preg_match('/^(\d+(?:[\.\,]\d+)?)' . $k . '/', $parts[1], $m))
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
