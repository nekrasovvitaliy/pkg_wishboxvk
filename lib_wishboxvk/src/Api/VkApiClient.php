<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxVkLibrary\Api;

use InvalidArgumentException;
use Joomla\CMS\Log\Log;
use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use JsonException;
use WishboxVkLibrary\Exception\VkApiException;
use WishboxVkLibrary\Exception\VkCaptchaRequiredException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Sends authenticated requests to VK API methods.
 *
 * @since 1.0.0
 */
final class VkApiClient
{
	private const string DEFAULT_API_URL = 'https://api.vk.com/method/';

	private const string DEFAULT_API_VERSION = '5.199';

	private Http $http;

	private string $apiUrl;

	private string $proxyToken;

	/**
	 * @since 1.0.0
	 */
	public function __construct(
		private readonly string $accessToken,
		private readonly string $apiVersion = self::DEFAULT_API_VERSION,
		?Http $http = null,
		string $apiUrl = self::DEFAULT_API_URL,
		string $proxyToken = ''
	) {
		$apiUrl = trim($apiUrl);

		if (filter_var($apiUrl, FILTER_VALIDATE_URL) === false || parse_url($apiUrl, PHP_URL_SCHEME) !== 'https')
		{
			throw new InvalidArgumentException('VK API URL must be a valid HTTPS URL.');
		}

		$this->apiUrl = rtrim($apiUrl, '/') . '/';
		$this->proxyToken = $proxyToken;
		$this->http = $http ?? new HttpFactory()->getHttp([], ['curl', 'stream']);
	}

	/**
	 * Call a VK API method and return its response value.
	 *
	 * @param   array<string, mixed>  $parameters  VK method parameters without authentication data.
	 *
	 * @throws VkApiException             When the request or response is invalid.
	 * @throws VkCaptchaRequiredException When VK requests captcha validation.
	 *
	 * @since 1.0.0
	 */
	public function call(string $method, array $parameters = [], ?string $apiVersion = null): mixed
	{
		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.]+$/', $method))
		{
			throw new InvalidArgumentException('Invalid VK API method name.');
		}

		if ($this->accessToken === '')
		{
			throw new VkApiException('VK access token must be configured.');
		}

		$parameters['access_token'] = $this->accessToken;
		$parameters['v'] = $apiVersion ?? $this->apiVersion;
		$headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

		if ($this->proxyToken !== '')
		{
			$headers['X-VK-Proxy-Token'] = $this->proxyToken;
		}

		$response = $this->http->post(
			$this->apiUrl . $method,
			http_build_query($parameters),
			$headers
		);
		$statusCode = $response->getStatusCode();

		if ($statusCode < 200 || $statusCode >= 300)
		{
			throw new VkApiException('VK returned HTTP status ' . $statusCode . '.');
		}

		try
		{
			$data = json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $exception)
		{
			throw new VkApiException('VK returned invalid JSON.', 0, $exception);
		}

		if (!is_object($data))
		{
			throw new VkApiException('VK response must be a JSON object.');
		}

		if (isset($data->error))
		{
			if (!is_object($data->error))
			{
				throw new VkApiException('VK returned an invalid error payload.');
			}

			$this->throwApiError($data->error);
		}

		if (!property_exists($data, 'response'))
		{
			throw new VkApiException('VK response does not contain a response value.');
		}

		$executeErrors = $data->execute_errors ?? [];

		if (!is_iterable($executeErrors))
		{
			throw new VkApiException('VK returned an invalid execute_errors value.');
		}

		foreach ($executeErrors as $executeError)
		{
			Log::add(
				'VK API: ' . (is_object($executeError) ? ($executeError->error_msg ?? 'unknown execute error') : 'unknown execute error'),
				Log::ERROR,
				'wishboxvk'
			);
		}

		return $data->response;
	}

	/**
	 * Execute VKScript and return the execute response value.
	 *
	 * @param   array<string, mixed>  $parameters  Additional execute parameters, including captcha values.
	 *
	 * @throws VkApiException             When the request or response is invalid.
	 * @throws VkCaptchaRequiredException When VK requests captcha validation.
	 *
	 * @since 1.0.0
	 */
	public function execute(string $code, array $parameters = [], ?string $apiVersion = null): mixed
	{
		$parameters['code'] = $code;

		return $this->call('execute', $parameters, $apiVersion);
	}

	/**
	 * @throws VkApiException
	 * @throws VkCaptchaRequiredException
	 *
	 * @since 1.0.0
	 */
	private function throwApiError(object $error): never
	{
		$errorCode = (int) ($error->error_code ?? 0);

		if ($errorCode === 14)
		{
			throw new VkCaptchaRequiredException(
				(string) ($error->captcha_img ?? ''),
				(string) ($error->captcha_sid ?? '')
			);
		}

		throw new VkApiException(
			(string) ($error->error_msg ?? 'VK API request failed.'),
			$errorCode
		);
	}
}
