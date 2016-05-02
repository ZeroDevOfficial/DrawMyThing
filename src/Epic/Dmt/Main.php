<?php

namespace Epic\Dmt;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\block\Block;

class Main extends PluginBase implements Listener {
	public $mode = 0;
	public $arenaname = "";
	
	public $preX = 0;
	public $preY = 0;
	public $preZ = 0;
	
	public $walllength1 = 0;
	public $walllength2 = 0;
	public $wallheight1 = 0;
	public $wallheight2 = 0;
	public $rest = 0;
	
	public function onEnable() {
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "/arenas");
		@mkdir($this->getDataFolder() . "/words");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
		$this->getLogger()->info("Done!");
    }
	
	public function onChat(PlayerChatEvent $event)
	{
		$player = $event->getPlayer();
		$levelname = $levelname = $player->getLevel()->getFolderName();
		if(file_exists($this->getDataFolder() . "/arenas/" . $levelname . ".yml"))
		{
			$this->arena = new Config($this->getDataFolder() . "/arenas/" . $levelname . ".yml", Config::YAML);
			if($this->arena->get("ingame")==true)
			{
				if($player->getAllowFlight()==false)
				{
					$message = $event->getMessage();
					$word = $this->arena->get("word");
					if(strtolower($message)==strtolower($word))
					{
						for($i=1;$i<=8;$i++)
						{
							if($this->arena->get($i . "Name") == $player->getName())
							{
								if($this->arena->get($i . "HasGuessed") == false)
								{
								$points = $this->arena->get($i . "Points");
								if($this->arena->get("guessed")==0)
								{
									$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] You're the first! You got 3 points!");
									$this->arena->set($i . "Points", $this->arena->get($i . "Points")+3);
								}
								else
								{
									$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Correct! You got 1 point!");
									$this->arena->set($i . "Points", $this->arena->get($i . "Points")+1);
								}
								$allplayers = $this->getServer()->getOnlinePlayers();
								$aop = 0;
								foreach($allplayers as $inp)
								{
								if($inp->getLevel()->getFolderName()==$levelname)
								{
									$inp->sendMessage(TextFormat::GRAY . "[§cDmt§7]" TextFormat::BOLD . $player->getName() . " has guessed the word!");
									$aop++;
								}
								}
								$this->arena->set("guessed", $this->arena->get("guessed")+1);
								if($this->arena->get("guessed")==$aop)
								{
									$this->arena->set("buildtime",5);
								}
								$this->arena->set($i . "HasGuessed", true);
								$event->setCancelled(true);
								break;
								}
								else
								{
									$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Please don't try to tell someone the word!");
									$event->setCancelled(true);
								}
							}
						}
					}
				}
				else
				{
					$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] You are not allowed to chat while you're drawing!");
					$event->setCancelled(true);
				}
			}
			$this->arena->save();
		}
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
        switch($cmd->getName()){
            case "dmt":
				if($args[0]=="spawn")
				{
					$spawn = $this->getServer()->getLevelByName()->getSafeSpawn();
					$this->getServer()->getLevelByName()->loadChunk($spawn->getX(), $spawn->getZ());
					$sender->teleport($spawn,0,0);
				}
			
				if($args[0]=="teleport" && $sender->isOp() && $this->mode == 11)
				{
					if($this->getServer()->getLevelByName($args[1]) instanceof Level)
					{
					$spawn = $this->getServer()->getLevelByName($args[1])->getSafeSpawn();
					$this->getServer()->getLevelByName($args[1])->loadChunk($spawn->getX(), $spawn->getZ());
					$sender->teleport($spawn,0,0);
					}
					else
					{
						$sender->sendMessage(TextFormat::RED . "This is not a valid world name");
					}
				}
			
                if($args[0]=="addarena" && $sender->isOp() && $this->mode == 0) {
					$this->mode = 1;
					$sender->sendMessage(TextFormat::GRAY . "[§cDmt§7] Use the command " . TextFormat::LIGHT_PURPLE . "/dmt setarena [worldname]" . TextFormat::GOLD . " to enter the arena that you want to make available for the game");
				}
				
				if($args[0]=="setarena" && $this->mode==1 && $sender->isOp()) {
					if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
					//if($this->getServer()->getLevelByName($args[1]) instanceof Level)
					{
						$this->getServer()->loadLevel($args[1]);
						$spawn = $this->getServer()->getLevelByName($args[1])->getSafeSpawn();
						$this->getServer()->getLevelByName($args[1])->loadChunk($spawn->getX(), $spawn->getZ());
						$sender->teleport($spawn,0,0);
						$sender->sendMessage(TextFormat::GRAY . "[§cDmt§7] The level " . TextFormat::LIGHT_PURPLE . $args[1] . TextFormat::GOLD . " has been choosen as an arena");
						$sender->sendMessage(TextFormat::GRAY . "[§cDmt§7] Now you have to choose the wall. Just tap it from one corner to the opposite one");
						$sender->sendMessage(TextFormat::GRAY . "[§cDmt§7] WARNING! DON'T TOUCH A WRONG WOOL BLOCK! ALSO IT MUST BE A WOOL BLOCK");
						$this->arenaname = $args[1];
						$this->mode = 2;
					}
					else
					{
						$sender->sendMessage(TextFormat::GRAY . "[§cDmt§7] This is not a valid world name");
					}
				}
            return true;
        }
    }
	
	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		
		if($player->isOp() && $this->wallheight1==0 && $this->mode==2 && $event->getBlock()->getId()==35)
		{
			if($this->preY==0)
			{
				$this->preX = $event->getBlock()->getX();
				$this->preY = $event->getBlock()->getY();
				$this->preZ = $event->getBlock()->getZ();
				$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] First position: " . $this->preX . " " . $this->preY . " " . $this->preZ);
			}
			else
			{
				if($this->preX == $event->getBlock()->getX())
				{
					if($this->preZ <= $event->getBlock()->getZ())
					{
						$this->walllength1=$this->preZ;
						$this->walllength2=$event->getBlock()->getZ();
						$this->rest = $event->getBlock()->getX();
					}
					else
					{
						$this->walllength1=$event->getBlock()->getZ();
						$this->walllength2=$this->preZ;
						$this->rest = $event->getBlock()->getX();
					}
					if($this->preY <= $event->getBlock()->getY())
					{
						$this->wallheight1=$this->preY;
						$this->wallheight2=$event->getBlock()->getY();
					}
					else
					{
						$this->wallheight1=$event->getBlock()->getY();
						$this->wallheight2=$this->preY;
					}
					$this->createArena();
					$this->resetValues();
					$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Second position: " . $event->getBlock()->getX() . " " . $event->getBlock()->getY() . " " . $event->getBlock()->getZ());
					$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Now tap on 8 different spawn positions");
					$this->mode=3;
				}
				else if($this->preZ == $event->getBlock()->getZ())
				{
					if($this->preX <= $event->getBlock()->getX())
					{
						$this->walllength1=$this->preX;
						$this->walllength2=$event->getBlock()->getX();
						$this->rest = $event->getBlock()->getZ();
					}
					else
					{
						$this->walllength1=$event->getBlock()->getX();
						$this->walllength2=$this->preX;
						$this->rest = $event->getBlock()->getZ();
					}
					if($this->preY <= $event->getBlock()->getY())
					{
						$this->wallheight1=$this->preY;
						$this->wallheight2=$event->getBlock()->getY();
					}
					else
					{
						$this->wallheight1=$event->getBlock()->getY();
						$this->wallheight2=$this->preY;
					}
					$this->createArena();
					$this->resetValues();
					$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Second position: " . $event->getBlock()->getX() . " " . $event->getBlock()->getY() . " " . $event->getBlock()->getZ());
					$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Now tap on 8 different spawn positions");
					$this->mode=3;
				}
				else
				{
					$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] The wall must have a width of 1 in one direction");
				}
			}
		}
		else if($player->isOp() && $this->mode>=3 && $this->mode<=10)
		{
			$this->addSpawnToArena($event->getBlock()->getX(),$event->getBlock()->getY()+2,$event->getBlock()->getZ());
			$this->mode = $this->mode+1;
			if($this->mode<11)
			{
			$player->sendMessage(TextFormat::GOLD . (11 - $this->mode) . " more to go");
			}
			else
			{
			$this->arena->remove("spawntoadd");
			$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Not you have to tap on a sign, to allow the players to join! Use /dmt teleport [world] to teleport to another world!");
			$this->mode=11;
			}
		}
		else if($player->isOp() && $this->mode==11 && $event->getPlayer()->getLevel()->getTile($event->getBlock()) instanceOf Sign)
		{
			$tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
			$tile->setText(TextFormat::BLUE . "Draw my Thing",$this->arenaname,"",TextFormat::WHITE . TextFormat::BOLD . "0 / 8");
			$player->sendMessage(TextFormat::GREEN . "The arena '" . $this->arenaname . "' has been successfully added!");
			$this->mode=0;
			$this->arenaname="";
		}
		else if($event->getPlayer()->getLevel()->getTile($event->getBlock()) instanceOf Sign)
		{
			$tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
			$text = $tile->getText();
			if($text[0]=="Draw my Thing" || $text[0]== TextFormat::BLUE . "Draw my Thing")
			{
				$this->arena = new Config($this->getDataFolder() . "/arenas/" . $text[1] . ".yml", Config::YAML);
				if($this->arena->get("ingame")==false)
				{
					if($text[3] != TextFormat::WHITE . TextFormat::BOLD . "8 / 8")
					{
						$player->getInventory()->clearAll();
						$playersin = 0;
						$allplayers = $this->getServer()->getOnlinePlayers();
						foreach($allplayers as $inp)
						{
							if($inp->getLevel()->getFolderName()==$text[1])
							{
								$playersin++;
							}
						}
						$playersin++;
						$this->getServer()->loadLevel($text[1]);
						$pos = new Vector3($this->arena->get($playersin . "X"),$this->arena->get($playersin . "Y"),$this->arena->get($playersin . "Z"));
						$level = $this->getServer()->getLevelByName($text[1]);
						$player->teleport(new Position($this->arena->get($playersin . "X"),$this->arena->get($playersin . "Y"),$this->arena->get($playersin . "Z"),$level));
						$this->arena->set($playersin . "Points", 0);
						$this->arena->set($playersin . "HasGuessed", false);
						$this->arena->set($playersin . "Name", $player->getName());
						$this->arena->save();
					}
					else
					{
						$player->sendTip(TextFormat::GRAY . "[§cDmt§7] This game is full");
					}
				}
				else
				{
					$player->sendTip(TextFormat::GRAY . "[§cDmt§7] This game is running");
				}
			}
		}
		else if($event->getBlock()->getId()==35)
		{
			$levelname = $player->getLevel()->getFolderName();
			if(file_exists($this->getDataFolder() . "/arenas/" . $levelname . ".yml"))
			{
			$this->arena = new Config($this->getDataFolder() . "/arenas/" . $player->getLevel()->getFolderName() . ".yml", Config::YAML);
			if($this->arena->get("ingame")==true&&$player->getAllowFlight()==true)
			{
				if($player->getInventory()->getItemInHand()!=Item::get(325)&&$player->getInventory()->getItemInHand()!=Item::get(318))
				{
				$block=$event->getBlock();
				$r1 = $this->arena->get("length1");
				$r2 = $this->arena->get("length2");
				$h1 = $this->arena->get("height1");
				$h2 = $this->arena->get("height2");
				$wall = false;
				if((($block->getX()>=$r1 && $block->getX()<=$r2 && $block->getZ()==$this->arena->get("rest")) || ($block->getZ()>=$r1 && $block->getZ()<=$r2 && $block->getX()==$this->arena->get("rest")))&& $block->getY()>= $h1 && $block->getY()<= $h2)
				{
					$wall = true;
				}
				
				if($wall == false)
				{
				$damage = $this->getColorDamage($event->getBlock()->getDamage());
				$player->getInventory()->setItem(1, Item::get(351, $damage, 1));
				$player->getInventory()->setHotbarSlotIndex(3, 1);
				$player->getInventory()->setHotbarSlotIndex(4, 1);
				$player->getInventory()->setHotbarSlotIndex(5, 1);
				$player->getInventory()->setHotbarSlotIndex(6, 1);
				$player->getInventory()->setHotbarSlotIndex(7, 1);
				$player->getInventory()->setHotbarSlotIndex(8, 1);
				}
				else 
				{
					$damage = $this->getColorDamage($player->getInventory()->getItem(1)->getDamage());
					$this->getServer()->getLevelByName($levelname)->setBlock(new Vector3($block->getX(),$block->getY(),$block->getZ()),Block::get(35, $damage));
				}
				}
				else if($player->getInventory()->getItemInHand()==Item::get(318))
				{
					$this->reconstruct($levelname, 0);
				}
				else if($player->getInventory()->getItemInHand()==Item::get(325))
				{
					$this->reconstruct($levelname,15 - $player->getInventory()->getItem(1)->getDamage());
				}
			}
			}
		}
	}
	
	public function resetValues() {
	$this->preX = 0;
	$this->preY = 0;
	$this->preZ = 0;
	
	$this->walllength1 = 0;
	$this->walllength2 = 0;
	$this->wallheight1 = 0;
	$this->wallheight2 = 0;
	}
	
	public function createArena() {
		$this->arena = new Config($this->getDataFolder() . "/arenas/" . $this->arenaname . ".yml", Config::YAML);
		$this->arena->set("length1", $this->walllength1);
		$this->arena->set("length2", $this->walllength2);
		$this->arena->set("height1", $this->wallheight1);
		$this->arena->set("height2", $this->wallheight2);
		$this->arena->set("rest", $this->rest);
		$this->arena->set("spawntoadd", 1);
		$this->arena->set("ingame",false);
		$this->arena->set("time",120);
		$this->arena->set("buildtime",0);
		$this->arena->set("current",0);
		$this->arena->set("word","");
		$this->arena->set("guessed",0);
		$this->arena->save();
	}
	
	public function addSpawnToArena($x,$y,$z) {
		$this->arena = new Config($this->getDataFolder() . "/arenas/" . $this->arenaname . ".yml", Config::YAML);
		$pos = (string)$this->arena->get("spawntoadd");
		$this->arena->set($pos . "X", $x);
		$this->arena->set($pos . "Y", $y);
		$this->arena->set($pos . "Z", $z);
		$this->arena->set($pos . "Points", 0);
		$this->arena->set($pos . "Name", 'unknown');
		$this->arena->set($pos . "HasGuessed", false);
		$this->arena->set("spawntoadd", $this->arena->get("spawntoadd") + 1);
		$this->arena->save();
	}
	
	public function getColorDamage($d) {
		return 15-$d;
	}
	
	public function reconstruct($levelname, $damage)
	{
		$level = $this->getServer()->getLevelByName($levelname);
		$this->arena = new Config($this->getDataFolder() . "/arenas/" . $levelname . ".yml", Config::YAML);
		$a1 = $this->arena->get("length1");
		$a2 = $this->arena->get("length2");
		$b1 = $this->arena->get("height1");
		$b2 = $this->arena->get("height2");
		$c = $this->arena->get("rest");
		
		if($level->getBlock(new Vector3($a1,$b1,$c))->getId()==35&&$level->getBlock(new Vector3($a2,$b2,$c))->getId()==35)
		{
		for($i=$a1;$i<=$a2;$i++)
		{
			for($j=$b1;$j<=$b2;$j++)
			{
				$level->setBlock(new Vector3($i,$j,$c),Block::get(35, $damage));
			}
		}
		}
		else
		{
		for($i=$a1;$i<=$a2;$i++)
		{
			for($j=$b1;$j<=$b2;$j++)
			{
				$level->setBlock(new Vector3($c,$j,$i),Block::get(35, $damage));
			}
		}
		}
	}
}

