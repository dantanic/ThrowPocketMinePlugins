<?php

/*
__PocketMine Plugin__
name=PMFDecompiler
version=
author=
apiversion=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
class=PMF_D
*/
define("TAB", "\t");
define("UNKNOWN_STR", "\x01");

class PMF_D implements Plugin{
	private $api, $log, $cnt;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api=$api;
	}
	
	public function init(){
		@mkdir(DATA_PATH."plugins/PMFDecompiler");
		@mkdir(DATA_PATH."plugins/PMFDecompiler/log");
		if(is_file(DATA_PATH."plugins/PMFDecompiler/log/DecompileLog.log")){
			$data = file_get_contents(DATA_PATH."plugins/PMFDecompiler/log/DecompileLog.log");
			$this->log = str_replace("\\n", "\n", $data);
		}else{
			$this->log = "";
		}
		$this->api->console->register("/d", "", array($this, "commandHandler"));
	}
	
	public function __destruct(){}
	
	public function commandHandler($cmd, $param, $issuer, $alias = false){
	$output = "";
	switch($cmd){
		case "/d":
			$class = implode(" ",$param);
			if($class == "") {
				console("[INFO] /decompile <Plugin Name>"); break;
			}
			if(!is_file(DATA_PATH."plugins/$class.pmf")){
				console("[PMFDecompiler] Decompile failure. Plugin \"$class\" doesn't exist");break;
			}
			$pmf = new PMFPlugin(DATA_PATH."plugins/$class.pmf");
			$info = $pmf->getPluginInfo();
			$code = $info["code"];
			$code = $this->alignBlocks($code);
            if($code !== ""){
				file_put_contents(DATA_PATH."plugins/PMFDecompiler/$class.php", "<?php\n\n/*\n__Decompiled with PMFDecompiler__\nname=".$info["name"]."\nversion=".$info["version"]."\nauthor=".$info["author"]."\napiversion=".$info["apiversion"]."\nclass=".$info["class"]."\n*/\n\n$code");
				$success = true; 
			}
			if(isset($success)){
				$output .= ("[PMFDecompiler] Decompile Success.\nFile at ".DATA_PATH."plugins/PMFDecompiler/$class.php");
				break;
			}else{
				$output .= ("[PMFDecompiler] Source of \"$class\" is empty");
				break;
			}
			break;
		}
		return $output;
	}
	
	private function alignBlocks($code){
		$code = str_replace(array("\n", "\t", "\r"), array("\\n", "\\t", "\\r"), $code);
		$lastId = "";
		$isStr = false;
		$isCase = false;
		$ret = "";
		$startOffset = 0;
		$offset = 0;
		$tabs = "";
		while(($offset = $this->checkBlocks(array("{", "}", "\":", "':", ";", "\"", "'", "case", "||", "&&"), $code, $startOffset)) !== false){
			$next = $code{$offset + 1};
			$str = $code{$offset};
			if($code{0} === " "){
				--$offset;
				$code = substr($code, 1);
			}
			switch(true){
				case ($str === "c"):
				if($isStr){
					$startOffset = $offset;
					break;
				}
				$isCase = true;
				break;
				case ($str === "\"" and $next === ":"):
				case ($str === "'" and $next === ":"):
				if($isCase){
					$isCase = false;
					$ret .= "\n".$tabs.substr($code, 0, $offset + 2);
					$code = substr($code, $offset + 2);
					$isStr = false;
					$lastId = "";
					$startOffset = -1;
				}else{
					$isStr = true;
					$lastId = $str;
					$startOffset = $offset;
				}
				break;
				case ($str === "\""):
				case ($str === "'"):
				if($isStr){
					if($str === $lastId){
						$isStr = false;
						$lastId = "";
						$startOffset = $offset;
					}
				}else{
					$isStr = true;
					$lastId = $str;
					$startOffset = $offset;
				}
				break;
				case $str === ";":
				if($isStr){
					$startOffset = $offset;
					break;
				}
				$ret .= "\n".$tabs.substr($code, 0, $offset + 1);
				$code = substr($code, $offset + 1);
				$startOffset = -1;
				break;
				case $str === "{":
				if($isStr){
					$startOffset = $offset;
					break;
				}
				$ret .= "\n".$tabs.substr($code, 0, $offset + 1);
				$code = substr($code, $offset + 1);
				$tabs .= TAB;
				$startOffset = -1;
				break;
				case $str === "}":
				if($isStr){
					$startOffset = $offset;
					break;
				}
				$tabs = substr($tabs, 1);
				$ret .= "\n".$tabs.substr($code, 0, $offset + 1);
				$code = substr($code, $offset + 1);
				$startOffset = -1;
				break;
				case $str === "|":
				if($isStr){
					$startOffset = $offset;
					break;
				}
				$code{$offset} = "o";
				$code{$offset + 1} = "r";
				$startOffset = $offset;
				break;
				case $str === "&":
				if($isStr){
					$startOffset = $offset;
					break;
				}
				$code{$offset} = UNKNOWN_STR;
				$code = str_replace(UNKNOWN_STR."&", "and", $code);
				$startOffset = $offset;
				break;
			}
			++$startOffset;
		}
		$ret .= "\n".$tabs.$code;
		$ret = substr($ret, 1);
		return $ret;
	}
	
	public function checkBlocks($arr, $code, $offset){
		if(!is_array($arr)){
			return false;
		}
		$offsets = array();
		foreach($arr as $regex){
			while(($o = strpos($code, $regex, $offset)) !== false){
				$offsets[] = $o;
				break;
			}
		}
		asort($offsets);
		if(count($offsets) > 0){
			foreach($offsets as $o){
				$offset = $o;
				break;
			}
			return $offset;
		}
		return false;
	}
	
	public function writeLog($data){
		$this->log .= $data."\\n";
	}
}