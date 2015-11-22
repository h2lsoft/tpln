<?php
/**
 * TPLN template engine - main class
 *
 * @author H2LSOFT
 * @website http://tpln.h2lsoft.com
 * @license LGPL
 * @version 5.0
 * @package Template Engine
 */

// @todo> doclet


// includes
define('TPLN_PATH', dirname(__FILE__));

include_once(__DIR__.'/lang/form_error.inc.php');
include_once(__DIR__.'/TPLN_Form.php');
class Tpln_Engine extends TPLN_Form
{
	private $lang = 'en';
	private $stack;
	private $tpl_index = -1;
	private $DOCUMENT_ROOT;
	protected $t;

	/**
	 * Tpln_Engine constructor
	 *
	 * @param string $lang (default en)
	 * @param string $document_root (default $_SERVER['DOCUMENT_ROOT'])
	 */
	public function __construct($lang='en', $document_root='')
	{
		$this->lang = $lang;
		$this->formLang = $lang;
		$this->DOCUMENT_ROOT = !empty($document_root) ? $document_root : $_SERVER['DOCUMENT_ROOT'];
	}

	protected function _trigger_error($err_msg, $err_type=E_USER_NOTICE, $err_msg_append_template=true, $err_msg_append_backtrace=true)
	{
		$error_message = htmlspecialchars($err_msg);
		if($err_msg_append_template)
		{
			$error_message .= " in template `{$this->stack[$this->tpl_index]->file}`";
		}

		if($err_msg_append_backtrace)
		{
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
			if(count($backtrace) >= 2)
			{
				$backtrace[1]['file'] = str_replace($this->DOCUMENT_ROOT, "", $backtrace[1]['file']);
				$backtrace[1]['file'] = trim($backtrace[1]['file']);
				$error_message .= " in `{$backtrace[1]['file']}` line {$backtrace[1]['line']}";
			}
		}

		trigger_error($error_message, $err_type);
	}

	/**
	 * Get current template index
	 *
	 * @return int
	 */
	public function getTemplateIndex()
	{
		return $this->tpl_index;
	}

	/**
	 * Set current template index
	 *
	 * @param $index
	 */
	public function setTemplateIndex($index)
	{
		$this->tpl_index = $index; $this->t = &$this->stack[$this->tpl_index];
	}

	/**
	 * Set template buffer
	 *
	 * @param string $buffer
	 * @param string $file (by default file name is virtual)
	 */
	public function setTemplateBuffer($buffer, $file='virtual')
	{
		// register
		$this->stack[++$this->tpl_index] = new stdClass();

		$this->t = &$this->stack[$this->tpl_index];
		$this->t->dir = basename($file);
		$this->t->file = $file;
		$this->t->buffer = $buffer;
		$this->t->blocs = array();

		// direct include file and reload buffer
		$pattern = "/\{ @include file=\"+([a-zA-Z0-9_.-]*?)\" \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		if($match)
		{
			for($i=0; $i < count($tab[1]); $i++)
			{
				$include_file = $tab[1][$i];
				if(!file_exists($include_file))
				{
					$this->_trigger_error("File '$include_file' not found", E_USER_WARNING);
					$val = '';
				}
				else
				{
					$val = file_get_contents($include_file);
				}

				$this->t->buffer = str_replace($tab[0][$i], $val, $this->t->buffer);
			}
		}

		// capture constants
		$pattern = "/\{ const\(+([a-zA-Z0-9_]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->contants = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture @if .. @endif
		$pattern = "/\{ @if\(+(.*)?\) \}(.*)?\{ @endif \}/msU";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->if_block = (count($tab) > 1) ? $tab[0] : array();

		// capture globals
		$pattern = "/\{ globals\(+([a-zA-Z0-9_'->\[\]]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->globals = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture php get
		$pattern = "/\{ get\(+([a-zA-Z0-9_]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->get = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture php post
		$pattern = "/\{ post\(+([a-zA-Z0-9_]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->post = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture php cookie
		$pattern = "/\{ cookie\(+([a-zA-Z0-9_]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->cookie = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture php server
		$pattern = "/\{ server\(+([a-zA-Z0-9_]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->stack[$this->tpl_index]->server = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture php session
		$pattern = "/\{ session\(+([a-zA-Z0-9_]*?)\) \}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->session = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture item
		$pattern = "/\{([a-zA-Z0-9_]*?)\}/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);;
		$this->t->items = (count($tab) > 1) ? array_unique($tab[1]) : array();

		// capture all blocs
		$pattern = "/\<bloc::([a-zA-Z0-9_]*?)\>/U";
		$match = preg_match_all($pattern, $this->stack[$this->tpl_index]->buffer, $tab);
		$this->t->blocs_found = (count($tab) > 1) ? array_unique($tab[1]) : array();
		$this->t->blocs_root = array();
		$this->t->path_defined = array();

		// store root item
		$this->t->cmd = array();
		$this->t->cmd['items-vars'] = array();
		$this->t->cmd['items-values'] = array();


		// verify bloc end
		foreach($this->t->blocs_found as $bloc_name)
		{
			if(strpos($this->t->buffer, "</bloc::{$bloc_name}>") === false)
			{
				$this->_trigger_error("bloc `</bloc::$bloc_name>` not found", E_USER_ERROR);
				return;
			}
			else
			{
				// capture bloc
				$pattern = "/<bloc::$bloc_name>(.*)?<\\/bloc::$bloc_name>/msU";
				$match = preg_match_all($pattern, $this->t->buffer, $tab);
				$this->t->blocs[$bloc_name] = new stdClass();
				$this->t->blocs[$bloc_name]->original = $tab[1][0];
				$this->t->blocs[$bloc_name]->parsed = $tab[1][0];
				$this->t->blocs[$bloc_name]->childrens = array();

				// capture bloc items
				$pattern = "/\{([a-zA-Z0-9_]*?)\}/U";
				$match = preg_match_all($pattern, $this->t->blocs[$bloc_name]->original, $tab);;
				$this->t->blocs[$bloc_name]->items = (count($tab) > 1) ? array_unique($tab[1]) : array();
			}
		}

	}

