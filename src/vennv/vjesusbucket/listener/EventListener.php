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

namespace vennv\vjesusbucket\listener;

use Throwable;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\VanillaItems;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use vennv\vjesusbucket\data\DataManager;

final class EventListener implements Listener
{

	/**
	 * @throws Throwable
	 */
	public function onPlayerInteract(PlayerInteractEvent $event): void
	{
        DataManager::onJesus($event);
    }

	public function onPlayerItemUse(PlayerItemUseEvent $event): void
	{
		$player = $event->getPlayer();
		$item = $event->getItem();

		if (DataManager::isPremiumItem($item))
		{
			$player->getInventory()->remove(VanillaItems::BUCKET());
			$event->cancel();
		}
	}

}