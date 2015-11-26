<?php

namespace Tpln;

class Form
{
	protected $formLang = 'en';
	private $formErrorCssClass = 'error';
	private $formName = '';
	private $formInputNames = array();
	private $formLastInputName = '';
	private $formErrorStack = array();
	private $formValues = array();


	/**
	 * Initialize form with value and apply xss
	 *
	 * @param array $values
	 * @param array $xss_exception input names exception for xss
	 */
	public function formInit($values, $xss_exception=array())
	{
		$this->formValues = $values;

		if($this->formIsPosted())
		{
			foreach($_POST as $key => $val)
			{
				if(!in_array($key, $xss_exception))
				{
					if(!is_array($_POST[$key]))
						$_POST[$key] = trim($this->xssProtect(trim($_POST[$key])));
					else
						for($i=0; $i <  count($_POST[$key]); $i++)
							$_POST[$key][$i] = trim($this->xssProtect(trim($_POST[$key][$i])));
				}
			}
		}
	}

	/**
	 * Set form error css class
	 *
	 * @param string $class (default error)
	 */
	public function formSetErrorCssClass($class)
	{
		$this->formErrorCssClass = $class;
	}

	/**
	 * Set form name
	 *
	 * @param string $form_name
	 */
	public function formSetName($form_name)
	{
		$this->formName = $form_name;
	}

	/**
	 * Set form lang
	 *
	 * @param string $lang
	 */
	public function formSetLang($lang)
	{
		$this->formLang = $lang;
	}

	/**
	 * Set input name in error message
	 *
	 * @param string $input
	 * @param string $label
	 */
	public function formSetInputName($input, $label)
	{
		$this->formInputNames[$input] = $label;
	}

	/**
	 * Set input name in error message
	 *
	 * @param array $inputs
	 */
	public function formSetInputNames($inputs)
	{
		$this->formInputNames = $inputs;
	}

	private function _getErrorMsg($index, $fields, $custom_message='')
	{
		$msg = (empty($custom_message)) ? $GLOBALS['tpln_form_err'][$index][$this->formLang] : $custom_message;
		$msg = vsprintf($msg, $fields);

		foreach($this->formInputNames as $key => $val)
		{
			$msg = str_replace("`{$key}`", "`{$val}`", $msg);
		}


		return $msg;
	}