	/**
	 * Open template
	 *
	 * @param $file path
	 */
	public function open($file)
	{
		// check template
		if(!file_exists($file))
		{
			$this->_trigger_error("template `$file` not found", E_USER_ERROR, false);
		}
		else
		{
			$this->setTemplateBuffer(file_get_contents($file), $file);
		}
	}

	/**
	 * Parse automatically array in bloc or erase it, you can use {_Number} to parse current index
	 *
	 * @param string $bloc
	 * @param array $values
	 * @param string $bloc_norecord
	 */
	public function parseArray($bloc, $values, $bloc_norecord='')
	{
		if(!count($values))
		{
			$this->eraseBloc($bloc);
		}
		else
		{
			if(!empty($bloc_norecord))
				$this->eraseBloc($bloc_norecord);

			$i = 1;
			foreach($values as $rec)
			{
				foreach($rec as $key => $val)
				{
					if(!is_array($val))
					{
						if($this->itemExists($key, $bloc))
							$this->parse("{$bloc}.{$key}", $val);
					}
					else
					{
						$j = 1;
						foreach($val as $key2 => $v2)
						{
							$sub_path = "{$bloc}.{$key}";
							foreach($v2 as $k3 => $v3)
							{
								if($this->itemExists($k3, $key))
									$this->parse("{$sub_path}.{$k3}", $v3);
							}

							if($this->itemExists('_number', $key))
								$this->parse("{$sub_path}._number", $j);

							$this->loop($sub_path);
							$j++;
						}
					}
				}

				if($this->itemExists('_number', $bloc))
					$this->parse("$bloc._number", $i);

				$this->loop($bloc);
				$i++;
			}

		}
	}

	/**
	 * Erase item
	 *
	 * @param $var
	 */
	public function eraseItem($var)
	{
		$this->parse($var, "");
	}

	/**
	 * Verify if bloc exists
	 *
	 * @param $bloc
	 *
	 * @return bool
	 */
	public function blocExists($bloc)
	{
		return in_array($bloc, $this->t->blocs_found);
	}

