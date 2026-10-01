<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Service;

use stdClass;
use WishboxVkLibrary\Api\VkApiClient;
use WishboxVkLibrary\Exception\VkApiException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Loads group orders and their items from VK Market.
 *
 * @since 1.0.0
 */
final readonly class VkOrderService
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(
		private VkApiClient $apiClient,
		private int         $groupId
	) {
	}

	/**
	 * Load one page of VK group orders.
	 *
	 * @throws VkApiException When the group ID or VK response is invalid.
	 *
	 * @since 1.0.0
	 */
	public function getGroupOrders(int $limit = 10, int $offset = 0): stdClass
	{
		if ($this->groupId < 1)
		{
			throw new VkApiException('VK group ID must be configured.');
		}

		$response = $this->apiClient->call(
			'market.getGroupOrders',
			[
				'group_id' => $this->groupId,
				'offset'   => max(0, $offset),
				'count'    => min(100, max(1, $limit)),
			],
			'5.122'
		);

		if (!$response instanceof stdClass)
		{
			throw new VkApiException('VK group orders response must be an object.');
		}

		return $response;
	}

	/**
	 * Load the items belonging to one VK Market order.
	 *
	 * @return list<stdClass>
	 *
	 * @throws VkApiException When the order identifiers or VK response are invalid.
	 *
	 * @since 1.0.0
	 */
	public function getOrderItems(int $userId, int $orderId, int $limit = 1000, int $offset = 0): array
	{
		if ($userId < 1 || $orderId < 1)
		{
			throw new VkApiException('VK user ID and order ID must be greater than zero.');
		}

		$response = $this->apiClient->call(
			'market.getOrderItems',
			[
				'user_id'  => $userId,
				'order_id' => $orderId,
				'offset'   => max(0, $offset),
				'count'    => min(1000, max(1, $limit)),
			]
		);

		if (!$response instanceof stdClass || !is_array($response->items ?? null))
		{
			throw new VkApiException('VK order items response must contain an items array.');
		}

		return array_values(
			array_filter(
				$response->items,
				static fn (mixed $item): bool => $item instanceof stdClass
			)
		);
	}
}
