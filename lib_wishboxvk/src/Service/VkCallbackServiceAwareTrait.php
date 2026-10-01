<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Service;

use UnexpectedValueException;

defined('_JEXEC') or die;

/**
 * Provides VK Callback service injection for service-aware classes.
 *
 * @since 1.0.0
 */
trait VkCallbackServiceAwareTrait
{
	private ?VkCallbackService $vkCallbackService = null;

	/**
	 * Get the configured VK Callback service.
	 *
	 * @throws UnexpectedValueException When the service has not been injected.
	 *
	 * @since 1.0.0
	 */
	protected function getVkCallbackService(): VkCallbackService
	{
		if ($this->vkCallbackService instanceof VkCallbackService)
		{
			return $this->vkCallbackService;
		}

		throw new UnexpectedValueException('VkCallbackService not set in ' . static::class . '.');
	}

	/**
	 * Set the VK Callback service.
	 *
	 * @since 1.0.0
	 */
	public function setVkCallbackService(VkCallbackService $vkCallbackService): void
	{
		$this->vkCallbackService = $vkCallbackService;
	}
}
