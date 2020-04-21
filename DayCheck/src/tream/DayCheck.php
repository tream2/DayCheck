<?php

namespace tream;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use onebone\economyapi\EconomyAPI;
class DayCheck extends PluginBase implements Listener{
    public function onEnable() {
    	@mkdir($this->getDataFolder());
        $this->data = new Config ( $this->getDataFolder () . "DayCheck.yml", Config::YAML,[
        	"출석체크" => [ ]
        ]);
        $this->db = $this->data->getAll ();
		$this->join = new Config($this->getDataFolder() . "join.yml", Config::YAML);
		$this->jo = $this->join->getAll();
        $this->day = (int) date("d");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
	public function DayCheckUI($info){
		 $DayCheckUI = [ 
            "type" => "form",
            "title" => "§l출석체크",
            "content" => "".$info."\n\n\n\n", 
            "buttons" => [
                [
                    "text" => "§l§b▶ §f돌아가기§r§f",
                ]
            ]
        ];
        return json_encode ($DayCheckUI);
   }
    public function 출석체크UI(DataPacketReceiveEvent $event) {
		$pack = $event->getPacket ();
    	$player = $event->getPlayer();
		$pname = $player->getName();
		if ($pack instanceof ModalFormResponsePacket) {
			if($pack->formId == 777){
				$name = json_decode ( $pack->formData, true );
				if($name[0]){
					return true;
				}
			}
		}
	}
    public function onJoin(PlayerJoinEvent $event){
       $player = $event->getPlayer();
       $name = $player->getName();
       if(!isset($this->jo [$name])){
        if(!isset($this->jo [strtolower($name)])){
            $this->jo [strtolower($name)] = 0;
            $this->onSave();
            return true;
        }
       }
    }
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if ($command->getName() == "출석") {
			$name = $sender->getName();
			if(!isset($this->db ["출석체크"] [$this->day] [strtolower($name)])){
				if($this->day !== (int) date("d")){
					$this->db ["출석체크"] [$this->day] [strtolower($name)] = true;
				    $this->jo [strtolower($name)] += 1;
				    $this->onSave();
				    $count = count($this->db ["출석체크"] [$this->day]);
				    EconomyAPI::getInstance()->addmoney($sender, 3000);
				    $this->sendUI($sender, 777, $this->DayCheckUI("§l출석체크\n오늘의 출석체크를 성공적으로 하셨습니다!\n보상 :: 3000원\n\n지금까지 출석체크 :: ".$this->jo [strtolower($name)]."번    \n오늘 출석체크 인원 :: ".$count."명"));
				    return true;
			    }
			    return true;
		    }
			$count = count($this->db ["출석체크"] [$this->day]);
			$this->sendUI($sender, 777, $this->DayCheckUI("§l출석체크\n이미 오늘 출석체크를 하셨습니다.\n\n지금까지 출석체크 :: ".$this->jo [strtolower($name)]."번\n오늘 출석체크 인원 :: ".$count."명"));
			return true;
        }
	}
    public function sendUI(Player $player, $code, $data) {
		$pk = new ModalFormRequestPacket();
		$pk->formId = $code;
		$pk->formData = $data;
		$player->dataPacket($pk);
	}
    public function onSave (){
      $this->data->setAll($this->db);
      $this->data->save();
      $this->join->setAll($this->jo);
      $this->join->save();
    }
}