<?php
namespace MineBlock\ShortCut;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;

class ShortCut extends PluginBase implements Listener{

	public function onEnable(){
		// $this->getServer()->registerEvent("ShortCut", $this, 5, new MethodEventExecutor("onServerCommand"), $this, false);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->loadYml();
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
		if(!isset($sub[0])) return false;
		$ec = $this->ec;
		$rm = TextFormat::RED . "Usage: /ShortCut ";
		$mm = "[ShortCut] ";
		$ik = $this->isKorean();
		switch(strtolower(array_shift($sub))){
			case "add":
			case "a":
			case "추가":
				if(!isset($sub[0]) || !isset($sub[1])){
					$r = $rm . ($ik ? "추가 <단축명> <명령어>": "Add(A) <Alias> <Command>");
				}else{
					$alias = strtolower(array_shift($sub));
					$command = str_replace([".@","_@","-@"], ["@","@","@"], implode(" ", $sub));
					$ec[$alias] = $command;
					$r = $mm . ($ik ? " 추가됨": " add") . "[$alias] => $command";
					;
				}
			break;
			case "del":
			case "d":
			case "삭제":
			case "제거":
				if(!isset($sub[0])){
					$r = $rm . "Del(D) <Alias>";
				}else{
					$a = strtolower($sub[0]);
					if(!isset($ec[$a])){
						$r = "$mm [$a] " . ($ik ? " 목록에 존재하지 않습니다..\n   $rm 목록 ": " does not exist.\n   $rm List(L)");
					}else{
						$alias = $ec[$a];
						unset($ec[$a]);
						$r = $mm . ($ik ? " 제거됨": " del") . "[$a] =>$a";
					}
				}
			break;
			case "reset":
			case "r":
			case "리셋":
			case "초기화":
				$ec = [];
				$r = $mm . ($ik ? " 리셋됨.": " Reset");
			break;
			case "list":
			case "l":
			case "목록":
			case "리스트":
				$page = 1;
				if(isset($sub[1]) && is_numeric($sub[1])) $page = max(floor($sub[1]), 1);
				$list = array_chunk($ec, 5, true);
				if($page >= ($c = count($list))) $page = $c;
				$r = $mm . ($ik ? "이지커맨드 목록 (페이지": "ShortCut List (Page") . " $page/$c) \n";
				$num = ($page - 1) * 5;
				foreach($list[$page - 1] as $k => $v){
					$num++;
					$r .= "  [$num] $k : [$v]\n";
				}
			break;
			case "mineblock":
			case "db":
			case "데베":
				$cnt = 0;
				foreach($this->getArrayBySite("MineBlock2.p-e.kr") as $cmds){
					$cmd = explode(",", $cmds);
					if(isset($cmd[1]) && isset($ec[strtolower($cmd[0])]) && $ec[strtolower($cmd[0])] == $cmd[1]){
						unset($ec[strtolower($cmd[0])]);
						$cnt++;
						$sender->sendMessage($ik ? " 제거 [$cmd[0]] => $cmd[1]": " Del [$cmd[0]] => $cmd[1]");
					}
				}
				if($cmd > 0){
					$r = true;
					$sender->sendMessage("\n" . $mm . ($ik ? "자동으로 명령어 -> 제거완료 (갯수": "Auto MineBlockPlugin Command -> Del Complete (Count") . " : $cnt) ");
				}
				$cnt = 0;
				foreach($this->getArrayBySite("MineBlock.p-e.kr") as $cmds){
					$cmd = explode(",", $cmds);
					if(isset($cmd[1]) and !isset($ec[strtolower($cmd[0])])){
						$ec[strtolower($cmd[0])] = $cmd[1];
						$cnt++;
						$sender->sendMessage($ik ? " 추가 [$cmd[0]] => $cmd[1]": " Add [$cmd[0]] => $cmd[1]");
					}
				}
				if($cmd > 0) $r = "\n" . $mm . ($ik ? "자동으로 명령어 -> 추가완료 (갯수": "Auto MineBlockPlugin Command -> Add Complete (Count") . " : $cnt) ";
				if(!isset($r)) $r = "\n" . $mm . ($ik ? "최신 상태입니다. 업데이트가 필요없습니다.": "This is the latest of the state. The update does not need.");
			break;
			default:
				return false;
			break;
		}
		if(isset($r)) $sender->sendMessage($r);
		if($this->ec !== $ec){
			$this->ec = $ec;
			$this->saveYml();
		}
		return true;
	}

