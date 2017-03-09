<?php
namespace X;

class X extends \pocketmine\plugin\PluginBase{
	public function onLoad(){
		@mkdir($this->getDataFolder());
		$this->ignoreTokenTable = [T_STRING, T_CONCAT_EQUAL, T_DOUBLE_ARROW, T_BOOLEAN_AND, T_BOOLEAN_OR, T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL, T_INC, T_DEC, T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_DOUBLE_COLON, T_PAAMAYIM_NEKUDOTAYIM, T_OBJECT_OPERATOR, T_DOLLAR_OPEN_CURLY_BRACES, T_AND_EQUAL, T_MOD_EQUAL, T_XOR_EQUAL, T_OR_EQUAL, T_SL, T_SR, T_SL_EQUAL, T_SR_EQUAL];
		$this->wordList = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "_"];
 	}

	public function onCommand(\pocketmine\command\CommandSender $sender, \pocketmine\command\Command $cmd, $label, array $sub){
		if(!isset($sub[1])){
			@mkdir($this->getDataFolder());
			return false;
		}else{
			@mkdir($folder = $this->getDataFolder());
			$sender->sendMessage("Obfuscation Start");
			if(file_exists($path = $folder . $sub[0] . ".php")){
				file_put_contents($folder . $sub[1] . ".php", $this->obfuscation(file_get_contents($path), $folder . $sub[1] . ".php"));
 				$sender->sendMessage("Obfuscation Success");
			}else{
				$sender->sendMessage("Obfuscation Failed...Can not find the file");
			}
			return true;
		}
	}

	public function obfuscation($src){
		$variables = ["\$this" => "\$this"];
		$newSrc = "";
		$lastSign = "";
		$openTag = null;
		$ignore = false;
		$inHeredoc = false;
		if(preg_match_all("/(protected|private|public|\:\:)[ \t\n\f\v]*[\$]([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/", $src, $matches)){
			foreach($matches[2] as $key => $match){
				$src = str_replace($matches[0][$key], str_replace("\$", ":\\\$:", $matches[0][$key]), $src);
			}
		}
		for($i = 0; $i < ($count = count($tokens = token_get_all($src))); $i++){
			if(is_array($token = $tokens[$i])){
				if(in_array($token[0], $this->ignoreTokenTable)){
					$newSrc .= $token[1];
					$ignore = true;
				}else{
					switch($token[0]){
						case T_INLINE_HTML:
						case T_EXTENDS:
						case T_IMPLEMENTS:
							$newSrc .= $token[1];
						break;
						case T_COMMENT:
						case T_DOC_COMMENT:
							$ignore = true;
						break;
						case T_VARIABLE:
							if(!isset($variables[$token[1]])){
								$variables[$token[1]] = "\$" . [$word = $this->wordList[array_rand($this->wordList)], strtoupper($word), "_"][rand(0,2)] . dechex(count($variables) + 10);
							}
							$newSrc .=  $variables[$token[1]];
							$ignore = true;
						break;
						case T_WHITESPACE:
							if(!$ignore && (!is_string($next = @$tokens[$i+1]) || $next == "\$") && !in_array($next, $this->ignoreTokenTable)){
								$newSrc .= " ";
							}
						break;
						case T_OPEN_TAG:
							if(strpos($token[1], " ") || strpos($token[1], "\n") || strpos($token[1], "\t") || strpos($token[1], "\r")){
								$token[1] = rtrim($token[1]);
							}
							$token[1] .= " ";
							$newSrc .= $token[1];
							$openTag = T_OPEN_TAG;
							$ignore = true;
						break;
						case T_OPEN_TAG_WITH_ECHO:
							$newSrc .= $token[1];
							$openTag = T_OPEN_TAG_WITH_ECHO;
							$ignore = true;
						break;
						case T_CLOSE_TAG:
							if($openTag == T_OPEN_TAG_WITH_ECHO){
								$newSrc= rtrim($new, "; ");
							}else{
								$token[1] = " ".$token[1];
							}
							$newSrc .= $token[1];
							$openTag = null;
						break;
						case T_CONSTANT_ENCAPSED_STRING:
						case T_ENCAPSED_AND_WHITESPACE:
							if($token[1][0] == "\""){
								$token[1] = addcslashes($token[1], "\n\t\r");
							}
							$newSrc .= $token[1];
							$ignore = true;
						break;
						case T_START_HEREDOC:
							$newSrc .= "<<<S\n";
							$inHeredoc = true;
						break;
						case T_END_HEREDOC:
							$newSrc .= "S;";
							$ignore = true;
							$inHeredoc = false; // in HEREDOC
							for($j = $i+1; $j < $count; $j++){
								if(is_string($tokens[$j]) && $tokens[$j] == ";"){
									$i = $j;
									break;
								}elseif($tokens[$j][0] == T_CLOSE_TAG){
									break;
								}
							}
						break;
						default:
							$newSrc .= $token[1];
						break;
					}
				}
				$ignore = false;
				$lastSign = "";
			}else{
				if(($token != ";" && $token != ":") || $lastSign != $token){
					$newSrc .= $token;
					$lastSign = $token;
				}
				$ignore = true;
			}
		}
		return str_replace(":\\\$:", "\$", $newSrc);
	}
}