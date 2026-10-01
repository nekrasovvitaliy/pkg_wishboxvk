<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Dto;

use InvalidArgumentException;

defined('_JEXEC') or die;

/**
 * Describes one VK Market item update.
 *
 * @since 1.0.0
 */
final class VkMarketItemUpdate
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(
		public readonly int $itemId,
		public readonly float $price,
		public readonly float $oldPrice = 0.0,
		public readonly bool $deleted = false
	) {
		if ($this->itemId < 1)
		{
			throw new InvalidArgumentException('VK Market item ID must be greater than zero.');
		}

		if ($this->price < 0 || $this->oldPrice < 0)
		{
			throw new InvalidArgumentException('VK Market prices must not be negative.');
		}
	}
}