	/**
	 * Verify if input exists
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	public function exists($name)
	{
		if(!isset($_POST[$name]))
		{
			// $msg = $this->_getErrorMsg(15, array($name));
			// $this->formErrorStack[$name][] = $msg;
			$_POST[$name] = '';
			return false;
		}

		return true;
	}

	/**
	 * Verify if input is not empty
	 *
	 * @param string $name
	 * @param string $custom_message (optional)
	 *
	 * @return $this
	 */
	public function required($name, $custom_message='')
	{
		if(!$_POST)return $this;

		// is array
		if(strpos($name, '[]') !== false)
		{
			$name = str_replace('[]', '', $name);
			if(!isset($_POST[$name])) $_POST[$name]= array();
		}

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || (!is_array($_POST[$name])) && empty(trim(strip_tags($_POST[$name]))) || (is_array($_POST[$name]) && !count($_POST[$name])))
		{
			$msg = $this->_getErrorMsg(0, array($name), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}


	/**
	 * Verify if is boolean input value
	 *
	 * @param string $name
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function boolean($name='', $custom_message='')
	{
		$list = array('yes', 'YES', 1, '1', 'TRUE', 'true', 'FALSE', 'false', '0', 0, 'NO', 'no');
		if(!$_POST)return $this;

		$name = str_replace('[]', '', $name);
		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || empty($_POST[$name]))return $this;

		if(!is_array($_POST[$name]))
		{
			if(!in_array($_POST[$name], $list))
			{
				$msg = $this->_getErrorMsg(29, array($name), $custom_message);
				$this->formErrorStack[$name][] = $msg;
			}
		}
		else
		{
			foreach($_POST[$name] as $v)
			{
				if(!in_array($v, $list))
				{
					$msg = $this->_getErrorMsg(29, array($name), $custom_message);
					$this->formErrorStack[$name][] = $msg;
					break;
				}
			}
		}


		return $this;
	}

	/**
	 * Verify if input value is in list
	 *
	 * @param string $name
	 * @param array  $list
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function inList($name='', $list=array(), $custom_message='')
	{
		if(!$_POST)return $this;

		$name = str_replace('[]', '', $name);
		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || empty($_POST[$name]))return $this;

		if(!is_array($_POST[$name]))
		{
			if(!in_array($_POST[$name], $list))
			{
				$msg = $this->_getErrorMsg(28, array($name, join(', ', $list)), $custom_message);
				$this->formErrorStack[$name][] = $msg;
			}
		}
		else
		{
			foreach($_POST[$name] as $v)
			{
				if(!in_array($v, $list))
				{
					$msg = $this->_getErrorMsg(28, array($name, join(', ', $list)), $custom_message);
					$this->formErrorStack[$name][] = $msg;
					break;
				}
			}
		}


		return $this;
	}


	/**
	 * Check if input value is conform
	 *
	 * @param string $name
	 * @param string $operator (=, >, >=, <, <=, in)
	 * @param string $value
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function value($name='', $operator, $value, $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		// =
		if(in_array($operator, array('=', '==')) && $_POST[$name] != $value)
		{
			$msg = $this->_getErrorMsg(1, array($name, $value), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}
		// <
		elseif($operator == '<' && $_POST[$name] >= $value)
		{
			$msg = $this->_getErrorMsg(2, array($name, $value), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}
		// <=
		elseif($operator == '<=' && $_POST[$name] > $value)
		{
			$msg = $this->_getErrorMsg(3, array($name, $value), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}
		// >
		elseif($operator == '>' && $_POST[$name] <= $value)
		{
			$msg = $this->_getErrorMsg(4, array($name, $value), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}
		// >=
		elseif($operator == '>=' && $_POST[$name] < $value)
		{
			$msg = $this->_getErrorMsg(5, array($name, $value), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}
		// in
		elseif(strtolower($operator) == 'in' && !in_array($_POST[$name], $value))
		{
			$msg = $this->_getErrorMsg(6, array($name, join(', ', $value)), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value if email
	 *
	 * @param string $name
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function email($name='', $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		if(!empty($_POST[$name]) && !filter_var($_POST[$name], FILTER_VALIDATE_EMAIL))
		{
			$msg = $this->_getErrorMsg(7, array($name), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value is alpha with latin accent allowed
	 *
	 * @param string    $name
	 * @param array     $added
	 * @param bool|true $uppercase
	 * @param bool|true $lowercase
	 * @param string    $custom_message
	 *
	 * @return $this
	 */
	public function alphaLatin($name='', $added=array(), $uppercase=true, $lowercase=true, $custom_message='')
	{
		return $this->alpha($name, $added, $uppercase, $lowercase, true, $custom_message);
	}

	/**
	 * Verify if input value is alphanumeric with latin accent allowed
	 *
	 * @param string    $name
	 * @param array     $added
	 * @param bool|true $uppercase
	 * @param bool|true $lowercase
	 * @param string    $custom_message
	 *
	 * @return $this
	 */
	public function alphaNumericLatin($name='', $added=array(), $uppercase=true, $lowercase=true, $custom_message='')
	{
		return $this->alphaNumeric($name, $added, $uppercase, $lowercase, true, $custom_message);
	}

	/**
	 * Verify if input value is alpha
	 *
	 * @param string     $name
	 * @param array      $added
	 * @param bool|true  $uppercase
	 * @param bool|true  $lowercase
	 * @param bool|false $latin
	 * @param string     $custom_message
	 *
	 * @return $this
	 */
	public function alpha($name='', $added=array(), $uppercase=true, $lowercase=true, $latin=false, $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		$joined = join('', $added);

		$str = '';
		if($uppercase)$str .= 'a-z';
		if($lowercase)$str .= 'A-Z';
		if($latin && $lowercase)
			$joined .= "àâäéèêëîïöôùûüç";

		if(!empty($_POST[$name]) && !preg_match("/^[{$str}{$joined}]+$/", $_POST[$name]))
		{
			if(!count($added))
				$msg = $this->_getErrorMsg(8, array($name), $custom_message);
			else
				$msg = $this->_getErrorMsg(9, array($name, join('', $added)), $custom_message);

			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value is alphanumeric
	 *
	 * @param string     $name
	 * @param array      $added
	 * @param bool|true  $uppercase
	 * @param bool|true  $lowercase
	 * @param bool|false $latin
	 * @param string     $custom_message
	 *
	 * @return $this
	 */
	public function alphaNumeric($name='', $added=array(), $uppercase=true, $lowercase=true, $latin=false, $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		$joined = join('', $added);

		$str = '';
		if($uppercase)$str .= 'a-z';
		if($lowercase)$str .= 'A-Z';
		if($latin && $lowercase)
			$joined .= "àâäéèêëîïöôùûüç";

		if(!empty($_POST[$name]) && !preg_match("/^[{$str}0-9{$joined}]+$/", $_POST[$name]))
		{
			if(!count($added))
				$msg = $this->_getErrorMsg(10, array($name), $custom_message);
			else
				$msg = $this->_getErrorMsg(11, array($name, join('', $added)), $custom_message);

			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value is digit
	 *
	 * @param string $name
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function digit($name='', $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		if(!empty($_POST[$name]) && !preg_match("/^[0-9 ]+$/", $_POST[$name]))
		{
			$msg = $this->_getErrorMsg(12, array($name), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value is float
	 *
	 * @param string $name
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function float($name='', $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		if(!empty($_POST[$name]) && !filter_var($_POST[$name], FILTER_VALIDATE_FLOAT))
		{
			$msg = $this->_getErrorMsg(13, array($name), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value is date
	 *
	 * @param string $name
	 * @param string $format (d => day, m => month, y => year 2 digits, Y => 4 digits)
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function date($name='', $format='', $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		if(empty($format))
		{
			if($this->formLang == 'fr')
				$format = 'd/m/Y';
			else
				$format = 'm/d/Y';
		}

		if(!empty($_POST[$name]))
		{
			$d = \DateTime::createFromFormat($format, $_POST[$name]);
			if(!$d || $d->format($format) != $_POST[$name])
			{
				$msg = $this->_getErrorMsg(14, array($name, $format), $custom_message);
				$this->formErrorStack[$name][] = $msg;
			}
		}

		return $this;
	}

	/**
	 * Add a custom error
	 *
	 * @param string $name
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function addError($name='', $custom_message)
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;

		$this->formErrorStack[$name][] = $this->_getErrorMsg(0, array($name), $custom_message);

		return $this;
	}


	/**
	 * Verify if input value length >= minimum
	 *
	 * @param string $name
	 * @param int $min
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function min($name='', $min, $custom_message='')
	{
		return $this->charLength($name, $min, 0, $custom_message);
	}

	/**
	 * Verify if input value length <= maximum
	 *
	 * @param string $name
	 * @param int $max
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function max($name='', $max, $custom_message='')
	{
		return $this->charLength($name, 0, $max, $custom_message);
	}

	/**
	 * Verify if input value length is between minimum and maximum
	 *
	 * @param string $name
	 * @param int    $min ( 0 => no control)
	 * @param int    $max ( 0 => no control)
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function charLength($name='', $min=0, $max=0, $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]))
			return $this;


		if($min > 0 && $max > 0)
		{
			if($min == $max && strlen(trim($_POST[$name])) != $max)
			{
				$msg = $this->_getErrorMsg(18, array($name, $max), $custom_message);
				$this->formErrorStack[$name][] = $msg;
			}
			elseif(strlen(trim($_POST[$name])) < $min && strlen(trim($_POST[$name])) > $max)
			{
				$msg = $this->_getErrorMsg(19, array($name, $min, $max), $custom_message);
				$this->formErrorStack[$name][] = $msg;
			}

		}
		elseif(($min > 0 && !$max) && strlen(trim($_POST[$name])) < $min)
		{
			$msg = $this->_getErrorMsg(16, array($name, $min), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}
		elseif((!$min && $max > 0) && strlen(trim($_POST[$name])) > $max)
		{
			$msg = $this->_getErrorMsg(17, array($name, $max), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value match pattern regex
	 *
	 * @param string $name
	 * @param string $pattern
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function regex($name, $pattern, $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]) || empty($_POST[$name]))
			return $this;

		if(!preg_match($pattern, $_POST[$name]))
		{
			$msg = $this->_getErrorMsg(20, array($name, $pattern), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value is a correct url
	 *
	 * @param string $name
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function url($name='', $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]) || empty($_POST[$name]))
			return $this;

		$pattern = "#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;\"\':<]|\.\s|$)#i";
		if(!preg_match($pattern, $_POST[$name]))
		{
			$msg = $this->_getErrorMsg(27, array($name), $custom_message);
			$this->formErrorStack[$name][] = $msg;
		}

		return $this;
	}

	/**
	 * Verify if input value match mask
	 *
	 * @param string $name
	 * @param string $mask (A => upper alpha, a => lower alpha, 9 => digit, * => all characters)
	 * @param string $custom_message
	 *
	 * @return $this
	 */
	public function mask($name, $mask, $custom_message='')
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$this->exists($name) || is_array($_POST[$name]) || empty($_POST[$name]))
			return $this;

		$str = $_POST[$name];

		if(strlen($str) != strlen($mask))
		{
			$msg = $this->_getErrorMsg(21, array($name, $mask), $custom_message);
			$this->formErrorStack[$name][] = $msg;
			return $this;
		}

		$error = false;
		for($i=0; $i < strlen($str); $i++)
		{
			// mask digit
			if($mask[$i] == 9 && !ctype_digit($str[$i]))
				$error = true;

			// mask alpha upper
			if($mask[$i] == 'A' && !ctype_upper($str[$i]))
				$error = true;

			// mask alpha lower
			if($mask[$i] == 'a' && !ctype_lower($str[$i]))
				$error = true;

			// other
			if($mask[$i] != 9 && $mask[$i] != 'A'  && $mask[$i] != 'a' && $mask[$i] != '*' && $mask[$i] != $str[$i])
				$error = true;

			if($error)
			{
				$msg = $this->_getErrorMsg(21, array($name, $mask), $custom_message);
				$this->formErrorStack[$name][] = $msg;
				return $this;
			}

		}

		return $this;

	}

	/**
	 * Verify input upload file
	 *
	 * @param string     $name
	 * @param bool|false $required
	 * @param string     $size file size with unit (ko, mo, go)
	 * @param string     $extensions separated by comma
	 * @param string     $mimes mime type separated by comma
	 *
	 * @return $this
	 */
	public function fileControl($name, $required=false, $size='', $extensions='', $mimes='')
	{
		if(!$_POST)return $this;

		if(!isset($_FILES[$name]) && !$required)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!$required && !$_FILES[$name]['size'])
			return $this;

		// empty
		if((!isset($_FILES[$name]) && $required) || $_FILES[$name]['error'] != 0 || !is_uploaded_file($_FILES[$name]['tmp_name']))
		{
			$tmp_error = (isset($_FILES[$name]['error'])) ? $_FILES[$name]['error'] : '-';
			$msg = $this->_getErrorMsg(22, array($name, $tmp_error));
			$this->formErrorStack[$name][] = $msg;
			return $this;
		}

		// size
		$size_original = $size;
		if(empty($size)) $size = ini_get('upload_max_filesize');
		if(empty($size) || !$size) $size = '500 Ko';

		$size = str_replace(' ', '', strtolower($size));
		if(strpos($size, 'ko') !== false) $size *= 1024;
		if(strpos($size, 'mo') !== false) $size *= 1024 * 1024;
		if(strpos($size, 'go') !== false) $size *= 1024 * 1024 * 1024;
		$size = (int)$size;

		if($_FILES[$name]['size'] > $size)
		{
			$msg = $this->_getErrorMsg(23, array($name, $size_original));
			$this->formErrorStack[$name][] = $msg;
			return $this;
		}

		// extensions
		if(!empty($extensions))
		{
			$extensions = str_replace(array(' ', '.'), '', trim(strtolower($extensions)));
			$extensions = explode(',', $extensions);

			$cur_ext = explode('.', $_FILES[$name]['name']);
			$cur_ext = end($cur_ext);
			$cur_ext = strtolower($cur_ext);

			$cur_ext_3 = $cur_ext;

			if(strlen($cur_ext_3) > 3)
			{
				$cur_ext_3 = substr($cur_ext, 0, 3);
				$cur_ext_3 .= '*';
			}


			if(!in_array($cur_ext, $extensions) && !in_array($cur_ext_3, $extensions))
			{
				$msg = $this->_getErrorMsg(24, array($name, join(', ', $extensions)));
				$this->formErrorStack[$name][] = $msg;
				return $this;
			}
		}

		// mimes
		if(!empty($mimes))
		{
			$mimes = trim(strtolower($mimes));
			$mimes = str_replace(' ', '', $mimes);
			$mimes = explode(',', $mimes);

			$cur_mime = strtolower(trim($_FILES[$name]['type']));

			if(!in_array($cur_mime, $mimes))
			{
				$msg = $this->_getErrorMsg(25, array($name, join(', ', $mimes)));
				$this->formErrorStack[$name][] = $msg;
				return $this;
			}
		}


		return $this;
	}

	/**
	 * Veirfy if upload file is an image with dimension restriction
	 *
	 * @param string $name
	 * @param string $width_operator
	 * @param int    $width (0 = no control)
	 * @param string $height_operator
	 * @param int    $height (0 = no control)
	 *
	 * @return $this
	 */
	public function imageDimension($name='', $width_operator='=', $width=0, $height_operator='=', $height=0)
	{
		if(!$_POST)return $this;

		if(empty($name))$name = $this->formLastInputName;
		$this->formLastInputName = $name;

		if(!isset($_FILES[$name]) || !$_FILES[$name]['size'] || $_FILES[$name]['error'])return $this;

		// force extension
		$cur_ext = explode('.', strtolower(trim($_FILES[$name]['name'])));
		$cur_ext = end($cur_ext);

		$extensions = array('jpg', 'jpeg', 'png', 'gif');
		if(!in_array($cur_ext, $extensions))
		{
			$msg = $this->_getErrorMsg(24, array($name, join(', ', $extensions)));
			$this->formErrorStack[$name][] = $msg;
			return $this;
		}

		// force mime check
		$mimes = array('image/jpg', 'image/jpeg','image/pjpeg', 'image/png', 'image/gif');
		$cur_mime = strtolower(trim($_FILES[$name]['type']));

		if(!in_array($cur_mime, $mimes))
		{
			$msg = $this->_getErrorMsg(25, array($name, join(', ', $mimes)));
			$this->formErrorStack[$name][] = $msg;
			return $this;
		}

		// check width and heigth
		$dimensions = getimagesize($_FILES[$name]['tmp_name']);
		$cur_width = $dimensions[0];
		$cur_height = $dimensions[1];

		$error = false;
		if($width && $width_operator == '=' && $width != $cur_width) $error = true;
		elseif($width && $width_operator == '<' && $cur_width  >= $width) $error = true;
		elseif($width && $width_operator == '<=' && $cur_width > $width) $error = true;
		elseif($width && $width_operator == '>' && $cur_width <= $width) $error = true;
		elseif($width && $width_operator == '>=' && $cur_width < $width) $error = true;

		if($height && $height_operator == '=' && $height != $cur_height) $error = true;
		elseif($height && $height_operator == '<' && $cur_height >= $height) $error = true;
		elseif($height && $height_operator == '<=' && $cur_height > $height) $error = true;
		elseif($height && $height_operator == '>' && $cur_height <= $height) $error = true;
		elseif($height && $height_operator == '>=' && $cur_height < $height) $error = true;


		if($error)
		{
			$width_label = (!$width) ? '-' : "{$width_operator} {$width}";
			$height_label = (!$height) ? '-' : "{$height_operator} {$height}";
			$msg = $this->_getErrorMsg(26, array($name, $width_label, $height_label));
			$this->formErrorStack[$name][] = $msg;
		}


		return $this;
	}

	/**
	 * Get error count
	 *
	 * @return int
	 */
	public function formGetErrorCount()
	{
		return count($this->formErrorStack);
	}

	/**
	 * Get all error message stack
	 *
	 * @return array
	 */
	public function formGetErrorMessage()
	{
		return $this->formErrorStack;
	}

	/**
	 * Check if form is posted
	 *
	 * @return mixed
	 */
	public function formIsPosted()
	{
		return $_POST;
	}

	/**
	 * Apply Xss purifier for a string
	 *
	 * @param string $str
	 * @param bool|true $strip_tags
	 * @param string    $strip_tags_allowed
	 *
	 * @return mixed|string
	 */
	public function xssProtect($str, $strip_tags=true, $strip_tags_allowed='')
	{
		// tpln code protect
		if($strip_tags)$str = strip_tags($str, $strip_tags_allowed);

		$str = str_replace('{ @', '{ !', $str);
		$str = str_replace('<!-- ajax::', '<!-- ajax ', $str);
		$str = str_replace('<!-- /ajax::', '<!-- /ajax ', $str);
		$str = str_replace('{ const', '{ ! const', $str);
		$str = str_replace('{ get', '{ ! get', $str);
		$str = str_replace('{ globals', '{ ! globals', $str);
		$str = str_replace('{ post', '{ ! post', $str);
		$str = str_replace('{ cookie', '{ ! cookie', $str);
		$str = str_replace('{ server', '{ ! server', $str);
		$str = str_replace('{ session', '{ ! session', $str);

		// remove invisble characters
		$non_displayables = array();
		$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
		$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127
		do {
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while($count);

		// naughty scripting
		$str = preg_replace('#(alert|cmd|passthru|eval|shell_exec|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#i',
					'[XSS-PROTECT:\\1] &#40;\\3&#41;', $str);

		// never allowed
		$_never_allowed_str = array('document.cookie', 'document.write', '.parentNode', '.innerHTML', 'window.location', '-moz-binding');
		$str = str_replace($_never_allowed_str, '[REMOVED]', $str);

		$_never_allowed_regex = array('javascript\s*:', 'expression\s*(\(|&\#40;)', 'vbscript\s*:', 'Redirect\s+302', "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?");
		foreach($_never_allowed_regex as $reg)
			$str = preg_replace('#'.$reg.'#is', '[removed]', $str);

		// remove php code
		$str = str_ireplace(array('<?php', '<%', '<?=', '<?', '?>'), '', $str);

		// remove xss
		$str = str_ireplace(array("&lt;", "&gt;"), array("&amp;lt;", "&amp;gt;"), $str);
		$str = preg_replace('#(&\#*\w+)[\s\r\n]+;#U', "$1;", $str);
		$str = preg_replace('#(<[^>]+[\s\r\n\"\'])(on|xmlns)[^>]*>#iU', "$1>", $str);
		$str = preg_replace('#(<[^>]+[\s\r\n\"\'])(on|xmlns)[^>]*>#iU', "$1>", $str);
		$str = preg_replace('#([a-z]*)[\s\r\n]*=[\s\n\r]*([\`\'\"]*)[\\s\n\r]*j[\s\n\r]*a[\s\n\r]*v[\s\n\r]*a[\s\n\r]*s[\s\n\r]*c[\s\n\r]*r[\s\n\r]*i[\s\n\r]*p[\s\n\r]*t[\s\n\r]*:#iU', '$1=$2nojavascript...', $str);
		$str = preg_replace('#([a-z]*)[\s\r\n]*=([\'\"]*)[\s\n\r]*v[\s\n\r]*b[\s\n\r]*s[\s\n\r]*c[\s\n\r]*r[\s\n\r]*i[\s\n\r]*p[\s\n\r]*t[\s\n\r]*:#iU', '$1=$2novbscript...', $str);
		$str = preg_replace('#(<[^>]+)style[\s\r\n]*=[\s\r\n]*([\`\'\"]*).*expression[\s\r\n]*\([^>]*>#iU', "$1>", $str);
		$str = preg_replace('#(<[^>]+)style[\s\r\n]*=[\s\r\n]*([\`\'\"]*).*s[\s\n\r]*c[\s\n\r]*r[\s\n\r]*i[\s\n\r]*p[\s\n\r]*t[\s\n\r]*:*[^>]*>#iU', "$1>", $str);
		$str = preg_replace('#</*\w+:\w[^>]*>#i', "", $str);

		do
		{
			$oldstring = $str;
			$str = preg_replace('#</*(style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i', "", $str);
		}
		while($oldstring != $str);

		return $str;
	}


	/**
	 * Return if form is valid
	 *
	 * @return bool
	 */
	public function formIsValid()
	{
		if(empty($this->formName))
		{
			$this->_trigger_error("form not defined, please use formSetName method", E_USER_ERROR, true, false);
			return false;
		}

		$pattern = "<form name=\"$this->formName\"[^>]*>(.*)<\/form>";

		if(!preg_match("/$pattern/mis", $this->t->buffer, $arr))
		{
			$this->_trigger_error("form not found", E_USER_ERROR, true, false);
			return false;
		}

		$form_original = $arr[0];
		$form_parsed = $arr[0];

		// parse or init values
		if($_POST || count($this->formValues) > 0)
		{
			$error_names = array_keys($this->formErrorStack);

			// grep inputs
			$inputs = array();
			if(preg_match_all("/<input[^>]*>/i", $form_original, $arr))
			{
				foreach($arr[0] as $input)
				{
					$type = $this->extractString($input, 'type="', '"');
					$name = $this->extractString($input, 'name="', '"');
					$value = $this->extractString($input, 'value="', '"');
					$class = $this->extractString($input, 'class="', '"');

					if(!empty($name))
					{
						$inputs[$name][] = array('type' => $type, 'name' => $name, 'value' => $value, 'class' => $class, 'html' => $input, 'parsed' => $input);
					}
				}
			}

			foreach($inputs as $input_name => $cur_inputs)
			{
				$input_name = str_replace('[]', '', $input_name);
				foreach($cur_inputs as $cur)
				{
					// RADIO - CHECKBOX
					if(strtolower($cur['type']) == 'radio' || strtolower($cur['type']) == 'checkbox')
					{
						if($_POST)
						{
							$val = (!isset($_POST[$input_name])) ? '' : $_POST[$input_name];

							// checked ?
							$cur['parsed'] = str_replace(array('checked="checked"', 'checked'), "", $cur['parsed']);
							if(
									(!is_array($val) && $cur['value'] == $val) ||
									(is_array($val) && in_array($cur['value'], $val))
							  )
							{
								$cur['parsed'] = str_replace('<input ', "<input checked=\"checked\" ", $cur['parsed']);
							}

							// error
							if(in_array($input_name, $error_names))
							{
								if(!$cur['class'])
									$cur['parsed'] = str_replace('<input ', "<input class=\"{$this->formErrorCssClass}\" ", $cur['parsed']);
								else
								{
									$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
									$cur['parsed'] = str_replace("class=\"{$cur['class']}\"", "class=\"$tmp {$this->formErrorCssClass}\"", $cur['parsed']);
								}
							}
							elseif($cur['class'])
							{
								$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
								$cur['parsed'] = str_replace("class=\"{$cur['class']}\"", "class=\"$tmp\" ", $cur['parsed']);
							}
						}
						elseif(isset($this->formValues[$input_name]))
						{
							$cur['parsed'] = str_replace(array('checked="checked"', 'checked'), "", $cur['parsed']);
							if(
									(!is_array($this->formValues[$input_name]) && $cur['value'] == $this->formValues[$input_name]) ||
									(is_array($this->formValues[$input_name]) && in_array($cur['value'], $this->formValues[$input_name]))
							   )
							{
								$cur['parsed'] = str_replace('<input ', "<input checked=\"checked\" ", $cur['parsed']);
							}
						}
					}
					else
					{
						$init_val = false;
						if($_POST)
						{
							$init_val = true;

							$val = '';
							if(isset($_POST[$input_name]))
							{
								if(!is_array($_POST[$input_name]))
								{
									$_POST[$input_name] = str_replace('"', "`", $_POST[$input_name]);
									$val = $_POST[$input_name];
								}
							}

							if($cur['type'] == 'file')$init_val = false;

							// error
							if(in_array($input_name, $error_names))
							{
								if(!$cur['class'])
									$cur['parsed'] = str_replace('<input ', "<input class=\"{$this->formErrorCssClass}\" ", $cur['parsed']);
								else
								{
									$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
									$cur['parsed'] = str_replace("class=\"{$cur['class']}\"", "class=\"$tmp {$this->formErrorCssClass}\"", $cur['parsed']);
								}
							}
							elseif($cur['class'])
							{
								$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
								$cur['parsed'] = str_replace("class=\"{$cur['class']}\"", "class=\"$tmp\" ", $cur['parsed']);
							}

						}
						elseif(isset($this->formValues[$input_name]))
						{
							$init_val = true;
							$val = $this->formValues[$input_name];
						}


						if($init_val)
						{
							if(!$cur['value'])
							{
								$cur['parsed'] = str_replace('<input ', "<input value=\"{$val}\" ", $cur['parsed']);
							}
							else
							{
								$cur['parsed'] = str_replace("value=\"{$cur['value']}\"", "value=\"{$val}\"", $cur['parsed']);
							}
						}
					}

					$form_parsed = str_replace($cur['html'], $cur['parsed'], $form_parsed);

				}
			}

			// grep textarea
			$textareas = array();
			if(preg_match_all("/<textarea[^>]*>/i", $form_original, $arr))
			{
				foreach($arr[0] as $textarea)
				{
					$name = $this->extractString($textarea, 'name="', '"');
					$class = $this->extractString($textarea, 'class="', '"');
					$value = $this->extractString($form_original, $textarea, '</textarea>');
					$node = $this->extractString($form_original, $textarea, '</textarea>', true);

					if(!empty($name))
					{
						$textareas[$name][] = array('name' => $name, 'value' => $value, 'class' => $class, 'html' => $textarea, 'html-original' => $textarea, 'parsed' => $node, 'node' => $node);
					}
				}
			}

			foreach($textareas as $textarea_name => $textarea)
			{
				foreach($textarea as $cur)
				{
					$init_val = false;
					if($_POST)
					{
						$init_val = true;
						$val = (!isset($_POST[$textarea_name])) ? '' : $_POST[$textarea_name];

						// error ?
						if(in_array($textarea_name, $error_names))
						{
							if(!$cur['class'])
							{
								$cur['html'] = str_replace('<textarea ', "<textarea class=\"{$this->formErrorCssClass}\" ", $cur['html']);
							}
							else
							{
								$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
								$cur['html'] = str_replace("class=\"{$cur['class']}\"", "class=\"{$tmp} {$this->formErrorCssClass}\"", $cur['html']);
							}
						}
						elseif($cur['class'])
						{
							$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
							$cur['html'] = str_replace("class=\"{$cur['class']}\"", "class=\"{$tmp}\"", $cur['html']);
						}

						// replace in node
						$cur['parsed'] = str_replace($cur['html-original'], $cur['html'], $cur['parsed']);

					}
					elseif(isset($this->formValues[$textarea_name]))
					{
						$init_val = true;
						$val = $this->formValues[$textarea_name];
					}

					if($init_val)
					{
						// $val = $this->xssProtect($val, false);
						$cur['parsed'] = str_replace('</textarea>', "{$val}</textarea>", $cur['parsed']);
					}


					$form_parsed = str_replace($cur['node'], $cur['parsed'], $form_parsed);

				}

			}

			// grep select
			$selects = array();
			if(preg_match_all("/<select[^>]*>/i", $form_original, $arr))
			{
				foreach($arr[0] as $select)
				{
					$name = $this->extractString($select, 'name="', '"');
					$class = $this->extractString($select, 'class="', '"');
					$value = $this->extractString($form_original, $select, '</select>');
					$node = $this->extractString($form_original, $select, '</select>', true);

					$options = array();
					if(preg_match_all("/<option[^>]*>/i", $node, $arr2))
					{
						foreach($arr2[0] as $option)
						{
							$class2 = $this->extractString($option, 'class="', '"');
							$value2 = $this->extractString($option, 'value="', '"');
							$node2 = $option;

							$options[] = array('class' => $class2, 'value' => $value2, 'node' => $node2, 'parsed' => $option);
						}
					}

					if(!empty($name))
					{
						$selects[$name][] = array('name' => $name, 'value' => $value, 'class' => $class, 'html' => $select, 'html-original' => $select, 'parsed' => $node, 'node' => $node, 'options' => $options);
					}
				}
			}


			foreach($selects as $select_name => $select)
			{
				$select_name = str_replace('[]', '', $select_name);

				foreach($select as $cur)
				{
					$init_val = false;

					if($_POST)
					{
						$init_val = true;
						$val = (!isset($_POST[$select_name])) ? '' : $_POST[$select_name];
					}
					elseif(isset($this->formValues[$select_name]))
					{
						$init_val = true;
						$val = $this->formValues[$select_name];
					}

					// init value
					if($init_val)
					{
						foreach($cur['options'] as $option)
						{
							$option['parsed'] = str_replace(array('selected="selected', 'selected'), '', $option['parsed']);
							if(
								(!is_array($val) && $option['value'] == $val) ||
								(is_array($val) && in_array($option['value'], $val))
							  )
							{
								$option['parsed'] = str_replace('<option ', "<option selected=\"selected\" ", $option['parsed']);
							}

							$cur['parsed'] = str_replace($option['node'], $option['parsed'], $cur['parsed']);
						}
					}

					// error
					if($_POST)
					{
						if(in_array($select_name, $error_names))
						{
							if(!$cur['class'])
							{
								$cur['parsed'] = str_replace('<select ', "<select class=\"{$this->formErrorCssClass}\" ", $cur['parsed']);
							}
							else
							{
								$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
								$cur['parsed'] = str_replace("class=\"{$cur['class']}\"", "class=\"{$tmp} {$this->formErrorCssClass}\"", $cur['parsed']);
							}
						}
						elseif($cur['class'])
						{
							$tmp = str_replace($this->formErrorCssClass, '', $cur['class']);
							$cur['parsed'] = str_replace("class=\"{$cur['class']}\"", "class=\"{$tmp}\"", $cur['parsed']);
						}

					}


					$form_parsed = str_replace($cur['node'], $cur['parsed'], $form_parsed);
				}
			}

			// form replacement
			$this->t->buffer = str_replace($form_original, $form_parsed, $this->t->buffer);
		}



		if(!$_POST)
		{
			if($this->blocExists('form_valid'))$this->eraseBloc('form_valid');
			$this->eraseBloc('form_errors');
		}
		else
		{
			// form error or valid ?
			if($this->formGetErrorCount() > 0)
			{
				foreach($this->formErrorStack as $input => $msgs)
				{
					$msgs = array_unique($msgs);
					foreach($msgs as $msg)
					{
						$this->parse('form_error.message', $msg);
						$this->loop('form_error');
					}
				}

				if($this->blocExists('form_valid'))
					$this->eraseBloc('form_valid');

				return false;
			}
			else
			{
				$this->eraseBloc('form_errors');

				// kill form
				if($this->blocExists('form_valid'))
				{
					$this->t->buffer = str_replace($form_parsed, '', $this->t->buffer);
				}

				return true;
			}
		}
	}

	/**
	 * Extract a string between start and end delimiter
	 *
	 * @param     $str
	 * @param     $start
	 * @param     $end
	 * @param boolean|int $inc_markup return delimiter in final string
	 *
	 * @return bool|string
	 */
	public function extractString($str, $start, $end, $inc_markup=false)
	{
		$pos_start = strpos($str, $start);

		if($inc_markup)
			$pos_end = strpos($str, $end, ($pos_start));
		else
			$pos_end = strpos($str, $end, ($pos_start + strlen($start)));

		if(($pos_start !== false) && ($pos_end !== false))
		{
			if($inc_markup)
			{
				$pos1 = $pos_start;
				$pos2 = ($pos_end + strlen($end)) - $pos1;
			}
			else
			{
				$pos1 = $pos_start + strlen($start);
				$pos2 = $pos_end - $pos1;
			}

			return substr($str, $pos1, $pos2);
		}

		return false;
	}

}