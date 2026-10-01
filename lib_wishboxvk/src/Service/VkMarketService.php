<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Service;

use JsonException;
use WishboxVkLibrary\Api\VkApiClient;
use WishboxVkLibrary\Dto\VkMarketItemUpdate;
use WishboxVkLibrary\Exception\VkApiException;
use WishboxVkLibrary\Exception\VkCaptchaRequiredException;

defined('_JEXEC') or die;

/**
 * Updates product data through the VK Market API.
 *
 * @since 1.0.0
 */
final class VkMarketService
{
	private const API_VERSION = '5.131';

	private const MAX_BATCH_SIZE = 25;

	/**
	 * @since 1.0.0
	 */
	public function __construct(
		private readonly VkApiClient $apiClient,
		private readonly int $groupId
	) {
	}

	/**
	 * Update prices and publication state for one VK execute batch.
	 *
	 * @param   list<VkMarketItemUpdate>  $items       VK Market item updates.
	 * @param   string                    $captchaSid  Captcha session identifier.
	 * @param   string                    $captchaKey  Captcha answer.
	 *
	 * @return list<mixed> VK responses in the same order as the supplied products.
	 *
	 * @throws VkApiException             When VK returns an invalid response or an API error.
	 * @throws VkCaptchaRequiredException When VK requests captcha validation.
	 *
	 * @since 1.0.0
	 */
	public function editProducts(array $items, string $captchaSid = '', string $captchaKey = ''): array
	{
		if ($items === [] || count($items) > self::MAX_BATCH_SIZE)
		{
			throw new VkApiException('A VK execute batch must contain between 1 and 25 products.');
		}

		if ($this->groupId < 1)
		{
			throw new VkApiException('VK group ID must be configured.');
		}

		$code = ['var results = [];'];

		foreach ($items as $item)
		{
			if (!$item instanceof VkMarketItemUpdate)
			{
				throw new VkApiException('Every VK Market update must be a VkMarketItemUpdate object.');
			}

			$request = [
				'owner_id' => '-' . $this->groupId,
				'item_id'  => $item->itemId,
				'price'    => (string) $item->price,
				'deleted'  => $item->deleted,
			];

			if ($item->oldPrice > 0.01)
			{
				$request['old_price'] = (string) $item->oldPrice;
			}

			try
			{
				$code[] = 'results.push(API.market.edit('
					. json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
					. '));';
			}
			catch (JsonException $exception)
			{
				throw new VkApiException('Unable to encode a VK Market request.', 0, $exception);
			}
		}

		$code[] = 'return results;';
		$parameters = [];

		if ($captchaSid !== '' && $captchaKey !== '')
		{
			$parameters['captcha_sid'] = $captchaSid;
			$parameters['captcha_key'] = $captchaKey;
		}

		$response = $this->apiClient->execute(implode('', $code), $parameters, self::API_VERSION);

		if (!is_array($response))
		{
			throw new VkApiException('VK execute response must be an array.');
		}

		return $response;
	}
}
