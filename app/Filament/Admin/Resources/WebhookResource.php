<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WebhookResource\Pages;
use App\Filament\Admin\Resources\WebhookResource\Pages\EditWebhookConfiguration;
use App\Livewire\AlertBanner;
use App\Models\WebhookConfiguration;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Set;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportEvents\HandlesEvents;

class WebhookResource extends Resource
{
    use HandlesEvents;

    protected static ?string $model = WebhookConfiguration::class;

    protected static ?WebhookConfiguration $clone = null;

    protected static ?string $navigationIcon = 'tabler-webhook';

    protected static ?string $recordTitleAttribute = 'description';

    protected const TYPES = [
        'standalone' => 'Standalone',
        'discord' => 'Discord',
    ];

    public static function getNavigationLabel(): string
    {
        return trans('admin/webhook.nav_title');
    }

    public static function getModelLabel(): string
    {
        return trans('admin/webhook.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('admin/webhook.model_label_plural');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('admin/dashboard.advanced');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('type')
                    ->icon(fn ($state) => $state === 'standalone' ? 'tabler-world-www' : 'tabler-brand-discord')
                    ->color(fn ($state) => $state === 'standalone' ? null : Color::hex('#5865F2')),
                TextColumn::make('endpoint')
                    ->label(trans('admin/webhook.table.endpoint'))
                    ->wrap()
                    ->formatStateUsing(fn ($state) => str($state)->after('//'))
                    ->limit(60),
                TextColumn::make('description')
                    ->label(trans('admin/webhook.table.description')),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn ($record) => static::canEdit($record)),
                EditAction::make(),
                ReplicateAction::make()
                    ->iconButton()
                    ->tooltip(trans('filament-actions::replicate.single.label'))
                    ->modal(false)
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (WebhookConfiguration $record, WebhookConfiguration $replica) {
                        $replica->description = $record->description . ' Copy ' . now()->format('Y-m-d H:i:s');
                    })
                    ->successRedirectUrl(fn (WebhookConfiguration $replica) => EditWebhookConfiguration::getUrl(['record' => $replica])),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateIcon('tabler-webhook')
            ->emptyStateDescription('')
            ->emptyStateHeading(trans('admin/webhook.no_webhooks'))
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->persistFiltersInSession()
            ->filters([
                SelectFilter::make('type')
                    ->options(self::TYPES)
                    ->attribute('type'),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ToggleButtons::make('type')
                    ->live()
                    ->inline()
                    ->options(self::TYPES)
                    ->default('standalone')
                    ->icons([
                        'standalone' => 'tabler-world-www',
                        'discord' => 'tabler-brand-discord',
                    ])
                    ->colors([
                        'standalone' => null,
                        'discord' => Color::hex('#5865F2'),
                    ])
                    ->afterStateHydrated(function () {
                        AlertBanner::make()
                            ->title('Help')
                            ->body('You have to wrap variable name in between {{ }} for example if you want to get the name from the api you can use {{name}}')
                            ->icon('tabler-question-mark')
                            ->info()
                            ->send();
                    }),
                TextInput::make('description')
                    ->label(trans('admin/webhook.description'))
                    ->required(),
                TextInput::make('endpoint')
                    ->label(trans('admin/webhook.endpoint'))
                    ->activeUrl()
                    ->required()
                    ->columnSpanFull(),
                // ->afterStateUpdated(fn ($state, Set $set) => $set('type', str($state)->contains('discord.com') ? 'discord' : 'standalone')),
                Section::make('Discord')
                    ->hidden(fn (Get $get) => $get('type') === 'standalone')
                    ->dehydratedWhenHidden()
                    ->schema(fn () => self::getDiscordFields())
                    ->view('filament.components.section')
                    ->viewData([
                        'record' => self::getClonedModel(),
                        'pollingInterval' => null,
                    ])
                    ->aside()
                    ->formBefore(),
                Section::make('Events')
                    ->collapsible()->collapsed()
                    ->schema([
                        CheckboxList::make('events')
                            ->lazy()
                            ->options(fn () => WebhookConfiguration::filamentCheckboxList())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->columnSpanFull()
                            ->gridDirection('row')
                            ->required(),
                    ]),
            ]);
    }

    /** @return array<array-key, mixed> */
    private static function getDiscordFields(): array
    {
        return [
            Section::make('Profile')
                ->collapsible()
                ->schema([
                    TextInput::make('username')
                        ->label('Username')
                        ->afterStateUpdated(function ($state, $livewire) {
                            self::getClonedModel(['username' => $state], $livewire);
                        }),
                    TextInput::make('avatar_url')
                        ->label('Avatar Url')
                        ->afterStateUpdated(function ($state, $livewire) {
                            self::getClonedModel(['avatar_url' => $state], $livewire);
                        }),
                ]),
            Section::make('Message')
                ->collapsible()
                ->schema([
                    TextInput::make('content')
                        ->label('Message')
                        ->live()
                        ->required(fn (Get $get) => empty($get('embeds')))
                        ->afterStateUpdated(function ($state, $livewire) {
                            self::getClonedModel(['content' => $state], $livewire);
                        }),
                    TextInput::make('thread_name')
                        ->label('Forum Thread Name'),
                    CheckboxList::make('flags')
                        ->label('Flags')
                        ->options([
                            'embeds' => 'Suppress Embeds',
                            'notifications' => 'Suppress Notifications',
                        ]),
                ]),
            /*
                Section::make('Attachments')
                    ->collapsible()->collapsed()
                    ->schema([
                        FileUpload::make('files')
                            ->label('Attachments')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize((int) round(25 * (config('panel.use_binary_prefix') ? 1.048576 * 1024 : 1000)))
                            ->directory('discord-attachments'),
                    ]),
                */
            Repeater::make('embeds')
                ->itemLabel(fn (array $state) => $state['title'])
                ->addActionLabel('Add embed')
                ->required(fn (Get $get) => $get('../messages.needstobeastringhere.content') === '')
                ->afterStateUpdated(function (array $state, $livewire) {
                    self::getClonedModel($state, $livewire);
                })
                // ->grid()
                ->reorderable()
                ->collapsible()
                ->maxItems(10)
                ->schema([
                    Section::make('Author')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('author.name')
                                ->label('Author')
                                ->required(fn (Get $get) => filled($get('author.url')) || filled($get('author.icon_url'))),
                            TextInput::make('author.url')
                                ->label('Author URL'),
                            TextInput::make('author.icon_url')
                                ->label('Author Icon URL'),
                        ]),
                    Section::make('Body')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('title')
                                ->label('Title')
                                ->required(fn (Get $get) => $get('description') === null),
                            Textarea::make('description')
                                ->label('Description')
                                ->required(fn (Get $get) => $get('title') === null),
                            ColorPicker::make('color')
                                ->label('Embed Color')
                                ->hex(),
                            TextInput::make('url')
                                ->label('URL'),
                        ]),
                    Section::make('Images')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('image.url')
                                ->label('Image URL'),
                            TextInput::make('thumbnail.url')
                                ->label('Thumbnail URL'),
                        ]),
                    Section::make('Footer')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('footer.text')
                                ->label('Footer'),
                            /* TextInput::make('timestamp')
                                ->label('Timestamp')
                                ->hintAction(Action::make('now')->action(fn (Set $set) => $set('timestamp', Carbon::now()->getTimestamp()))), */
                            Checkbox::make('has_timestamp')
                                ->label('Has Timestamp'),
                            TextInput::make('footer.icon_url')
                                ->label('Footer Icon URL'),
                        ]),
                    Section::make('Fields')
                        ->collapsible()->collapsed()
                        ->schema([
                            Repeater::make('fields')
                                ->reorderable()
                                ->collapsible()
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required(),
                                    Textarea::make('value')
                                        ->label('Field Value')
                                        ->rows(4)
                                        ->required(),
                                    Checkbox::make('inline')
                                        ->label('Inline Field'),
                                ]),
                        ]),
                ]),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function getClonedModel(?array $data = null, ?Component $livewire = null): WebhookConfiguration
    {
        $model = self::$model::first();

        if (!$data) {
            return self::$clone ?? $model;
        }

        if (self::$clone) {
            self::$clone->update($data);
        } else {
            self::$clone = $model->replicate()->fill($data);
            self::$clone->save();
        }

        // dump(collect(self::$clone->getChanges())->except(['updated_at'])->all());

        $livewire->dispatch('updateData', self::$clone->toArray());

        return self::$clone;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookConfigurations::route('/'),
            'create' => Pages\CreateWebhookConfiguration::route('/create'),
            'view' => Pages\ViewWebhookConfiguration::route('/{record}'),
            'edit' => Pages\EditWebhookConfiguration::route('/{record}/edit'),
        ];
    }
}
