<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Exception;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Signals that VK requires captcha validation before a request can be repeated.
 *
 * @since 1.0.0
 */
final class VkCaptchaRequiredException extends VkApiException
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(
		private readonly string $captchaImage,
		private readonly string $captchaSid
	) {
		parent::__construct('VK requires captcha validation.', 14);
	}

	/**
	 * @since 1.0.0
	 */
	public function getCaptchaImage(): string
	{
		return $this->captchaImage;
	}

	/**
	 * @since 1.0.0
	 */
	public function getCaptchaSid(): string
	{
		return $this->captchaSid;
	}
}
