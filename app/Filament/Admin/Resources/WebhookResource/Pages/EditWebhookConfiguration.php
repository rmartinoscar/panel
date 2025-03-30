<?php

namespace App\Filament\Admin\Resources\WebhookResource\Pages;

use App\Filament\Admin\Resources\WebhookResource;
use App\Models\WebhookConfiguration;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWebhookConfiguration extends EditRecord
{
    protected static string $resource = WebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runNow')
                ->label(fn (WebhookConfiguration $webhookConfiguration) => count($webhookConfiguration->events) === 0 ? 'No tasks' : 'Run now')
                ->color(fn (WebhookConfiguration $webhookConfiguration) => count($webhookConfiguration->events) === 0 ? 'warning' : 'primary')
                ->disabled(fn (WebhookConfiguration $webhookConfiguration) => count($webhookConfiguration->events) === 0)
                ->action(function (WebhookConfiguration $webhookConfiguration) {
                    $this->dispatch($webhookConfiguration, collect($webhookConfiguration->events)->first(), ['testing']);
                }),
            DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (!$record instanceof WebhookConfiguration) {
            return $record;
        }

        if ($data['type'] === 'discord') {
            $tmp = collect([
                'messages' => $data['messages'] ?? [],
                'tts' => false,
                'embeds' => $data['embeds'] ?? [],
                'components' => [],
            ])
                ->filter(fn ($key) => !empty($key))->all();
            $data['payload'] = json_encode($tmp);

            unset($data['content'], $data['embeds']);
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
