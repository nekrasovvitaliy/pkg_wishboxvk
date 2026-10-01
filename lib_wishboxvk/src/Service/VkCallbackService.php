<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Service;

use WishboxVkLibrary\Exception\VkCallbackException;

defined('_JEXEC') or die;

/**
 * Validates VK Callback API payloads and confirmation requests.
 *
 * @since 1.0.0
 */
final class VkCallbackService
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(
		private readonly int $groupId,
		private readonly string $secret,
		private readonly string $confirmationCode
	) {
	}

	/**
	 * Validate the group and secret carried by a VK Callback API payload.
	 *
	 * @throws VkCallbackException When callback configuration or credentials are invalid.
	 *
	 * @since 1.0.0
	 */
	public function validate(object $payload): void
	{
		if ($this->groupId < 1 || (int) ($payload->group_id ?? 0) !== $this->groupId)
		{
			throw new VkCallbackException('VK Callback API group ID does not match.');
		}

		if ($this->secret === '' || !hash_equals($this->secret, (string) ($payload->secret ?? '')))
		{
			throw new VkCallbackException('VK Callback API secret does not match.');
		}
	}

	/**
	 * Return the configured confirmation response for a confirmation payload.
	 *
	 * @throws VkCallbackException When the confirmation code is not configured.
	 *
	 * @since 1.0.0
	 */
	public function getConfirmationResponse(object $payload): ?string
	{
		if ((string) ($payload->type ?? '') !== 'confirmation')
		{
			return null;
		}

		if ($this->confirmationCode === '')
		{
			throw new VkCallbackException('VK Callback API confirmation code is not configured.');
		}

		return $this->confirmationCode;
	}
}
