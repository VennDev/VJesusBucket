<?php

/**
 * VJesusBucket - PocketMine plugin.
 * Copyright (C) 2023 - 2025 VennDev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vennv\vjesusbucket\data;

use pocketmine\player\Player;
use Throwable;
use pocketmine\block\Air;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\world\sound\BucketFillLavaSound;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use vennv\vapm\Async;
use vennv\vapm\Promise;
use vennv\vjesusbucket\utils\ItemUtil;
use vennv\vjesusbucket\utils\MathUtil;
use vennv\vjesusbucket\utils\TypeBucket;
use vennv\vjesusbucket\VJesusBucket;

final class DataManager
{

    public static function getConfig(): Config
	{
        return VJesusBucket::getInstance()->getConfig();
    }

	public static function isPremiumItem(Item $item): bool
	{
		$namedTag = $item->getNamedTag();

		return $namedTag->getTag("vjesusbucket") !== null &&
			$namedTag->getTag("vtype_bucket") !== null;
	}

	public static function giveVJesusBucket(Player $player, string $type, int $count): bool
	{
		$item = self::getVJesusBucket($type);

		if ($item !== null)
		{
			for ($i = 0; $i < $count; $i++)
			{
				$player->getInventory()->addItem($item);
			}

			return true;
		}

		return false;
	}

	public static function getVJesusBucket(string $type): ?Item
	{
		$item = match ($type) {
			TypeBucket::WATER => ItemUtil::getItem("water_bucket"),
			TypeBucket::LAVA => ItemUtil::getItem("lava_bucket"),
			default => null,
		};

		if ($item !== null)
		{
			$name = self::getConfig()->get($type);
			$item->setCustomName($name);

			$item = $item->setNamedTag($item->getNamedTag()->setString("vjesusbucket", "true"));
			$item = $item->setNamedTag($item->getNamedTag()->setString("vtype_bucket", $type));
		}

		return $item;
	}

	/**
	 * @throws Throwable
	 */
	public static function onJesus(PlayerInteractEvent $event): void
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();

		$world = $player->getWorld();
		$location = clone $player->getLocation();
		$positionBlock = $block->getPosition();
		$inventory = $player->getInventory();

		$positionBlock = $world->getBlock($positionBlock->asVector3()->add(0, 1, 0))->getPosition();

		$itemHand = $inventory->getItemInHand();

		if (self::isPremiumItem($itemHand))
		{
			$typeBucket = $itemHand->getNamedTag()->getString("vtype_bucket");

			$blockJesus = match ($typeBucket)
			{
				TypeBucket::WATER => VanillaBlocks::WATER(),
				TypeBucket::LAVA => VanillaBlocks::LAVA(),
				default => VanillaBlocks::AIR(),
			};

			new Async(function() use (
				$world, $location, $positionBlock, $blockJesus
			): void
			{
				for ($i = 1; $i < self::getConfig()->get("length"); $i++)
				{
					$promise = Async::await(new Promise(function($resolve, $reject) use (
						$world, $location, $positionBlock, $blockJesus, $i
					): void
					{
						$nextVector = MathUtil::getNextBlockByInteract($location, $positionBlock->asVector3(), $i);

						$blockHere = $world->getBlock($nextVector);
						$blockDownHere = $world->getBlock($nextVector->subtract(0, 1, 0));

						if (
							($blockHere instanceof Air || $blockHere instanceof $blockJesus) &&
							!$blockDownHere instanceof Air
						)
						{
							$world->setBlock(
								$nextVector,
								$blockJesus
							);

							$viewers = $world->getViewersForPosition($location->asVector3());
							$world->addSound($location->asVector3(), new BucketFillLavaSound(), $viewers);

							$resolve(true);
						}
						else
						{
							$reject(false);
						}
					}));

					if ($promise === false)
					{
						break;
					}
				}
			});
		}
    }

}