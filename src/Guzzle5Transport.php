<?php

namespace Vault\Transports;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Ring\Future\FutureInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Vault\Exceptions\TransportException;
use Vault\Helpers\ArrayHelper;

/**
 * Class Guzzle5Transport
 *
 * @package Vault\Transports
 */
class Guzzle5Transport implements Transport
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Guzzle5Transport constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string              $method HTTP method.
     * @param string|UriInterface $uri URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return ResponseInterface
     * @throws TransportException
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->send(new \GuzzleHttp\Psr7\Request($method, $uri, ArrayHelper::getValue($options, 'headers'),
            ArrayHelper::getValue($options, 'body')), $options);
    }

    /**
     * Send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array            $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return ResponseInterface
     * @throws TransportException
     */
    public function send(RequestInterface $request, array $options = [])
    {
        $response = $this->rawSend($request, $options);

        return new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody(),
            $response->getProtocolVersion(), $response->getReasonPhrase());
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Message\ResponseInterface|FutureInterface|null
     * @throws \Vault\Exceptions\TransportException
     */
    protected function rawSend(RequestInterface $request, array $options)
    {
        $oldRequest = new Request($request->getMethod(), $request->getUri(), $request->getHeaders(),
            $request->getBody(), $options);

        try {
            return $this->client->send($oldRequest);
        } catch (TransferException $e) {
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string              $method HTTP method
     * @param string|UriInterface $uri URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return PromiseInterface
     * @throws \Vault\Exceptions\TransportException
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        return $this->sendAsync(new \GuzzleHttp\Psr7\Request($method, $uri, ArrayHelper::getValue($options, 'headers'),
            ArrayHelper::getValue($options, 'body')), $options);
    }

    /**
     * Asynchronously send an HTTP request.
     *
     * @param RequestInterface $request Request to send
     * @param array            $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return PromiseInterface
     * @throws \Vault\Exceptions\TransportException
     */
    public function sendAsync(RequestInterface $request, array $options = [])
    {
        /** @var FutureInterface $response */
        $future = $this->rawSend($request, array_merge($options, ['future' => true]));

        return new Promise([$future, 'wait'], [$future, 'cancel']);
    }

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $this->client->getDefaultOption($option);
    }
}