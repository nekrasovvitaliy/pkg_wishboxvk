<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Service;

defined('_JEXEC') or die;

/**
 * Defines the contract for classes that depend on the VK Callback service.
 *
 * @since 1.0.0
 */
interface VkCallbackServiceAwareInterface
{
	/**
	 * Set the VK Callback service.
	 *
	 * @since 1.0.0
	 */
	public function setVkCallbackService(VkCallbackService $vkCallbackService): void;
}