	public function onServerCommand(ServerCommandEvent $event){
		$event->setCommand($this->alias($event->getCommand()));
		if($m = $this->eazyCommand($event)) $event->setCommand($m);
		else $event->setCancelled();
	}

	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event){
		if(strpos($cmd = $event->getMessage(), "/") !== 0) return;
		$event->setMessage("/" . $this->alias(substr($cmd, 1)));
		if($m = $this->eazyCommand($event)) $event->setMessage("/" . $m);
		else $event->setCancelled();
	}

	public function onPlayerChat(PlayerChatEvent $event){
		if($m = $this->eazyCommand($event)) $event->setMessage($m);
		else $event->setCancelled();
	}

	public function alias($cmd){
		$ec = $this->ec;
		$arr = explode(" ", $cmd);
		while((isset($ec[strtolower($arr[0])]))){
			$arr[0] = $ec[strtolower($arr[0])];
			$cmd = implode(" ", $arr);
			$arr = explode(" ", $cmd);
		}
		return $cmd;
	}

	public function eazyCommand($event){
		if($event->isCancelled()) return false;
		if($event instanceof PlayerCommandPreprocessEvent || $event instanceof PlayerChatEvent){
			$cmd = $event instanceof PlayerChatEvent ? $event->getMessage(): substr($event->getMessage(), 1);
			$sender = $event->getPlayer();
			$ip = true;
		}else{
			$cmd = $event->getCommand();
			$sender = $event->getSender();
			$ip = false;
		}
		if(!$sender->hasPermission("mineblock.eazycommand.use")) return false;
		$arr = explode(" ", $cmd);
		$ecl = [];
		$ps = $this->getServer()->getOnlinePlayers();
		foreach($arr as $k => $v){
			if(strpos($v, "@") === 0){
				switch(substr($v, 1)){
					case "player":
					case "p":
						$arr[$k] = $sender->getName();
					break;
					case "x":
						if($ip) $arr[$k] = $sender->x;
					break;
					case "y":
						if($ip) $arr[$k] = $sender->y;
					break;
					case "z":
						if($ip) $arr[$k] = $sender->z;
					break;
					case "world":
					case "w":
						if($ip) $arr[$k] = $sender->getLevel()->getName();
					break;
					case "all":
					case "a":
						if($sender->isOp() && count($ps) > 0) $ecl[] = $k;
					break;
					case "random":
					case "r":
						$arr[$k] = count($ps) < 1 ? "": $ps[array_rand($ps)]->getName();
					break;
					case "server":
					case "s":
						$arr[$k] = $this->getServer()->getServerName();
					break;
					case "version":
					case "v":
						$arr[$k] = $this->getServer()->getApiVersion();
					break;
					case "mineblock":
					case "d":
						$arr[$k] = ["데베","MineBlock","데베플러그인","MineBlock"][rand(0, 3)];
					break;
				}
			}
		}
		foreach($arr as $k => $v)
			$arr[$k] = str_replace([".@","_@","-@"], ["@","@","@"], $v);
		if(count($ecl) !== 0){
			$event->setCancelled();
			foreach($ps as $p){
				foreach($ecl as $v)
					$arr[$v] = $p->getName();
				$cmd = implode(" ", $arr);
				$ep = false;
				if($event instanceof PlayerCommandPreprocessEvent){
					$ev = new PlayerCommandPreprocessEvent($sender, "/" . $cmd);
					$ep = true;
				}elseif($event instanceof PlayerChatEvent){
					$this->getServer()->getPluginManager()->callEvent($ev = new PlayerChatEvent($sender, $cmd));
					if(!$ev->isCancelled()) $this->getServer()->broadcastMessage(sprintf($ev->getFormat(), $ev->getPlayer()->getDisplayName(), $ev->getMessage()), $ev->getRecipients());
					return false;
				}else{
					$ev = new ServerCommandEvent($sender, $cmd);
				}
				$this->getServer()->getPluginManager()->callEvent($ev);
				if(!$ev->isCancelled()) $this->getServer()->dispatchCommand($sender, $ep ? substr($ev->getMessage(), 1): $ev->getCommand());
			}
			return false;
		}else{
			return implode(" ", $arr);
		}
	}

	public function getArrayBySite($url){
		return explode("]", str_replace(["<!-- 단일 페이지 -->","<div>","</div>","<br>","\r","\n","["," ","_"], ["","","","","","","",""," "], file_get_contents("http://" . $url)));
	}

	public function loadYml(){
		@mkdir($this->getServer()->getDataPath() . "/plugins/! MineBlock/");
		$this->ec = (new Config($this->getServer()->getDataPath() . "/plugins/! MineBlock/" . "ShortCut.yml", Config::YAML))->getAll();
	}

	public function saveYml(){
		ksort($this->ec);
		$ec = new Config($this->getServer()->getDataPath() . "/plugins/! MineBlock/" . "ShortCut.yml", Config::YAML);
		$ec->setAll($this->ec);
		$ec->save();
	}

	public function isKorean(){
		@mkdir($this->getServer()->getDataPath() . "/plugins/! MineBlock/");
		if(!isset($this->ik)) $this->ik = (new Config($this->getServer()->getDataPath() . "/plugins/! MineBlock/" . "! Korean.yml", Config::YAML, ["Korean" => false]))->get("Korean");
		return $this->ik;
	}
}