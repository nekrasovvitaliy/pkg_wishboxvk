<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Exception;

use RuntimeException;

defined('_JEXEC') or die;

/**
 * Represents a failed VK API request or an invalid VK API response.
 *
 * @since 1.0.0
 */
class VkApiException extends RuntimeException
{
}
