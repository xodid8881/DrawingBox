<?php
declare(strict_types=1);

namespace DrawingBox;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\player\Player;
use pocketmine\Server;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\utils\TextFormat;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;


use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;

use pocketmine\network\mcpe\protocol\ContainerClosePacket;

use LifeInventoryLib\InventoryLib\InvLibManager;
use LifeInventoryLib\InventoryLib\LibInvType;
use LifeInventoryLib\InventoryLib\InvLibAction;
use LifeInventoryLib\InventoryLib\SimpleInventory;
use LifeInventoryLib\InventoryLib\LibInventory;

class EventListener implements Listener
{
  
  protected $plugin;
  private $chat;
  
  public function __construct(DrawingBox $plugin)
  {
    $this->plugin = $plugin;
  }
  
  public function OnJoin (PlayerJoinEvent $event): void
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (!isset($this->plugin->pldb [strtolower($name)])){
      $this->plugin->pldb [strtolower($name)] ["BoxName"] = "없음";
      $this->plugin->save ();
    }
  }
  
  public function OnInteract(PlayerInteractEvent $event) {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    $item = $player->getInventory()->getItemInHand();
    foreach($this->plugin->boxdb as $boxname => $v){
      $chacktag = "{$boxname}|뽑기상자";
      if ($item->getCustomName () == null) return;
      if ($item->getCustomName () == $chacktag) {
        $player->sendMessage ( $this->plugin->tag() . "{$boxname}§r 뽑기상자를 오픈했습니다." );
        $event->cancel ();
        $count = $this->plugin->boxdb [$boxname] ["count"];
        $RandomNuber = mt_rand (0, $count-1);
        $nbtitem = Item::jsonDeserialize($this->plugin->boxdb [$boxname] [$RandomNuber] ['nbt']);
        $player->getInventory ()->addItem ( $nbtitem );
        $player->getInventory ()->removeItem ( $item->setCount(1) );
        return true;
      }
    }
  }
  
  public function onTransaction(InventoryTransactionEvent $event)
  {
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        $inv = $action->getInventory();
        if($inv instanceof LibInventory){
          if ($inv->getTitle() == "[ 뽑기상자 ] | 상자세팅정보"){
            $slot = $action->getSlot ();
            $item = $inv->getItem ($slot);
            $id = $item->getId ();
            $damage = $item->getMeta ();
            $itemname = $item->getCustomName ();
            $nbt = $item->jsonSerialize ();
            if ( $itemname == "나가기" ) {
              $event->cancel ();
              $inv->onClose($player);
            }
          }
          if ($inv->getTitle() == "[ 뽑기상자 ] | 상자세팅"){
            $slot = $action->getSlot ();
            $item = $inv->getItem ($slot);
            $id = $item->getId ();
            $damage = $item->getMeta ();
            $itemname = $item->getCustomName ();
            $nbt = $item->jsonSerialize ();
            if ( $id == 63 ) {
              $event->cancel ();
              return true;
            }
            if ( $itemname == "설정완료" ) {
              $event->cancel ();
              $BoxName = $this->plugin->pldb [strtolower($name)] ["BoxName"];
              $i = 0;
              $count = 0;
              while ($i <= 44){
                $item = $inv->getItem($i);
                if ($item->getId() != 0){
                  $this->plugin->boxdb [$BoxName] [$count] ["nbt"] = $item->jsonSerialize();
                  $this->plugin->save ();
                  ++$count;
                } else {
                  if (isset($this->plugin->boxdb [$BoxName] [$i])){
                    unset ($this->plugin->boxdb [$BoxName] [$i]);
                    $this->plugin->save ();
                  }
                }
                $this->plugin->boxdb [$BoxName] ["count"] = $count;
                $this->plugin->save ();
                ++$i;
              }
              $player->sendMessage ($this->plugin->tag() . "아이템 설정이 완료되었습니다.");
              $inv->onClose($player);
              return true;
            }
          }
        }
      }
    }
  }
  
}
