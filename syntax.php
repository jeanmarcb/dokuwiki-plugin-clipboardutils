<?php
/**
 * DokuWiki Plugin Clipboard Utils
 *
 * @license	GPL 2 (http://www.gnu.org/licenses/gpl.html)*
 * @author Jean-Marc Boulade <jean-marc@boulade.com>
 * @version 2021-09-08
 */

// must be run within Dokuwiki
if ( !defined( 'DOKU_INC' ) ) die();
//dbglog(sprintf("LOADING '%s'\n",__FILE__));

if ( !defined( 'DOKU_PLUGIN' ) ) define( 'DOKU_PLUGIN', DOKU_INC.'lib/plugins/' );
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_clipboardutils extends DokuWiki_Syntax_Plugin {
	var $c;

  function getType() { return 'substition';}
  //function getPType() { return 'block';}
  function getSort() { return 999; }
 
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
  /**
   * Ensure class initialisation.
   */
  public function assertInit() {
		if (!is_array($this->c)) {
			$this->c =array(
				'url_icons' => 'lib/plugins/clipboardutils/images/'
			);
		}
	}
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
  /**
   * Connect lookup pattern to lexer.
   *
   * @param string  $mode Parser mode
   */
  public function connectTo($mode) {
		$this->assertInit();
		//dbglog(sprintf("%s('%s')\n",__METHOD__,$mode));
		$this->Lexer->addSpecialPattern('<(?:clipb?)\b.*?>.*?</clipb>', $mode, 'plugin_clipboardutils');
  }
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
  /**
   * Handle matches of the clippy syntax
   *
   * @param string  $match   The match of the syntax
   * @param int     $state   The state of the handler
   * @param int     $pos     The position in the document
   * @param Doku_Handler $handler The handler
   * @return array Data for the renderer
   */
  public function handle($match, $state, $pos, Doku_Handler $handler) {
		//dbglog(sprintf("%s('%s') %s\n",__METHOD__,$state,print_r($match,TRUE)));
		$data =array('state' => $state, 'match' => $match, 'matchv' => htmlentities($match), 'pos' => $pos, 'att' => array());
		if (preg_match('/<(?:clipb?)\b(.*?)>(.*?)<\/clipb>/s',$match,$m)) {
			$data['m'] =$m;
			$data['value'] =$m[2];
			if (($str =trim($m[1])) != '') {
				$data['att_string'] =$str;
				foreach(preg_split('/[;\s]/',$str) as $el) {
					if (preg_match('/(\w+)\s*[=:]\s*(.*)$/',trim($el),$m2)) {
						$key =strtolower($m2[1]);
						switch($key) {
							case 't' : $key ='type'; break;
							case 'f' : $key ='format'; break;
							case 'i' : $key ='icon'; break;
						}
						$data['att'][$key] =$m2[2];
					}
				}
			}
		} else $data['error'] =TRUE;
		if (!isset($data['att']['type'])) {
			if (!empty($data['att']['format'])) $data['att']['type'] ='format';
			else $data['att']['type'] =$this->getConf('defaultype');
		}
		return $data;
  }
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
	/**
	 * For debug purpose
	 */
  public function debugArrayString($data, $level =0) {
		$pad =str_pad('',$level*2,' ');
		foreach($data as $key => $value) {
			$str .=$pad . $key;
			if (is_array($value)) {
				$sep =str_pad('',80,'-');
				$str .='   : IS an ARRAY :' . PHP_EOL . $sep . PHP_EOL;
				$str .=$this->debugArrayString($value,$level+1) . $sep . PHP_EOL;;
			} else {
				$str .='   : "' . htmlentities($value) . '"' . PHP_EOL;
			}
		}
		return $str;
	}
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
	/**
	 * For debug purpose
	 */
  public function debugDataString($data) {
		$str ='';
		if (!is_array($data) || count($data) < 1) {
			$str .='--- EMPTY VALUE ---' . PHP_EOL;
		} else {
			$str .=$this->debugArrayString($data);
		}
		return $str;
	}
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
	/**
	 * Get icon url from $data
	 *
	 * @param array $data The original data from the handler() function
	 * @return string with url of icon.
	 */
  public function GetIconFromData($data) {
		$this->assertInit();
		if (!empty($data['att']['icon'])) $icon =$data['att']['icon'];
		else $icon =$this->getConf('icon');
		if ($icon == '') $icon ='copy1.png';
		if (strpos($icon,'.') === FALSE) $icon .='.png';
		return $this->c['url_icons'] . $icon;
	}
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
	/**
	 * Render a 'type' of output from $data
	 *
	 * @param array $data The original data from the handler() function
	 * @param array $type Override the $data['type'].
	 * @return string to add $renderer->doc.
	 */
  public function GetStringFromData(Doku_Renderer $renderer, $data, $type ='-') {
		$str ='';
		if ($type == '-') $type =(empty($data['att']['type'])) ? '' : $data['att']['type'];
		$type =strtolower(trim($type));
		switch($type) {
			// Les types de base :
			case 'c' :
			case 'clic' : // Le texte que l'on clique pour le copier dans le presse papier.
				$str .='<span class="clipu-c vclipu" data-clipboard-text="' . $data['value'] . '">' . $renderer->_xmlEntities($data['value']) .'</span>';
				break;
			case 'i' :
			case 'icon' : // Une icone cliquable
				$str .='<span class="bclipu"><img class="clipu-c bclipu" data-clipboard-text="' . $data['value'] . '" src="' . $this->GetIconFromData($data) . '"></span>';
				break;
			case 't' :
			case 'text' : // le texte brut sans autre fonctionalités
				$str .=$renderer->_xmlEntities($data['value']);
				break;
			case 'f' :
			// LE type multiple :
			case 'format' : // Un ensemble de formats
				$f_str =(empty($data['att']['format'])) ? 'i' : $data['att']['format'];
				if (strpos($f_str,',') !== FALSE) $formats =explode(',',$f_str);
				else $formats =str_split($f_str);
				$cpt_format =0;
				foreach($formats as $format) {
					$format =strtolower($format);
					switch($format) {
						case '' : // on évite un certain nombre de type non compatible.
						case '-' :
						case 'f' :
						case 'f' :
						case 'format' :
							break;
						default :
							$value =$this->GetStringFromData($renderer,$data,$format);
							if (!empty($value)) {
								if ($cpt_format > 0) $str .=' ';
								$str .=$value;
								$cpt_format++;
							}
							break;
					}
				}
				break;
			// Les types composés :
			case 'ti' :
			case 'texticon' : // Le text avec l'icone cliquable.
				$str .=$this->GetStringFromData($renderer,$data,'t') . ' ' . $this->GetStringFromData($renderer,$data,'i');
				break;			
			case 'it' :
			case 'icontext' : // L'icone cliquable d'abord et le text derrière.
				$str .=$this->GetStringFromData($renderer,$data,'i') . ' ' . $this->GetStringFromData($renderer,$data,'t');
				break;			
			case 'ci' :
			case 'clicicon' : // Le textcliquable ET l'icone cliquable.
				$str .=$this->GetStringFromData($renderer,$data,'c') . ' ' . $this->GetStringFromData($renderer,$data,'i');
				break;			
			case 'ic' :
			case 'iconclic' : // L'icone cliquable d'abord et le text derrière.
				$str .=$this->GetStringFromData($renderer,$data,'i') . ' ' . $this->GetStringFromData($renderer,$data,'c');
				break;			
			// Les types pour le debogage :
			case 'debug' :
				$str .='<pre>' . print_r($conf,TRUE) . '</pre>';
				break;
			case 'data' :
				$str .= '<pre>'. $this->debugDataString($data) .'</pre>';
				break;
		}
		return $str;
	}
	// -+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
	/**
	 * Render xhtml output or metadata
	 *
	 * @param string $mode Renderer mode
	 * @param Doku_Renderer $renderer The renderer
	 * @param array $data The data from the handler() function
	 * @return bool If rendering was successful.
	 */
  public function render($mode, Doku_Renderer $renderer, $data) {
		global $conf;

    if ( $mode != 'xhtml' ) return false;
		if (!is_array($data) || !isset($data['state'])) return false;
		$renderer->doc .=$this->GetStringFromData($renderer,$data,'-');
    return true;
  }
}

