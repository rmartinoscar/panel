<?php

namespace App\Jobs;

use App\Models\WebhookConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<mixed>  $data
     */
    public function __construct(
        private WebhookConfiguration $webhookConfiguration,
        private string $eventName,
        private array $data
    ) {}

    public function handle(): void
    {
        $data = $this->data[0];
        if ($this->webhookConfiguration->type === 'discord') {
            $data = json_decode($data, true);

            $tmp = preg_replace_callback(
                '/{{(.*?)}}/',
                fn ($matches) => array_get($data, $matches[1], $matches[1]),
                $this->webhookConfiguration->payload
            );

            $data = json_decode($tmp, true);
        }

        try {
            Http::withHeader('X-Webhook-Event', $this->eventName)
                ->post($this->webhookConfiguration->endpoint, $data)
                ->throw();
            $successful = now();
        } catch (\Exception $exception) {
            report($exception);
            $successful = null;
        }

        $this->webhookConfiguration->webhooks()->create([
            'payload' => $this->data,
            'successful_at' => $successful,
            'event' => $this->eventName,
            'endpoint' => $this->webhookConfiguration->endpoint,
        ]);
    }
}
