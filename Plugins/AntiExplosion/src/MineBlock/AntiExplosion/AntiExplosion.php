<?php
namespace MineBlock\AntiExplosion;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\ExplosionPrimeEvent;

class AntiExplosion extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onExplosionPrime(ExplosionPrimeEvent $event){
		$event->setCancelled();
	}
}