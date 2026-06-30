<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionResource\Pages;
use App\Models\Category;
use App\Models\Institution;
use App\Support\CaseInsensitiveJsonColumnSearch;
use App\Support\RichTextSanitizer;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Institutions';

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        return (string) $record->getTranslation('name', app()->getLocale())
            ?: 'Institution #'.$record->getKey();
    }

    public static function form(Form $form): Form
    {
        $locales = config('translatable.locales') ?? ['it', 'en'];

        // EN first, IT second, others after
        $locales = collect($locales)
            ->sortBy(fn (string $locale) => $locale === 'en' ? 0 : ($locale === 'it' ? 1 : 2))
            ->values()
            ->toArray();

        $currentLocale = app()->getLocale();

        return $form->schema([
            Tabs::make('InstitutionTabs')->tabs([
                Tabs\Tab::make('General')->schema([
                    Section::make('Main data')
                        ->schema([
                            self::translatableTextInputs('name', 'Name', $locales),
                            self::translatableTextInputs('slug', 'Slug', $locales),

                            TextInput::make('website')
                                ->label('Website')
                                ->placeholder('https://example.com')
                                ->maxLength(2048)
                                ->nullable()
                                ->rule('url')
                                ->dehydrateStateUsing(function (?string $state) {
                                    $state = trim((string) $state);

                                    if ($state === '') {
                                        return null;
                                    }

                                    // Se l’utente scrive "example.com", aggiungo https://
                                    if (! str_starts_with($state, 'http://') && ! str_starts_with($state, 'https://')) {
                                        $state = 'https://'.$state;
                                    }

                                    return $state;
                                }),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'private' => 'Private',
                                    'published' => 'Published',
                                ])
                                ->default('draft')
                                ->required()
                                ->native(false),
                        ])
                        ->columns(1),

                    Section::make('Description')
                        ->schema([
                            self::translatableRichEditors('description', 'Description', $locales),
                        ])
                        ->columns(1),
                ]),

                Tabs\Tab::make('Relations')->schema([
                    Section::make('Categories')
                        ->schema([
                            Select::make('categories')
                                ->label('Categories')
                                ->multiple()
                                ->preload()
                                ->relationship('categories', 'name')
                                ->getOptionLabelFromRecordUsing(fn (Category $record) => (string) $record->getTranslation('name', app()->getLocale())
                                )
                                ->searchable(),
                        ])
                        ->columns(1),
                ]),

                Tabs\Tab::make('Media')->schema([
                    Section::make('Images')
                        ->schema([
                            CuratorPicker::make('logo_image_id')
                                ->label('Logo')
                                ->relationship('logoImage', 'id')
                                ->nullable(),

                            CuratorPicker::make('cover_image_id')
                                ->label('Cover image')
                                ->relationship('coverImage', 'id')
                                ->nullable(),

                            CuratorPicker::make('opengraph_picture_id')
                                ->label('OpenGraph picture')
                                ->relationship('opengraphPicture', 'id')
                                ->nullable(),
                        ])
                        ->columns(1),

                    Section::make('Image alts')
                        ->schema([
                            self::translatableTextInputs('logo_image_alt', 'Logo alt', $locales),
                            self::translatableTextInputs('cover_image_alt', 'Cover image alt', $locales),
                        ])
                        ->columns(1)
                        ->collapsible(),
                ]),

                Tabs\Tab::make('SEO')->schema([
                    Section::make('Meta')
                        ->schema([
                            self::translatableTextInputs('meta_title', 'Meta title', $locales),
                            self::translatableTextareaInputs('meta_description', 'Meta description', $locales),
                        ])
                        ->columns(1),

                    Section::make('OpenGraph')
                        ->schema([
                            self::translatableTextInputs('opengraph_title', 'OpenGraph title', $locales),
                            self::translatableTextareaInputs('opengraph_description', 'OpenGraph description', $locales),
                            self::translatableTextInputs('opengraph_picture_alt', 'OpenGraph image alt', $locales),
                        ])
                        ->columns(1),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable(),

            TextColumn::make('name')
                ->label('Name')
                ->getStateUsing(fn (Institution $record) => (string) $record->getTranslation('name', app()->getLocale()))
                ->searchable(query: fn (Builder $query, string $search): Builder => CaseInsensitiveJsonColumnSearch::whereMatches(
                    $query,
                    'name',
                    $search
                )),

            TextColumn::make('website')
                ->label('Website')
                ->toggleable()
                ->searchable()
                ->url(fn (Institution $record) => $record->website)
                ->openUrlInNewTab()
                ->limit(40),

            TagsColumn::make('categories')
                ->label('Categories')
                ->getStateUsing(function (Institution $record) {
                    $locale = app()->getLocale();

                    return $record->categories
                        ->map(fn (Category $cat) => (string) $cat->getTranslation('name', $locale))
                        ->filter(fn ($v) => is_string($v) && $v !== '')
                        ->values()
                        ->all();
                }),

            TextColumn::make('status')->label('Status')->badge(),

            TextColumn::make('updated_at')->label('Updated at')->dateTime('d/m/Y H:i')->sortable(),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutions::route('/'),
            'create' => Pages\CreateInstitution::route('/create'),
            'edit' => Pages\EditInstitution::route('/{record}/edit'),
        ];
    }

    protected static function translatableTextInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()->schema(
            collect($locales)->map(function ($locale) use ($field, $label) {
                $input = TextInput::make("{$field}.{$locale}")
                    ->label("{$label} (".strtoupper($locale).')')
                    ->maxLength(255);

                if ($field === 'name') {
                    $input = $input
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($locale) {
                            // 1) slug: se vuoto, genera da name
                            $currentSlug = $get("slug.$locale");
                            if (blank($currentSlug) && filled($state)) {
                                $set("slug.$locale", Str::slug((string) $state));
                            }

                            // 2) meta_title: se vuoto, copia name
                            $currentMetaTitle = $get("meta_title.$locale");
                            if (blank($currentMetaTitle) && filled($state)) {
                                $set("meta_title.$locale", (string) $state);
                            }
                        });
                }

                if ($field === 'slug') {
                    $input = $input
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set) use ($locale) {
                            $set("slug.$locale", Str::slug((string) $state));
                        })
                        ->rule(function (?Model $record) use ($locale) {
                            $column = $locale === 'it' ? 'slug_it' : 'slug_en';

                            return Rule::unique(static::$model, $column)
                                ->ignore($record?->getKey());
                        });
                }

                return $input;
            })->toArray()
        );
    }

    protected static function translatableTextareaInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()->schema(
            collect($locales)->map(fn ($locale) => Textarea::make("{$field}.{$locale}")
                ->label("{$label} (".strtoupper($locale).')')
                ->rows(3)
            )->toArray()
        );
    }

    protected static function translatableRichEditors(string $field, string $label, array $locales): Grid
    {
        return Grid::make(1)
            ->columnSpanFull()
            ->schema(
                collect($locales)->map(function ($locale) use ($field, $label) {
                    return RichEditor::make("{$field}.{$locale}")
                        ->label("{$label} (".strtoupper($locale).')')
                        ->dehydrateStateUsing(function ($state) {
                            return RichTextSanitizer::stripTrixAttachmentLinks(
                                is_string($state) ? $state : null
                            );
                        });
                })->toArray()
            );
    }
}
