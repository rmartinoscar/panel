<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WebhookResource\Pages;
use App\Filament\Admin\Resources\WebhookResource\Pages\DiscordPreview;
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
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebhookResource extends Resource
{
    protected static ?string $model = WebhookConfiguration::class;

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
                    ->limit(60),
                TextColumn::make('description')
                    ->label(trans('admin/webhook.table.description')),
            ])
            ->actions([
                ViewAction::make()
                    ->hidden(fn ($record) => static::canEdit($record)),
                EditAction::make(),
                DeleteAction::make(),
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
                    ->icons([
                        'standalone' => 'tabler-world-www',
                        'discord' => 'tabler-brand-discord',
                    ])
                    ->colors([
                        'standalone' => null,
                        'discord' => Color::hex('#5865F2'),
                    ]),
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
                    /* ->headerActions([
                        Action::make('preview')
                            ->label('Preview')
                            ->disabled(fn (Get $get) => empty($get('messages')) && empty($get('embeds')))
                            ->url(fn (WebhookConfiguration $webhookConfiguration) => DiscordPreview::getUrl(['record' => $webhookConfiguration])),
                    ]) */,
                CheckboxList::make('events')
                    ->lazy()
                    ->options(fn () => WebhookConfiguration::filamentCheckboxList())
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(3)
                    ->columnSpanFull()
                    ->gridDirection('row')
                    ->required(),
            ]);
    }

    /** @return array<array-key, mixed> */
    private static function getDiscordFields(): array
    {
        return [
            Repeater::make('messages')
                ->label('Messages')
                ->addActionLabel('Add message')
                ->required(fn (Get $get) => empty($get('embeds')))
                ->grid()
                ->reorderable()->orderColumn()
                ->collapsible()
                ->columnSpan(2)
                ->formatStateUsing(fn (WebhookConfiguration $webhookConfiguration) => array_get(json_decode($webhookConfiguration['payload'], true), 'messages'))
                ->schema([
                    Section::make('Message')
                        ->collapsible()
                        ->schema([
                            TextInput::make('content')
                                ->label('Content'),
                            TextInput::make('thread_name')
                                ->label('Forum Thread Name'),
                            CheckboxList::make('flags')
                                ->label('Flags')
                                ->options([
                                    'embeds' => 'Suppress Embeds',
                                    'notifications' => 'Suppress Notifications',
                                ]),
                        ]),
                    Section::make('Attachments')
                        ->collapsible()->collapsed()
                        ->schema([
                            FileUpload::make('files')
                                ->label('Attachments')
                                ->multiple()
                                ->directory('discord-attachments'),
                        ]),
                ]),
            Repeater::make('embeds')
                ->itemLabel(fn (array $state) => $state['title'])
                ->addActionLabel('Add embed')
                ->required(fn (Get $get) => $get('../messages.needstobeastringhere.content') === '')
                ->grid()
                ->reorderable()->orderColumn()
                ->collapsible()
                ->columnSpan(2)
                ->formatStateUsing(fn (WebhookConfiguration $webhookConfiguration) => array_get(json_decode($webhookConfiguration['payload'], true), 'embeds'))
                ->maxItems(10)
                ->schema([
                    // Hidden::make('id')->formatStateUsing(Str::random(9)),
                    Section::make('Author')
                        ->collapsible()->collapsed()
                        ->schema([
                            TextInput::make('author')
                                ->label('Author'),
                            TextInput::make('author_url')
                                ->label('Author URL'),
                            TextInput::make('author_icon_url')
                                ->label('Author Icon URL'),
                        ]),
                    Section::make('Body')
                        ->collapsible()->collapsed()
                        ->schema([
                            TextInput::make('title')
                                ->label('Title')
                                ->required(fn (Get $get) => $get('description') === null),
                            Textarea::make('description')
                                ->label('Description')
                                ->required(fn (Get $get) => $get('title') === null),
                            ColorPicker::make('color')
                                ->label('Embed Color'),
                            TextInput::make('url')
                                ->label('URL'),
                        ]),
                    Section::make('Images')
                        ->collapsible()->collapsed()
                        ->schema([
                            Repeater::make('images')
                                ->grid()
                                ->reorderable()->orderColumn()
                                ->columnSpan(2)
                                ->schema([
                                    TextInput::make('image')
                                        ->label('Image URL'),
                                ]),
                            TextInput::make('thumbnail')
                                ->label('Thumbnail URL'),
                        ]),
                    Section::make('Footer')
                        ->collapsible()->collapsed()
                        ->schema([
                            TextInput::make('footer')
                                ->label('Footer'),
                            TextInput::make('timestamp')
                                ->label('Timestamp'),
                            TextInput::make('footer_icon_url')
                                ->label('Footer Icon URL'),
                        ]),
                    Section::make('Fields')
                        ->collapsible()->collapsed()
                        ->schema([
                            Repeater::make('fields')
                                // ->grid()
                                ->reorderable()->orderColumn()
                                ->collapsible()
                                // ->columnSpan(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required(),
                                    Textarea::make('value')
                                        ->label('Field Value')
                                        ->hintIcon('tabler-question-mark')
                                        ->hintIconTooltip('You have to wrap variable name in between {{ }} for example if you want to get the name from the api you can use {{name}}')
                                        ->rows(4)
                                        ->required(),
                                    Checkbox::make('inline')
                                        ->label('Inline Field'),
                                ]),
                        ]),
                ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookConfigurations::route('/'),
            'create' => Pages\CreateWebhookConfiguration::route('/create'),
            'view' => Pages\ViewWebhookConfiguration::route('/{record}'),
            'edit' => Pages\EditWebhookConfiguration::route('/{record}/edit'),
            'preview' => Pages\DiscordPreview::route('/{record}/preview'),
        ];
    }
}