class GameSender extends PluginTask {
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$dir = $this->plugin->getDataFolder() . "arenas/";
		$arenas = array_slice(scandir($dir),2);
		foreach($arenas as $a)
		{
			$arenaname = pathinfo($a,PATHINFO_FILENAME);
			$this->arena = new Config($this->plugin->getDataFolder() . "/arenas/" . $arenaname . ".yml", Config::YAML);
			$playersinarena = 0;
			foreach($allplayers as $player)
			{
				if($player->getLevel()->getFolderName()==$arenaname)
				{
					$playersinarena += 1;
				}
			}
			if($this->arena->get("ingame")==false)
			{
			if($playersinarena==1)
			{
				foreach($allplayers as $player)
				{
					if($player->getLevel()->getFolderName()==$arenaname)
					{
						$player->sendTip(TextFormat::GRAY . "[§cDmt§7] Not enough players yet");
					}
				}
				$this->arena->set("time",120);
			}
			else if($playersinarena>=2)
			{
				$time = $this->arena->get("time");
				if($time>=1&&$this->arena->get("ingame")==false)
				{
					foreach($allplayers as $player)
					{
						if($player->getLevel()->getFolderName()==$arenaname)
						{
							$player->sendTip(TextFormat::GRAY . "[§cDmt§7] Starting in - ". $time . " seconds");
						}
					}
					$time -= 1;
					$this->arena->set("time", $time);
				}
				else if($time==0 &&$this->arena->get("ingame")==false)
				{
					$this->arena->set("ingame",true);
					$this->arena->set("time",120);
					$this->arena->set("buildtime",0);
					$this->arena->set("current",0);
					$this->arena->set("guessed",0);
					foreach($allplayers as $player)
					{
						if($player->getLevel()->getFolderName()==$arenaname)
						{
							$player->setGamemode(0);
							$player->getInventory()->clearAll();
							$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Good luck " . $player->getName() . "!");
						}
					}
					for($i = 1; $i <= 8; $i++)
							{
								$this->arena->set($i . "Points", 0);
								$this->arena->set($i . "HasGuessed", false);
							}
				}
			}
			else if($playersinarena==0)
			{
				if($this->arena->get("time")!=120)
				{
					$this->arena->set("time",120);
					$this->arena->set("buildtime",0);
					$this->arena->set("guessed",0);
					for($i = 1; $i <= 8; $i++)
					{
						$this->arena->set($i . "Name", 'unknown');
						$this->arena->set($i . "Points", 0);
						$this->arena->set($i . "HasGuessed", false);
					}
				}
			}
			}
			else
			{
				if($playersinarena==0)
				{
					$this->arena->set("ingame",false);
					$this->arena->set("time",120);
					$this->arena->set("buildtime",0);
					$this->arena->set("guessed",0);
					for($i = 1; $i <= 8; $i++)
					{
								$this->arena->set($i . "Name", 'unknown');
								$this->arena->set($i . "Points", 0);
								$this->arena->set($i . "HasGuessed", false);
					}
				}
				else if($playersinarena==1)
				{
					$this->arena->set("ingame",false);
					$this->arena->set("time",120);
					$this->arena->set("buildtime",0);
					$this->arena->set("guessed",0);
					for($i = 1; $i <= 8; $i++)
					{
						$this->arena->set($i . "Name", 'unknown');
						$this->arena->set($i . "Points", 0);
						$this->arena->set($i . "HasGuessed", false);
					}
					foreach($allplayers as $player)
						{
							if($player->getLevel()->getFolderName()==$arenaname)
							{
								
									$player->setGamemode(0);
									$player->getInventory()->clearAll();
									$player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "=======================");
									$player->sendMessage(TextFormat::GREEN . "|");
									$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] You are the winner!");
									$player->sendMessage(TextFormat::GREEN . "|");
									$player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "=======================");
									$spawn = $this->plugin->getServer()->getLevelByName()->getSafeSpawn();
									$this->plugin->getServer()->getLevelByName()->loadChunk($spawn->getX(), $spawn->getZ());
									$player->teleport($spawn,0,0);
							}
						}
				}
				else
				{
					if($this->arena->get("buildtime")==0)
					{
						$rn=$this->arena->get("current");
						$this->arena->set("current",$rn+1);
						$this->reconstruct($arenaname);
						$this->arena->set("guessed",0);
						for($i = 1; $i <= 8; $i++)
						{
						$this->arena->set($i . "HasGuessed", false);
						}
						
						if($this->arena->get("current")<=10)
						{
						$this->arena->set("buildtime",240);
						$playersingame = array();
						foreach($allplayers as $player)
						{
							if($player->getLevel()->getFolderName()==$arenaname)
							{
								array_push($playersingame,$player);
							}
						}
						$randombuildervalue = rand(0, count($playersingame)-1);
						$builder = $playersingame[$randombuildervalue];
						foreach($allplayers as $player)
						{
							if($player->getLevel()->getFolderName()==$arenaname)
							{
								$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Round " . $this->arena->get("current")+1);
								if($player!=$builder)
								{
								$player->sendMessage(TextFormat::GRAY . "[§cDmt§7] Drawer: " . TextFormat::RED . $builder->getName());
								}
								if($player->getAllowFlight()==true)
								{
									$player->setAllowFlight(false);
									for($i=1;$i<=8;$i++)
									{
										if($this->arena->get($i . "Name")==$player->getName())
										{
											$level = $this->plugin->getServer()->getLevelByName($arenaname);
											$player->teleport(new Position($this->arena->get($i . "X"),$this->arena->get($i . "Y"),$this->arena->get($i . "Z"),$level));
										}
									}
								}
								$player->setGamemode(1);
								$player->setGamemode(0);
								$player->getInventory()->clearAll();
							}
						}
						$builder->setAllowFlight(true);
						$builder->getInventory()->clearAll();
						$builder->getInventory()->setItem(0, Item::get(267, 0, 1));
						$builder->getInventory()->setHotbarSlotIndex(0, 0);
						$builder->getInventory()->setItem(2, Item::get(318, 0, 1));
						$builder->getInventory()->setHotbarSlotIndex(1, 2);
						$builder->getInventory()->setItem(3, Item::get(325, 0, 1));
						$builder->getInventory()->setHotbarSlotIndex(2, 3);
						$builder->getInventory()->setItem(1, Item::get(351, 0, 1));
						$builder->getInventory()->setHotbarSlotIndex(3, 1);
						$builder->getInventory()->setHotbarSlotIndex(4, 1);
						$builder->getInventory()->setHotbarSlotIndex(5, 1);
						$builder->getInventory()->setHotbarSlotIndex(6, 1);
						$builder->getInventory()->setHotbarSlotIndex(7, 1);
						$builder->getInventory()->setHotbarSlotIndex(8, 1);
						$content = explode(",",file_get_contents($this->plugin->getDataFolder() . "/words/words.txt"));
						$length = count($content);
						$rn = rand(0,$length-1);
						$this->arena->set("word",$content[$rn]);
						$builder->sendMessage(TextFormat::GRAY . "[§cDmt§7] Your turn! The word is " . TextFormat::LIGHT_PURPLE . $this->arena->get("word"));
						$builder->sendMessage(TextFormat::GRAY . "[§cDmt§7] You can fly now!");
						}
						else
						{
							$points = array();
							$pointsname = array();
							for($i = 1; $i <= 8; $i++)
							{
								array_push($points,$this->arena->get($i . "Points"));
								array_push($pointsname,$this->arena->get($i . "Name"));
							}
							$first = $pointsname[$this->max_key($points)];
							
							foreach($allplayers as $player)
							{
								if($player->getLevel()->getFolderName()==$arenaname)
								{
									$player->setGamemode(0);
									$player->getInventory()->clearAll();
									$player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "=======================");
									$player->sendMessage(TextFormat::GREEN . "|");
									$player->sendMessageTextFormat::GRAY . "[§cDmt§7] The winner is " . TextFormat::WHITE . $first . "!");
									$player->sendMessage(TextFormat::GREEN . "|");
									$player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "=======================");
									$spawn = $this->plugin->getServer()->getLevelByName()->getSafeSpawn();
									$this->plugin->getServer()->getLevelByName()->loadChunk($spawn->getX(), $spawn->getZ());
									$player->teleport($spawn,0,0);
									$this->arena->set("buildtime",0);
									$this->arena->set("time",120);
									$this->arena->set("ingame",false);
									$this->arena->set("guessed",0);
								}
							}
							for($i = 1; $i <= 8; $i++)
							{
								$this->arena->set($i . "Name", 'unknown');
								$this->arena->set($i . "Points", 0);
								$this->arena->set($i . "HasGuessed", false);
							}
						}
						$this->arena->save();
					}
					else
					{
						foreach($allplayers as $player)
						{
							if($player->getLevel()->getFolderName()==$arenaname)
							{
								if($player->getAllowFlight()==false)
								{
									$word=$this->arena->get("word");
									$newword=str_repeat('_', strlen($word));
									$player->sendPopup(TextFormat::BOLD . $newword);
								}
							}
						}
						$bt = $this->arena->get("buildtime");
						if($bt==30||$bt==15||$bt==10||$bt<=5)
						{
						foreach($allplayers as $player)
						{
							if($player->getLevel()->getFolderName()==$arenaname)
							{
								$player->sendMessage(TextFormat::GOLD . $bt . " second(s) remaining!");
							}
						}
						}
						else if($bt==180||$bt==120||$bt==60)
						{
						foreach($allplayers as $player)
						{
							if($player->getLevel()->getFolderName()==$arenaname)
							{
								$player->sendMessage(TextFormat::GOLD . ($bt/60) . " minute(s) remaining!");
							}
						}
						}
						$this->arena->set("buildtime",$this->arena->get("buildtime")-1);
						$this->arena->save();
					}
				}
			}
			$this->arena->save();
		}
	}
	
	public function max_key($array)
	{
    $max = max($array);
    foreach ($array as $key => $val)
    {
        if ($val == $max) return $key;
    }
	}
	
	public function reconstruct($levelname)
	{
		$level = $this->plugin->getServer()->getLevelByName($levelname);
		$this->arena = new Config($this->plugin->getDataFolder() . "/arenas/" . $levelname . ".yml", Config::YAML);
		$a1 = $this->arena->get("length1");
		$a2 = $this->arena->get("length2");
		$b1 = $this->arena->get("height1");
		$b2 = $this->arena->get("height2");
		$c = $this->arena->get("rest");
		
		if($level->getBlock(new Vector3($a1,$b1,$c))->getId()==35&&$level->getBlock(new Vector3($a2,$b2,$c))->getId()==35)
		{
		for($i=$a1;$i<=$a2;$i++)
		{
			for($j=$b1;$j<=$b2;$j++)
			{
				$level->setBlock(new Vector3($i,$j,$c),Block::get(35, 0));
			}
		}
		}
		else
		{
		for($i=$a1;$i<=$a2;$i++)
		{
			for($j=$b1;$j<=$b2;$j++)
			{
				$level->setBlock(new Vector3($c,$j,$i),Block::get(35, 0));
			}
		}
		}
	}
}

class RefreshSigns extends PluginTask {
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getLevelByName();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[0]=="Draw my Thing" || $text[0]== TextFormat::BLUE . "Draw my Thing")
				{
					$aop = 0;
					foreach($allplayers as $player)
					{
						$levelname = $player->getLevel()->getFolderName();
						if($levelname==$text[1])
						{
							$aop=$aop+1;
						}
					}
					$ingame = "";
					$this->arena = new Config($this->plugin->getDataFolder() . "/arenas/" . $text[1] . ".yml", Config::YAML);
					if($this->arena->get("ingame")==false&&$aop<8)
					{
						$ingame=TextFormat::GREEN . "WAITING";
					}
					else if($this->arena->get("ingame")==false&&$aop==8)
					{
						$ingame=TextFormat::YELLOW . "FULL";
					}
					else if($this->arena->get("ingame")==true)
					{
						$ingame=TextFormat::RED . "INGAME";
					}
					$t->setText($text[0],$text[1],$ingame,TextFormat::WHITE . TextFormat::BOLD . $aop . "/ 8");
					
				}
			}
		}
	}
}
