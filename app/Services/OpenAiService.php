<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI as FacadesOpenAI;

class OpenAiService
{

    private ?string $apiKey = null;
    private ?string $apiUrl = null;
    private bool $streamed = false;
    private ?array $data = [];
    private ?object $response = null;

    public function __construct(array $data = [], string $apiKey = null, string $apiUrl = null)
    {
        $this->data = $data;
        $this->apiKey($apiKey);
        $this->apiUrl($apiUrl);
    }

    public static function init(?array $data = [])
    {
        return new self($data);
    }

    // creator methods
    public function completions(?array $data = [])
    {
        if ($data) {
            $this->$data = $data;
        }
        return $this;
    }

    // modifiers

    public function apiKey(?string $key)
    {
        $this->apiKey = $key ?? env('OPENAI_API_KEY');
        return $this;
    }

    public function apiUrl(?string $url)
    {
        $this->apiUrl = $url ?? 'https://api.openai.com/v1/completions';
        return $this;
    }

    public function data(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function model(?string $model)
    {
        $this->data['model'] = $model;
        return $this;
    }

    public function prompt(?string $prompt)
    {
        $this->data['prompt'] = $prompt;
        return $this;
    }

    public function n(?string $n)
    {
        $this->data['n'] = $n;
        return $this;
    }

    public function maxToken(int $maxToken = 100)
    {
        $this->data['maxToken'] = $maxToken;
        return $this;
    }

    public function temperature(float $temperature = 0.7)
    {
        $this->data['temperature'] = $temperature;
        return $this;
    }

    public function getResponse(): OpenAiService
    {
        $this->response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->post($this->apiUrl, $this->data);
       
        return $this;
    }

    // aliases
    public function get()
    {
        return $this->getResponse();
    }



    // utility methods
    public function dd(): never
    {
        dd($this);
    }

    public function streamed($stream = true): OpenAiService
    {
        $this->streamed = $stream;
        return $this;
    }

    public function toCollection(): Collection
    {
        return $this->response->collect('choices');
    }

    public function toString(): string
    {
        return $this->response->collect('choices')->value('text') ?? "";
    }

    public function toArray(): array
    {
        return preg_split("/(\s+)/", $this->toString(), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    public function toJson()
    {
        return $this->response->json();
    }

    public function __toString()
    {
        return $this->toString();
    }
}
