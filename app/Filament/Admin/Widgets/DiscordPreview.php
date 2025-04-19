<?php

namespace App\Filament\Admin\Widgets;

use App\Models\WebhookConfiguration;
use Filament\Widgets\Widget;

class DiscordPreview extends Widget
{
    protected static string $view = 'filament.admin.widgets.discord-preview';

    /** @var array<string, string> */
    protected $listeners = [
        'refresh-widget' => '$refresh',
    ];

    protected static bool $isDiscovered = false; // Without this its shown on every Admin Pages

    protected int|string|array $columnSpan = 1;

    public WebhookConfiguration $record;

    public function getViewData(): array
    {
        $data = $this->record->run(true);

        $payload = $this->record->replaceVars($data, json_encode($this->record->payload));
        $payload = json_decode($payload, true);

        $embeds = data_get($payload, 'embeds', []);

        foreach ($embeds as &$embed) {
            if (data_get($embed, 'has_timestamp')) {
                unset($embed['has_timestamp']);
                $embed['timestamp'] = $this->record->getTime();
            }
        }

        return [
            'link' => fn ($href, $child) => $href ? sprintf('<a href="%s" target="_blank" class="link">%s</a>', $href, $child) : $child,
            'content' => data_get($payload, 'content'),
            'sender' => $this->easterEgg(data_get($payload, 'username')),
            'embeds' => $embeds,
            'getTime' => $this->record->getTime(),
        ];
    }

    /** @return array<string, mixed> */
    private function easterEgg(?string $author): array
    {
        $avatar = data_get($this->record->payload, 'avatar_url');

        // If this is approved, add the other pelican contributors.
        return match ($author) {
            'JoanFo' => [
                'name' => $author,
                'avatar' => 'https://cdn.discordapp.com/avatars/668228483796959272/fa232a470776f48fc9aa53d5a8a6a074.png',
                'decoration' => 'https://cdn.discordapp.com/avatar-decoration-presets/a_af5ee420e5f860ff2cdbb5fa4633f2cf.png?size=96&amp;amp;passthrough=false',
                'human' => true,
            ],
            'Lance' => [
                'name' => $author,
                'avatar' => 'https://cdn.discordapp.com/avatars/108350949411532800/5c0366c62ccb4263734f9decebf4944d.png',
                'decoration' => 'https://cdn.discordapp.com/avatar-decoration-presets/a_b3d5743ff7a2cda95d28fd984f82a5f8.png?size=96&amp;amp;passthrough=false',
                'human' => true,
            ],
            'notCharles' => [
                'name' => $author,
                'avatar' => 'https://cdn.discordapp.com/avatars/168955129830178816/d6de49de0ff5f3f3338c8cad510825cf.png',
                'decoration' => null,
                'human' => true,
            ],
            default => [
                'name' => $author ?? 'Pelican',
                'avatar' => $avatar ?? 'https://cdn.discordapp.com/avatars/1222179499253170307/d4d6873acc8a0d5fb5eaa5aa81572cf3.png',
                'decoration' => null,
                'human' => false,
            ]
        };
    }
}