	/**
	 * Replace all bloc with contents
	 *
	 * @param $bloc_path
	 * @param $contents
	 */
	public function parseBloc($bloc_path, $contents)
	{
		$blocs = explode('.', $bloc_path);
		$last_bloc = end($blocs);

		// bloc exists
		if(!$this->blocExists($last_bloc))
		{
			$this->_trigger_error("bloc `<bloc::$last_bloc>` not found", E_USER_ERROR);
			return;
		}

		$this->pathConstructor($bloc_path);

		$b = &$this->t->blocs[$blocs[0]];
		if(count($blocs) == 1)
		{
			$b->parsed = $contents;
		}
		else
		{
			for($i=1; $i < count($blocs); $i++)
			{
				$bloc = $blocs[$i];
				$b = &$b->childrens[$bloc];

				// last bloc
				if($i == count($blocs)-1)
					$b->parsed = $contents;
			}
		}


	}

	/**
	 * Erase bloc
	 *
	 * @param $bloc_path
	 */
	function eraseBloc($bloc_path)
	{
		$this->parseBloc($bloc_path, '');
	}

	/**
	 * Parse item
	 *
	 * @param string $var => item to parse
	 * @param string $value
	 * @param string $functions (assign php function)
	 */
	public function parse($var, $value, $functions='')
	{
		// functions
		if(!empty($functions))
		{
			$funcs = explode('|', $functions);
			$funcs = array_reverse($funcs);

			foreach($funcs as $func)
			{
				if(!empty($func))
					$value = $func($value);
			}
		}


		// simple item
		$item = explode('.', $var);
		if(count($item) == 1)
		{
			$item = $item[0];
			if(!$this->itemExists($item))
			{
				$this->_trigger_error("item `{{$item}}` not found", E_USER_NOTICE);
			}
			else
			{
				$this->t->cmd['items-vars'][] = "{{$var}}";
				$this->t->cmd['items-values'][] = $value;
			}
		}
		else
		{
			// parse in blocs
			$blocs = $item;
			$item = array_pop($blocs);
			$blocs_path = join('.', $blocs);
			$last_bloc = end($blocs);

			// bloc exists
			if(!$this->blocExists($last_bloc))
			{
				$this->_trigger_error("bloc `<bloc::$last_bloc>` not found", E_USER_ERROR);
				return;
			}

			// register bloc
			$this->pathConstructor($blocs_path);

			// bloc items exists
			if(!$this->itemExists($item, $last_bloc))
			{
				$this->_trigger_error("item `{{$item}}` not found  in bloc `<bloc::$last_bloc>`", E_USER_NOTICE);
				return;
			}

			// access to path
			$b = &$this->t->blocs[$blocs[0]];
			if(count($blocs) == 1)
			{
				$b->parsed = str_replace("{{$item}}", $value, $b->parsed);
			}
			else
			{

				for($i=1; $i < count($blocs); $i++)
				{
					$bloc = $blocs[$i];
					$b = &$b->childrens[$bloc];

					// last bloc
					if($i == count($blocs)-1)
					{
						$b->parsed = str_replace("{{$item}}", $value, $b->parsed);
					}
				}
			}
		}
	}

	/**
	 * loop bloc
	 *
	 * @param $bloc_path
	 */
	public function loop($bloc_path)
	{
		$blocs = explode('.', $bloc_path);
		if(count($blocs) == 1)
		{
			$last_bloc = $blocs[0];

			// childrens
			$b = &$this->t->blocs[$last_bloc];
			foreach($b->childrens as $bloc_children => $bloc_children_content)
			{
				// replace children content to parents
				$pattern = "/<bloc::$bloc_children>(.*)?<\\/bloc::$bloc_children>/msU";
				$match = preg_match_all($pattern, $b->parsed, $tab);
				$c = $tab[0][0];

				// clean children
				$b->childrens[$bloc_children]->parsed = str_replace($this->t->blocs[$bloc_children]->original, '', $b->childrens[$bloc_children]->parsed);
				$b->parsed = str_replace($c, $b->childrens[$bloc_children]->parsed, $b->parsed);

				// children reset to original
				$b->childrens[$bloc_children]->parsed = $this->t->blocs[$bloc_children]->original;
			}

			$b->parsed .= $b->original;
		}
		else
		{
			$b = &$this->t->blocs[$blocs[0]];
			for($i=1; $i < count($blocs); $i++)
			{
				$bloc = $blocs[$i];
				$b = &$b->childrens[$bloc];

				// last bloc desired
				if($i == count($blocs)-1)
				{
					// childrens
					foreach($b->childrens as $bloc_children => $bloc_children_content)
					{
						// replace children content to parents
						$pattern = "/<bloc::$bloc_children>(.*)?<\\/bloc::$bloc_children>/msU";
						$match = preg_match_all($pattern, $b->parsed, $tab);

						$b->childrens[$bloc_children]->parsed = str_replace($this->t->blocs[$bloc_children]->original, '', $b->childrens[$bloc_children]->parsed);

						if(isset($tab[0][0]))
						{
							$c = $tab[0][0];
							$b->parsed = str_replace($c, $b->childrens[$bloc_children]->parsed, $b->parsed);
						}


						// children reset to original
						$b->childrens[$bloc_children]->parsed = $this->t->blocs[$bloc_children]->original;
					}

					$b->parsed .= $this->t->blocs[$bloc]->original;
				}
			}
		}

	}



