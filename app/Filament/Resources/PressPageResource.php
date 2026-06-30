<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PressPageResource\Pages;
use App\Models\PressPage;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class PressPageResource extends Resource
{
    protected static ?string $model = PressPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Press';

    protected static ?string $modelLabel = 'Press';

    protected static ?string $slug = 'press';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Press')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Main')->schema([
                        Section::make('Page')->schema([
                            Grid::make(12)->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (((string) ($get('meta_title') ?? '')) === '' && (string) $state !== '') {
                                            $set('meta_title', (string) $state);
                                        }
                                    })
                                    ->columnSpan(8),

                                Select::make('status')
                                    ->label('Status')
                                    ->required()
                                    ->options([
                                        'draft' => 'Draft',
                                        'published' => 'Published',
                                        'archived' => 'Archived',
                                    ])
                                    ->default('draft')
                                    ->columnSpan(4),

                                Textarea::make('intro')
                                    ->label('Introduction')
                                    ->rows(6)
                                    ->columnSpan(12),
                            ]),
                        ]),
                    ]),

                    Tab::make('Contacts')->schema([
                        Section::make('Contacts')->schema([
                            Repeater::make('contacts')
                                ->relationship('contacts')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Label')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('status')
                                        ->label('Status')
                                        ->required()
                                        ->options([
                                            'draft' => 'Draft',
                                            'published' => 'Published',
                                        ])
                                        ->default('published'),
                                ])
                                ->defaultItems(0)
                                ->reorderable()
                                ->orderColumn('sort_order')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['email'] ?? 'Contact')
                                ->addActionLabel('Add contact'),
                        ]),
                    ]),

                    Tab::make('Press Releases')->schema([
                        Section::make('Press Releases')->schema([
                            Repeater::make('releases')
                                ->relationship('releases')
                                ->schema(self::releaseRepeaterSchema())
                                ->defaultItems(0)
                                ->reorderable()
                                ->orderColumn('sort_order')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Press release')
                                ->addActionLabel('Add press release'),
                        ]),
                    ]),

                    Tab::make('Documents')->schema([
                        Section::make('Documents')->schema([
                            Repeater::make('documents')
                                ->relationship('documents')
                                ->schema(self::documentRepeaterSchema())
                                ->defaultItems(0)
                                ->reorderable()
                                ->orderColumn('sort_order')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Document')
                                ->addActionLabel('Add document'),
                        ]),
                    ]),

                    Tab::make('SEO')->schema([
                        Section::make('SEO')->schema([
                            TextInput::make('meta_title')->label('Meta title')->maxLength(255),
                            Textarea::make('meta_description')->label('Meta description')->rows(4),
                        ]),
                    ]),

                    Tab::make('OpenGraph')->schema([
                        Section::make('OpenGraph')->schema([
                            TextInput::make('opengraph_title')->label('OpenGraph title')->maxLength(255),
                            Textarea::make('opengraph_description')->label('OpenGraph description')->rows(4),
                            CuratorPicker::make('opengraph_picture_id')->label('OpenGraph picture'),
                        ]),
                    ]),
                ]),
        ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function releaseRepeaterSchema(): array
    {
        return [
            TextInput::make('title')->label('Title')->required()->maxLength(255),
            Textarea::make('description')->label('Description')->rows(3),
            CuratorPicker::make('cover_image_id')->label('Cover image'),
            TextInput::make('cover_image_alt')->label('Cover image alt')->maxLength(255),
            ...self::destinationFields(),
            Select::make('status')
                ->label('Status')
                ->required()
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])
                ->default('published'),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function documentRepeaterSchema(): array
    {
        return [
            TextInput::make('category')->label('Category')->maxLength(255),
            TextInput::make('title')->label('Title')->required()->maxLength(255),
            TextInput::make('date')->label('Date')->type('date'),
            ...self::destinationFields(),
            Select::make('status')
                ->label('Status')
                ->required()
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])
                ->default('published'),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function destinationFields(): array
    {
        return [
            Select::make('destination_type')
                ->label('Link type')
                ->required()
                ->options([
                    'none' => 'None',
                    'file' => 'File',
                    'external' => 'External URL',
                ])
                ->default('none')
                ->live(),
            FileUpload::make('file_path')
                ->label('File')
                ->disk('public')
                ->directory('press/files')
                ->downloadable()
                ->preserveFilenames()
                ->visible(fn (Get $get): bool => $get('destination_type') === 'file')
                ->required(fn (Get $get): bool => $get('destination_type') === 'file'),
            TextInput::make('external_url')
                ->label('External URL')
                ->url()
                ->maxLength(2048)
                ->visible(fn (Get $get): bool => $get('destination_type') === 'external')
                ->required(fn (Get $get): bool => $get('destination_type') === 'external'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function resolveRecordRouteBinding(int | string $key): ?Model
    {
        if ((string) $key !== PressPage::SINGLETON_KEY) {
            return parent::resolveRecordRouteBinding($key);
        }

        return PressPage::resolveSingleton();
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditPressPage::route('/{record}/edit'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => PressPage::SINGLETON_KEY]);
    }
}
