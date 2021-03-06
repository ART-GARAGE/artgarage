<?php
namespace ArtGarage;

class UserValidator
{
	/**
	 * @param $value
	 * @return bool
	 */
	public function required($value) {
		return !in_array($value, array(null, ''), true);
	}

	/**
	 * @param $value
	 * @param $format
	 * @return bool
	 */
	public function date($value, $format) {
		$d = \DateTime::createFromFormat($format, $value);
		return $d && $d->format($format) == $value;
	}

	/**
	 * @param $value
	 * @param $min
	 * @return bool
	 */
	public function min($value, $min)
	{
		return $value >= $min;
	}

	/**
	 * @param $value
	 * @param $max
	 * @return bool
	 */
	public function max($value, $max)
	{
		return $value <= $max;
	}
	
	/**
	 * @param $value
	 * @return bool
	 */
	public function checked($value)
	{
		return (strtolower($value)=="on");
	}
	
	/**
	 * @param $value
	 * @param $min
	 * @return bool
	 */
	public function greater($value, $min)
	{
		return $value > $min;
	}

	/**
	 * @param $value
	 * @param $max
	 * @return bool
	 */
	public function less($value, $max)
	{
		return $value < $max;
	}

	/**
	 * @param $value
	 * @param $min
	 * @param $max
	 * @return bool
	 */
	public function between($value, $min, $max)
	{
		return ($value >= $min && $value <= $max);
	}

	/**
	 * @param $value
	 * @param $allowed
	 * @return bool
	 */
	public function equals($value, $allowed)
	{
		return $value === $allowed;
	}

	/**
	 * @param $value
	 * @param array $allowed
	 * @return bool
	 */
	public function in($value, $allowed)
	{
		return in_array($value, $allowed, true);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function alpha($value)
	{
		return (bool) preg_match('/^\pL++$/uD', $value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function numeric($value)
	{
		return (bool) preg_match('#^[0-9]*$#',$value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function alphaNumeric($value)
	{
		return (bool) preg_match('/^[\pL\pN]++$/uD', $value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function slug($value)
	{
		return (bool) preg_match('/^[\pL\pN\-\_]++$/uD', $value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function decimal($value)
	{
		return (bool) preg_match('/^[0-9]+(?:\.[0-9]+)?$/D', $value);
	}


	/**
	 * @param $value
	 * @return bool
	 */
	public function phone($value)
	{
		return (bool) preg_match("/^(\+7\(\d{3}\)\d{3}-\d{2}-\d{2})$/", $value);
	}

	/**
	 * @param $value
	 * @param $regexp
	 * @return bool
	 */
	public function matches($value,$regexp)
	{
		return (bool) preg_match($regexp,$value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function url($value)
	{
		return (bool) preg_match(
			'~^
				[-a-z0-9+.]++://
				(?!-)[-a-z0-9]{1,63}+(?<!-)
				(?:\.(?!-)[-a-z0-9]{1,63}+(?<!-)){0,126}+
				(?::\d{1,5}+)?
				(?:/.*)?
			$~iDx',
			$value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function email($value)
	{
		
		if(empty($value)){
			return false;
		}
		$idn = new \ArtGarage\IdnaConvert(array('idn_version'=>2008));
		$email = $idn->encode($value);
		return (bool) preg_match("/^([\w-._]+@[\w-._]+\.[\w-]{2,})$/i", $email);
	}

	/**
	 * @param $value
	 * @param $length
	 * @return bool
	 */
	public function length($value, $length)
	{
		return $this->getLength($value) === $length;
	}

	/**
	 * @param $value
	 * @param $minLength
	 * @return bool
	 */
	public function minLength($value, $minLength)
	{
		return $this->getLength($value) >= $minLength;
	}

	/**
	 * @param $value
	 * @param $maxLength
	 * @return bool
	 */
	public function maxLength($value, $maxLength)
	{
		return $this->getLength($value) <= $maxLength;
	}

	/**
	 * @param $value
	 * @param $minLength
	 * @param $maxLength
	 * @return bool
	 */
	public function lengthBetween($value, $minLength, $maxLength)
	{
		$length = $this->getLength($value);
		return ($length >= $minLength && $length <= $maxLength);
	}

	/**
	 * @param $value
	 * @param $minSize
	 * @return bool
	 */
	public function minCount($value, $minSize) {
		return count($value) >= $minSize;
	}

	/**
	 * @param $value
	 * @param $maxSize
	 * @return bool
	 */
	public function maxCount($value, $maxSize) {
		return count($value) <= $maxSize;
	}

	/**
	 * @param $value
	 * @param $minSize
	 * @param $maxSize
	 * @return bool
	 */
	public function countBetween($value, $minSize, $maxSize) {
		return (count($value) >= $minSize && count($value) <= $maxSize);
	}
	
	/**
	 * @param $string
	 * @return int
	 */
	protected function getLength($string)
	{
		return strlen(utf8_decode($string));
	}
}