	private function pathConstructor($path)
	{
		if(in_array($path, $this->t->path_defined))return;

		$blocs = explode('.', $path);

		if(!in_array($blocs[0], $this->t->blocs_root))
			$this->t->blocs_root[] = $blocs[0];

		// register sub blocs
		$b = &$this->t->blocs[$blocs[0]];
		$path = $blocs[0];
		for($i=1; $i < count($blocs); $i++)
		{
			$bloc = $blocs[$i];
			$path .= ".{$bloc}";

			if(!isset($b->childrens[$bloc]))
			{
				$b->childrens[$bloc] = new stdClass();
				$b->childrens[$bloc]->path = $path;
				$b->childrens[$bloc]->parsed = $this->t->blocs[$bloc]->original;
				$b->childrens[$bloc]->childrens = array();
			}

			$b = &$b->childrens[$bloc];
		}
	}

	/**
	 * Verify if item exists
	 *
	 * @param        $item
	 * @param string $bloc
	 *
	 * @return bool
	 */
	public function itemExists($item, $bloc='')
	{
		if(empty($bloc))
			return in_array($item, $this->t->items);
		else
			return in_array($item, $this->t->blocs[$bloc]->items);
	}


	/**
	 * Render template
	 *
	 * @return mixed
	 */
	public function render()
	{
		// parse php instruction
		foreach($this->t->if_block as $if_block)
		{
			$if_block_original = $if_block;

			// remove php tags
			$if_block = str_replace('<?php', '&lt;?php', $if_block);
			$if_block = str_replace('<?=', '&lt;?=', $if_block);
			$if_block = str_replace('<?', '&lt;?', $if_block);
			$if_block = str_replace('?>', '?&gt;', $if_block);
			$if_block = str_replace('{ @if(', '<?php if(', $if_block);
			$if_block = str_replace(') }', '): ?>', $if_block);
			$if_block = str_replace('{ @elseif(', '<?php elseif(', $if_block);
			$if_block = str_replace('{ @else }', '<?php else: ?>', $if_block);
			$if_block = str_replace('{ @endif }', '<?php endif; ?>', $if_block);

			ob_start();
			eval("?>$if_block");
			$rep = ob_get_contents();
			ob_end_clean();

			$this->t->buffer = str_replace($if_block_original, $rep, $this->t->buffer);

		}


		// render constant
		foreach($this->t->contants as $var)
		{
			if(!defined($var))
			{
				$this->_trigger_error("php constant `{$var}` is not defined", E_USER_NOTICE, true, false);
				$val = '';
			}
			else
			{
				$val = constant($var);
			}

			$this->t->buffer = str_replace("{ const($var) }", $val, $this->t->buffer);
		}


		// render globals
		foreach($this->t->globals as $var)
		{
			$val = '';

			// object ?
			$v = explode('->', $var);
			if(count($v) == 2)
			{
				if(!isset($GLOBALS[$v[0]]->$v[1]))
					$this->_trigger_error("`\${$var}` is not defined", E_USER_NOTICE, true, false);
				else
					$val = $GLOBALS[$v[0]]->$v[1];
			}
			else
			{
				// array
				$v = explode('[', $var);
				if(count($v) == 2)
				{
					$v[0] = trim(str_replace(']', '', $v[0]));
					$v[1] = trim(str_replace(array(']','"', "'"), '', $v[1]));

					if(!isset($GLOBALS[$v[0]][$v[1]]))
						$this->_trigger_error("`\${$var}` is not defined", E_USER_NOTICE, true, false);
					else
						$val = $GLOBALS[$v[0]][$v[1]];
				}
				else
				{
					if(!isset($GLOBALS[$var]))
						$this->_trigger_error("`\${$var}` is not defined", E_USER_NOTICE, true, false);
					else
						$val = $GLOBALS[$var];
				}
			}

			$this->t->buffer = str_replace("{ globals($var) }", $val, $this->t->buffer);
		}

		// render server variable
		foreach($this->t->server as $var)
		{
			if(!isset($_SERVER[$var]))
			{
				$this->_trigger_error("`\$_SERVER[{$var}]` is not defined", E_USER_NOTICE, true, false);
				$val = '';
			}
			else
			{
				$val = $_SERVER[$var];
			}

			$this->t->buffer = str_replace("{ server($var) }", $val, $this->t->buffer);

		}

		// render get
		foreach($this->t->get as $var)
		{
			if(!isset($_GET[$var]))
			{
				$this->_trigger_error("`\$_GET[{$var}]` is not defined", E_USER_NOTICE, true, false);
				$val = '';
			}
			else
			{
				$val = $_GET[$var];
				$val = strip_tags($val);
				$val = htmlspecialchars($val);
			}

			$this->t->buffer = str_replace("{ get($var) }", $val, $this->t->buffer);
		}

		// render post
		foreach($this->t->post as $var)
		{
			if(!isset($_POST[$var]))
			{
				$this->_trigger_error("`\$_POST[{$var}]` is not defined", E_USER_NOTICE, true, false);
				$val = '';
			}
			else
			{
				$val = $_POST[$var];
			}

			$this->t->buffer = str_replace("{ post($var) }", $val, $this->t->buffer);
		}

		// render cookie
		foreach($this->t->cookie as $var)
		{
			if(!isset($_COOKIE[$var]))
			{
				$this->_trigger_error("`\$_COOKIE[{$var}]` is not defined", E_USER_NOTICE, true, false);
				$val = '';
			}
			else
			{
				$val = $_COOKIE[$var];
				$val = strip_tags($val);
			}

			$this->t->buffer = str_replace("{ cookie($var) }", $val, $this->t->buffer);

		}

		// render session
		foreach($this->t->session as $var)
		{
			if(!isset($_SESSION[$var]))
			{
				$this->_trigger_error("`\$_SESSION[{$var}]` is not defined", E_USER_NOTICE, true, false);
				$val = '';
			}
			else
			{
				$val = $_SESSION[$var];
			}

			$this->t->buffer = str_replace("{ session($var) }", $val, $this->t->buffer);
		}

		// render bloc
		foreach($this->t->blocs_root as $bloc_root)
		{
			// erase bloc orginal
			$this->t->blocs[$bloc_root]->parsed = str_replace($this->t->blocs[$bloc_root]->original, '', $this->t->blocs[$bloc_root]->parsed);
			$this->t->buffer = str_replace("<bloc::$bloc_root>{$this->t->blocs[$bloc_root]->original}</bloc::$bloc_root>", $this->t->blocs[$bloc_root]->parsed, $this->t->buffer);
		}

		// render root items
		if(count($this->t->cmd['items-vars']) > 0)
		{
			$this->t->buffer = str_replace($this->t->cmd['items-vars'], $this->t->cmd['items-values'], $this->t->buffer);
		}

		// clean orphan blocs
		$replaced = array();
		foreach($this->t->blocs_found as $b)
		{
			$replaced[] = "<bloc::$b>";
			$replaced[] = "</bloc::$b>";
		}

		$this->t->buffer = str_replace($replaced, '', $this->t->buffer);

		return $this->t->buffer;
	}

	/**
	 * Return ajax bloc in content
	 *
	 * @param $bloc
	 * @param $content
	 *
	 * @return string
	 */
	public function getAjaxBloc($bloc, $content)
	{
		// replace children content to parents
		$pattern = "/<!-- ajax::$bloc -->(.*)?<!-- \/ajax::$bloc -->/msU";
		$match = preg_match_all($pattern, $content, $tab);

		$content = '';
		if(count($tab) > 1)
		{
			$content = trim($tab[1][0]);
		}

		return $content;
	}

}

