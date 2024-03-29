<?php

namespace CodeDredd\Soap\Client;

use CodeDredd\Soap\SoapFactory;
use OutOfBoundsException;

class ResponseSequence
{
    /**
     * The responses in the sequence.
     *
     * @var array
     */
    protected $responses;

    /**
     * Indicates that invoking this sequence when it is empty should throw an exception.
     *
     * @var bool
     */
    protected $failWhenEmpty = true;

    /**
     * The response that should be returned when the sequence is empty.
     *
     * @var ?\GuzzleHttp\Promise\PromiseInterface
     */
    protected ?\GuzzleHttp\Promise\PromiseInterface $emptyResponse = null;

    /**
     * Create a new response sequence.
     *
     * @param  array  $responses
     * @return void
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * Push a response to the sequence.
     *
     * @param  string|array  $body
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function push($body = '', int $status = 200, array $headers = [])
    {
        return $this->pushResponse(
            SoapFactory::response($body, $status, $headers)
        );
    }

    /**
     * Push a response to the sequence.
     *
     * @param  mixed  $response
     * @return $this
     */
    public function pushResponse($response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Push a response with the given status code to the sequence.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushStatus(int $status, array $headers = [])
    {
        return $this->pushResponse(
            SoapFactory::response('', $status, $headers)
        );
    }

    /**
     * Push response with the contents of a file as the body to the sequence.
     *
     * @param  string  $filePath
     * @param  int  $status
     * @param  array  $headers
     * @return $this
     */
    public function pushFile(string $filePath, int $status = 200, array $headers = [])
    {
        $string = file_get_contents($filePath);

        return $this->pushResponse(
            SoapFactory::response($string, $status, $headers)
        );
    }

    /**
     * Make the sequence return a default response when it is empty.
     *
     * @return $this
     */
    public function dontFailWhenEmpty()
    {
        return $this->whenEmpty(SoapFactory::response());
    }

    /**
     * Make the sequence return a default response when it is empty.
     *
     * @param  \GuzzleHttp\Promise\PromiseInterface|\Closure  $response
     * @return $this
     */
    public function whenEmpty($response)
    {
        $this->failWhenEmpty = false;
        $this->emptyResponse = $response;

        return $this;
    }

    /**
     * Indicate that this sequence has depleted all of its responses.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->responses) === 0;
    }

    /**
     * Get the next response in the sequence.
     *
     * @return mixed
     */
    public function __invoke()
    {
        if ($this->failWhenEmpty && count($this->responses) === 0) {
            throw new OutOfBoundsException('A request was made, but the response sequence is empty.');
        }

        if (! $this->failWhenEmpty && count($this->responses) === 0) {
            return value($this->emptyResponse ?? SoapFactory::response());
        }

        return array_shift($this->responses);
    }
}
