<?php

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);

class Balsamiq2html {
	
	private $asset_controls = null;
	private $dirs = [
		'source' => 'source',
		'images' => 'images',
		'dest' => 'dest'
	];
	private $styles = [
		'body' => [
			'margin: 0'
		],
		'a' => [
			'background: red',
			'opacity: 0.2',
			'filter: alpha(opacity = 20)'
		]
	];
	
	public function __construct($dirs = null, $styles = null) {
		
		if($dirs != null) $this->dirs = $dirs;
		if($styles != null) $this->styles = $styles;
	}
	
	private function __filter($value) {
		
		$is_utf8 = mb_detect_encoding($value, 'UTF-8', true);
		$value = htmlentities($value, ENT_NOQUOTES, $is_utf8 ? 'UTF-8' : 'ISO-8859-1');
		$value = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $value);
		$value = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $value);
		
		return $value;
	}

	private function __cleanFileName($filename, $ext) {
		
		$filename = str_replace(['%20', ' '], '_', $filename);
		$filename = $this->__filter($filename);
		
		return str_replace('.bmml', '.'.$ext, $filename);
	}

	private function __crossControlList($controls, $x = 0, $y = 0) {
		
		$links = [];
		
		foreach ($controls->children() as $control) {
			
			$coords = [
				'x' => $x + $control['x'],
				'y' => $y + $control['y']
			];
			
			// need to recurse
			if (isset($control->groupChildrenDescriptors)) {
				
				$res = $this->__crossControlList($control->groupChildrenDescriptors, $coords['x'], $coords['y']);
				
				$links = array_merge($links, $res);
			}
			
			if (isset($control->controlProperties->map)) {
				
				$hrefs = isset($control->controlProperties->hrefs) ? explode('%2C',$control->controlProperties->hrefs) : [$control->controlProperties->href];
				// array_pop($hrefs);
				
				$maps = str_replace(['&lt;','&gt;'],['<','>'], $this->__filter(rawurldecode(((string)$control->controlProperties->map))));
				//print_r($maps);
				
				$mapsxml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><map>'. $maps .'</map>');
				//print_r($mapsxml);
				
				foreach ($mapsxml->area as $area) {
					
					$areacoords = explode(',', $area['coords']);
					
					// the decoded string might not be valid utf8
					$href = utf8_encode(rawurldecode($area['href']));
					
					$listhref = explode('&bm;', $href);
					
					echo 'maps = '.$listhref[0].'<br />';
					
					$links[] = [
						'href' => $listhref[0],
						'zOrder' => $control['zOrder'],
						'x' => (string)$areacoords[0],
						'y' => (string)$areacoords[1],
						'w' => (string)$areacoords[2] - $areacoords[0],
						'h' => (string)$areacoords[3] - $areacoords[1]
					];
				}
			}
			// link on standard element
			elseif ($href = (string)$control->controlProperties->href) {
				
				// the decoded string might not be valid utf8
				$href = utf8_encode(rawurldecode($href));
				
				$listhref = explode('&bm;',$href);
				
				echo 'href = '.$listhref[0].'<br />';
				
				$link = [
					'href' => $listhref[0].'.bmml',
					'zOrder' => $control['zOrder'],
					'x' => (string)$coords['x'],
					'y' => (string)$coords['y']
				];
				
				if((string)$control['w'] != '-1'){
					$link['w'] = (string)$control['w'];
				}
				elseif((string)$control['measuredW'] != '0'){
					$link['w'] = (string)$control['measuredW'];
				}
				
				if((string)$control['h'] != '-1'){
					$link['h'] = (string)$control['h'];
				}
				elseif((string)$control['measuredH'] != '0'){
					$link['h'] = (string)$control['measuredH'];
				}
				
				// icons have no specified dimensions
				if (!isset($link['w'])) {
					
					switch($control['controlTypeID']){
						
						case 'com.balsamiq.mockups::Icon':
							
							$icon = explode('%7C', $control->controlProperties->icon);
							
							switch($icon[1]){
								case 'xsmall': $link['w'] = '16'; break;
								case 'small': $link['w'] = '24'; break;
								case 'medium': $link['w'] = '32'; break;
								case 'large': $link['w'] = '48'; break;
								case 'xlarge': $link['w'] = '64'; break;
								case 'xxlarge': $link['w'] = '128'; break;
							}
							
							$link['h'] = $link['w'];
							
							break;
						
						case 'com.balsamiq.mockups::RadioButton':
						
							$link['w'] = '16';
							
							break;
						
						case 'com.balsamiq.mockups::Button':
							
							// sometimes buttons don't have a specific with, it's computed by Balsamiq
							// according to the text inside. We'll set 120px arbitrarily.
							$link['w'] = '120';
							
							break;
						
						default:
							echo 'Type not defined, what about a pull request? ;) => '.$control['controlTypeID'];
					}
					
					$link['h'] = $link['w'];
				}
				
				$links[] = $link;
			}
			// symbols
			elseif ($src = (string)$control->controlProperties->src) {
				
				$src = explode('#', $src);
				
				// this filters image assets to target symbols only
				if (isset($src[1])) {
					
					// load the Template file once
					if ($this->asset_controls === null) {
						$xml = simplexml_load_file($this->dirs['source'].'/'.$src[0]);
						$this->asset_controls = $xml->controls;
					}
					
					foreach($this->asset_controls->children() as $asset_control) {
						
						if (isset($asset_control->controlProperties)) {
							
							if (	isset($asset_control->controlProperties->controlName)
								&&	utf8_encode(rawurldecode($asset_control->controlProperties->controlName)) === rawurldecode($src[1])
							) {
								
								$res = $this->__crossControlList($asset_control->groupChildrenDescriptors, $coords['x'], $coords['y']);
								
								$links = array_merge($links, $res);
								
								break;
							}
						}
					}
					
					// check if there are links by override
					foreach ($control->controlProperties->children() as $tag) {
						
						if ($tag->getName() == 'override' && isset($tag->href)){
							
							// the decoded string might not be valid utf8
							$href = utf8_encode(rawurldecode($tag->href));
							
							$listhref = explode('&bm;', $href);
							
							echo 'href = '.$listhref[0].'<br />';
							
							$links[] = [
								'href' => $listhref[0].'.bmml',
								'zOrder' => $control['zOrder'],
								'x' => (string)($control['x'] + $tag['x']),
								'y' => (string)($control['y'] + $tag['y']),
								'w' => (string)$tag['w'],
								'h' => (string)$tag['h']
							];
						}
					}
				}
			}
		}
		
		return $links;
	}

	private function __createHtml($filename, $links, $title = '', $dimensions) {
		
		$contents = '<html><title>'.(empty($title) ? $filename : $title).'</title><style> body { '.implode(';', $this->styles['body']).' } a { '.implode(';', $this->styles['a']).' }</style><body>';
		$contents .= '<div style="margin: 0 auto; width: ' . $dimensions[0] . '; height: ' . $dimensions[1] . '; position: relative; background: no-repeat url(images/'.$this->__cleanFileName($filename, 'png').'?v='.rand().')">';
		
		foreach ($links as $key => $link) {
			$contents .= '<a style="display: block; width: '.$link['w'].'px; height: '.$link['h'].'px; position: absolute; left: '.$link['x'].'px; top: '.$link['y'].'px" href="'.$this->__cleanFileName($link['href'], 'html').'"></a><br />';
		}
		
		$contents .= '</div></body></html>';
		
		file_put_contents($this->dirs['dest'].'/'.$this->__cleanFileName($filename, 'html'), $contents);
	}

	static function sort_list($a, $b) {
		return $b['zOrder'] < $a['zOrder'];
	}
	
	public function run(){
		
		if (!file_exists($this->dirs['dest'])) {
	
			echo '<b>Creating directory '.$this->dirs['dest'].'...</b><br /><br />';
			
			mkdir($this->dirs['dest']);
		}

		if ($dh = opendir($this->dirs['source'].'/')) {
			
			echo '<b>Processing mockup files in '.$this->dirs['source'].' ...</b><br /><br />';

			while (($file = readdir($dh)) !== false) {
				
				$filepath = $this->dirs['source'].'/'.$file;
				
				if (filetype($filepath) == 'file' && substr($file, strlen($file) - 5) == '.bmml') {
					
					echo 'Processing '.$file.' ...<br />';
					
					$xml = simplexml_load_file($filepath);
					
					$links = $this->__crossControlList($xml->controls);
					
					usort($links, array('balsamiq2html','sort_list'));
					
					$browserWindowValues = $xml->xpath('//control[@controlTypeID="com.balsamiq.mockups::BrowserWindow"]/controlProperties/text');
					
					if (count($browserWindowValues)) {
						
						$browserWindowValue = explode('<br />', rawurldecode($browserWindowValues[0]));
						
						$title = $browserWindowValue[0];
					}
					else {
						$title = str_replace('.bmml', '', $file);
					}
					
					echo 'title = '.$file.'<br /><br />';
					
					$imagepath = $this->dirs['images'].'/'.str_replace('.bmml', '.png', $file);
					
					$dimensions = getimagesize($imagepath);
					
					$this->__createHtml($file, $links, $title, $dimensions);
				}
			}
			
			closedir($dh);
		}
		else die ('Cannot open '.$this->dirs['source']);

		if ($dh = opendir($this->dirs['images'].'/')) {
			
			echo '<b>Processing mockup images from '.$this->dirs['images'].' ...</b><br /><br />';
			
			if (!file_exists($this->dirs['dest'] .'/images')) {
				
				echo 'Creating directory '.$this->dirs['dest'].'/images...<br /><br />';
				
				mkdir($this->dirs['dest'] .'/images');
			}
			while (($file = readdir($dh)) !== false) {
				
				$filepath = $this->dirs['images'] .'/'. $file;
				
				if (filetype($filepath) == 'file' &&  substr($file, strlen($file) - 4) == '.png') {
					
					echo 'Copying '.$file.' ...<br />';
					
					copy($filepath, $this->dirs['dest'] .'/images/'.$this->__cleanFileName($file, 'png'));
				}
			}
			closedir($dh);
		}
		else die ('Cannot open '.$this->dirs['images']);

		echo '<br />End.';
	}
}
