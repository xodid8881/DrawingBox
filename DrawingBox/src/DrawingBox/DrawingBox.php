<?php
declare(strict_types=1);

namespace DrawingBox;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

use DrawingBox\Commands\DrawingBoxSettingCommand;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

use pocketmine\entity\Location;
use pocketmine\entity\Human;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Entity;

use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldManager;

use DrawingBox\InventoryLib\InvLibManager;
use DrawingBox\InventoryLib\LibInvType;
use DrawingBox\InventoryLib\InvLibAction;
use DrawingBox\InventoryLib\SimpleInventory;
use DrawingBox\InventoryLib\LibInventory;

use pocketmine\scheduler\Task;
use pocketmine\scheduler\AsyncTask;

use MoneyManager\MoneyManager;

class DrawingBox extends PluginBase {

  protected $config;
  public $db;
  public $get = [];
  private static $instance = null;

  public static function getInstance(): DrawingBox
  {
    return static::$instance;
  }

  public function onLoad():void
  {
    self::$instance = $this;
  }

  public function onEnable():void
  {
    $this->player = new Config ($this->getDataFolder() . "players.yml", Config::YAML);
    $this->pldb = $this->player->getAll();
    $this->box = new Config ($this->getDataFolder() . "boxs.yml", Config::YAML);
    $this->boxdb = $this->box->getAll();
    $this->getServer()->getCommandMap()->register('DrawingBox', new DrawingBoxSettingCommand($this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    InvLibManager::register($this);
  }

  public function tag() : string
  {
    return "§l§b[뽑기상자]§r§7 ";
  }

  public function ShopEntitySpawn($player,$shopName){
    $pos = $player->getPosition();
    $loc = $player->getLocation();
    $loc = new Location($pos->getFloorX() + 0.5, $pos->getFloorY() + 0.05, $pos->getFloorZ() + 0.5,
    $pos->getWorld(), $loc->getYaw(), $loc->getPitch());
    $npc = new Human($loc, $player->getSkin());
    $npc->setNameTag($shopName);
    $npc->setNameTagAlwaysVisible();
    $npc->spawnToAll();
    return true;
  }

  public function BoxSettingGUI($player) {
    $name = $player->getName ();
    $playerPos = $player->getPosition();
    $inv = InvLibManager::create(LibInvType::DOUBLE_CHEST(), new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), "[ 뽑기상자 ] | 상자세팅");
    $boxname = $this->pldb [strtolower($name)] ["BoxName"];
    foreach($this->boxdb [$boxname] as $count => $v){
      if ($count != "count"){
        $item = Item::jsonDeserialize($this->boxdb [$boxname] [$count] ['nbt']);
        $inv->setItem( $count , $item );
      }
    }
    $inv->setItem( 45 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 46 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 47 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 48 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 49 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 50 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 51 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 52 , ItemFactory::getInstance()->get(63, 0, 1)->setCustomName(" "));
    $inv->setItem( 53 , ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("설정완료")->setLore([ "이벤트 이용시 뽑기상자 설정완료\n경고 : 이용시 이전 저장정보가 삭제됩니다." ]) );

    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun():void {
        $this->inv->send($this->player);
      }
    }, 10);
  }

  public function BoxSeeSettingGUI($player) {
    $name = $player->getName ();
    $playerPos = $player->getPosition();
    $inv = InvLibManager::create(LibInvType::DOUBLE_CHEST(), new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), "[ 뽑기상자 ] | 상자세팅정보");
    $count = 0;
    $boxname = $this->pldb [strtolower($name)] ["BoxName"];
    foreach($this->boxdb [$boxname] as $count => $v) {
      $item = Item::jsonDeserialize($this->boxdb [$boxname] [$count] ['nbt']);
      $inv->setItem( $count , $item );
      ++$count;
    }
    $inv->setItem( 53 , ItemFactory::getInstance()->get(54, 0, 1)->setCustomName("나가기")->setLore([ "뽑기상자 나가기" ]) );

    $inv->setListener(function(InvLibAction $action):void{
      $action->setCancelled();
    });

    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun():void {
        $this->inv->send($this->player);
      }
    }, 10);
  }

  public function onDisable():void
  {
    $this->save();
  }

  public function save():void
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->box->setAll($this->boxdb);
    $this->box->save();
  }
}
