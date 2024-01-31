<?php

namespace AmpGuzzle;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

use function Amp\delay;

class HttpClientHandler
{

    /**
     * @param HttpClient $httpClient Amphp Http Client
     */
    public function __construct(private HttpClient $httpClient)
    {
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        // print_r($options);

        if (isset($options['delay'])) {
            delay($options['delay'] * 1000);
        }

        // create request
        $aRequest = new Request($request->getUri(), $request->getMethod());

        if (isset($options['connect_timeout'])) {
            $aRequest->setTcpConnectTimeout($options['connect_timeout']);
        }

        if (isset($options['read_timeout'])) {
            $aRequest->setTransferTimeout($options['read_timeout']);
        }

        if (isset($options['timeout'])) {
            $aRequest->setInactivityTimeout($options['timeout']);
        }

        if (isset($options['version'])) {
            $aRequest->setProtocolVersions($options['version']);
        }

        $headers = $request->getHeaders();
        if ($headers) {
            $aRequest->setHeaders($headers);
        }

        if (isset($options['auth'])) {
            [$username, $password] = $options['auth'];
            $digest = $options['auth'][2] ?? 'basic';

            $authorization = match ($digest) {
                'basic' => 'Basic '.base64_encode("$username:$password"),
                'rearer' => 'Bearer '.$options['auth'][0]
            };

            $aRequest->setHeader('Authorization', $authorization);
        }

        $cookieJar = null;

        if (isset($options['cookies']) && $options['cookies'] instanceof CookieJarInterface) {
            /** @var CookieJarInterface $cookieJar */
            $cookieJar = $options['cookies'];
            $request = $cookieJar->withCookieHeader($request);
        }

        if (($body = $request->getBody()) !== null) {
            $httpContent = new AmpStreamContent($body);
            $aRequest->setBody($httpContent);
        }

        //debug
        if (isset($options['debug']) && $options['debug']) {
            //ToDo
        }

        if (isset($options['expect'])) {
            if ($options['expect']) {
                $aRequest->setHeader('Expect', '100-Continue');
            } else {
                $aRequest->setHeader('Expect', '');
            }
        }

        // Unsupported options:
        // - allow_redirects - 受 \Amp\Http\Client\Interceptor\FollowRedirects 控制
        // - auth - 暂不支持 digest，支持 basic, bearer
        // - cert, ssl_key
        // - verify - $tlsContext = (new ClientTlsContext(''))->withoutPeerVerification()->withSecurityLevel(1); $this->connectContext = $this->connectContext->withTlsContext($tlsContext);
        // - decode_content - 受 \Amp\Http\Client\Interceptor\DecompressResponse 控制
        // - force_ip_resolve - 受客户端池创建时的参数控制 $connectContext = (new ConnectContext())->withDnsTypeRestriction(Record::A);
        // - on_stats
        // - progress
        // - sink
        // - stream

        $aResponse = $this->httpClient->request($aRequest);
        $content = new GuzzlePayloadStream($aResponse->getBody(), $aResponse->getHeader('content-length'));

        $response = new Response($aResponse->getStatus(), $aResponse->getHeaders(), $content, $aResponse->getProtocolVersion(), $aResponse->getReason());

        //Should before $aResponse->getBody()->buffer()
        if (isset($options['on_headers'])) {
            if (!\is_callable($options['on_headers'])) {
                throw new \InvalidArgumentException('on_headers must be callable');
            }
            $options['on_headers']($response);
        }

        if ($cookieJar !== null) {
            $cookieJar->extractCookies($request, $response);
        }

        return new FulfilledPromise($response);
    }

}
