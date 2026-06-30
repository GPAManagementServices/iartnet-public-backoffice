<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Institution;
use App\Models\Person;
use App\Support\CaseInsensitiveJsonColumnSearch;
use App\Support\RichTextSanitizer;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Activities';

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        return $record->getTranslation('title', app()->getLocale()) ?: 'Activity #'.$record->getKey();
    }

    public static function form(Form $form): Form
    {
        $locales = config('translatable.locales') ?? ['it', 'en'];

        // EN first, IT second
        $locales = collect($locales)
            ->sortBy(fn (string $locale) => $locale === 'en' ? 0 : ($locale === 'it' ? 1 : 2))
            ->values()
            ->toArray();

        $currentLocale = app()->getLocale();

        return $form->schema([
            Tabs::make('ActivityTabs')->tabs([
                Tabs\Tab::make('General')->schema([
                    Section::make('Title & status')
                        ->schema([
                            self::translatableTextInputs('title', 'Title', $locales),
                            self::translatableTextInputs('slug', 'Slug', $locales),

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

                    Section::make('Subtitle & description')
                        ->schema([
                            self::translatableTextInputs('subtitle', 'Subtitle', $locales),
                            self::translatableTextareaInputs('abstract_text', 'Abstract', $locales),
                            self::translatableRichEditors('description', 'Description', $locales),
                        ])
                        ->columns(1),

                    Section::make('Schedule & location')
                        ->schema([
                            DatePicker::make('start_date')->label('Start date')->nullable(),
                            TimePicker::make('start_hour')->label('Start time')->seconds(false)->nullable(),
                            DatePicker::make('end_date')->label('End date')->nullable(),
                            TimePicker::make('end_hour')->label('End time')->seconds(false)->nullable(),
                            self::translatableTextInputs('location', 'Location', $locales),
                        ])
                        ->columns(1),
                ]),

                Tabs\Tab::make('Relations')->schema([
                    Section::make('People groups')
                        ->schema([
                            Repeater::make('people')
                                ->label('People groups')
                                ->schema([
                                    Grid::make()->schema(
                                        collect($locales)->map(fn ($locale) => TextInput::make("label.$locale")
                                            ->label('Label ('.strtoupper($locale).')')
                                            ->maxLength(255)
                                        )->toArray()
                                    ),

                                    Select::make('people_ids')
                                        ->label('People')
                                        ->multiple()
                                        ->preload()
                                        ->options(fn () => Person::all()
                                            ->mapWithKeys(fn ($person) => [
                                                $person->id => $person->getTranslation('first_name', $currentLocale)
                                                    .' '.
                                                    $person->getTranslation('last_name', $currentLocale),
                                            ])
                                            ->toArray()
                                        )
                                        ->searchable(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state) => $state['label'][$currentLocale] ?? null),
                        ]),

                    Section::make('Institutions')
                        ->schema([
                            Select::make('institutions')
                                ->label('Institutions')
                                ->multiple()
                                ->preload()
                                ->options(fn () => Institution::all()
                                    ->mapWithKeys(fn ($inst) => [
                                        $inst->id => $inst->getTranslation('name', $currentLocale),
                                    ])
                                    ->toArray()
                                )
                                ->searchable(),
                        ]),

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
                        ]),
                ]),

                Tabs\Tab::make('Media')->schema([
                    Section::make('Images')
                        ->schema([
                            CuratorPicker::make('cover_image_id')->label('Cover image')->nullable(),

                            self::translatableTextInputs('cover_image_alt', 'Cover image alt', $locales),

                            self::translatableTextareaInputs('cover_image_caption', 'Cover caption (didascalia)', $locales),

                            CuratorPicker::make('opengraph_picture_id')->label('OpenGraph picture')->nullable(),
                        ]),

                    Section::make('Gallery')
                        ->schema([
                            Repeater::make('gallery')
                                ->label('Gallery')
                                ->schema([
                                    CuratorPicker::make('media_id')
                                        ->label('Image')
                                        ->required(),
                                    Textarea::make('caption.it')
                                        ->label('Caption (IT)')
                                        ->rows(2)
                                        ->maxLength(5000)
                                        ->nullable(),
                                    Textarea::make('caption.en')
                                        ->label('Caption (EN)')
                                        ->rows(2)
                                        ->maxLength(5000)
                                        ->nullable(),
                                ])
                                ->defaultItems(0)
                                ->collapsible()
                                ->reorderable()
                                ->itemLabel(function (array $state): ?string {
                                    $mid = $state['media_id'] ?? null;

                                    return is_numeric($mid) ? 'Media #'.$mid : 'Image';
                                })
                                ->addActionLabel('Add gallery image')
                                ->nullable(),
                        ]),

                    Section::make('Attachments')
                        ->schema([
                            Repeater::make('attachments')
                                ->label('Attachments')
                                ->schema([
                                    FileUpload::make('path')
                                        ->label('File')
                                        ->disk('public')
                                        ->directory('activities/attachments')
                                        ->downloadable()
                                        ->preserveFilenames()
                                        ->required(),
                                    TextInput::make('title')
                                        ->label('Title')
                                        ->maxLength(255)
                                        ->nullable(),
                                ])
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => self::attachmentRepeaterItemLabel($state))
                                ->addActionLabel('Add attachment')
                                ->nullable(),
                        ]),
                ]),

                Tabs\Tab::make('SEO')->schema([
                    Section::make('Meta')
                        ->schema([
                            self::translatableTextInputs('meta_title', 'Meta title', $locales),
                            self::translatableTextareaInputs('meta_description', 'Meta description', $locales),
                        ]),

                    Section::make('OpenGraph')
                        ->schema([
                            self::translatableTextInputs('opengraph_title', 'OpenGraph title', $locales),
                            self::translatableTextareaInputs('opengraph_description', 'OpenGraph description', $locales),
                            self::translatableTextInputs('opengraph_picture_alt', 'OpenGraph image alt', $locales),
                        ]),
                ]),

                Tabs\Tab::make('Extra')->schema([
                    Section::make('Video')
                        ->schema([
                            Repeater::make('video_urls')
                                ->label('Video URLs')
                                ->schema([
                                    TextInput::make('url')
                                        ->label('URL')
                                        ->url()
                                        ->maxLength(2048)
                                        ->required(),
                                ])
                                ->defaultItems(0)
                                ->reorderable()
                                ->addActionLabel('Add video URL')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => is_string($state['url'] ?? null) && $state['url'] !== ''
                                    ? $state['url']
                                    : 'Video URL')
                                ->afterStateHydrated(function (Repeater $component, $state): void {
                                    if ($state === null || $state === []) {
                                        $component->state([]);

                                        return;
                                    }

                                    if (! is_array($state)) {
                                        return;
                                    }

                                    if (isset($state[0]) && is_string($state[0])) {
                                        $component->state(
                                            collect($state)->map(fn (string $u) => ['url' => $u])->all()
                                        );
                                    }
                                })
                                ->nullable(),
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable(),

            TextColumn::make('title')
                ->label('Title')
                ->getStateUsing(fn (Activity $record) => (string) $record->getTranslation('title', app()->getLocale()))
                ->searchable(query: fn (Builder $query, string $search): Builder => CaseInsensitiveJsonColumnSearch::whereMatches(
                    $query,
                    'title',
                    $search
                )),

            TagsColumn::make('categories')
                ->label('Categories')
                ->getStateUsing(function (Activity $record) {
                    $locale = app()->getLocale();

                    return $record->categories
                        ->map(fn (Category $cat) => (string) $cat->getTranslation('name', $locale))
                        ->filter(fn ($v) => is_string($v) && $v !== '')
                        ->values()
                        ->all();
                }),

            TextColumn::make('status')->badge()->sortable(),

            TextColumn::make('created_at')->label('Created at')->dateTime('d/m/Y H:i')->sortable(),
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
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }

    /* -----------------------------------------------------------------
     | Translatable helpers
     |----------------------------------------------------------------- */

    protected static function translatableTextInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()->schema(
            collect($locales)->map(function ($locale) use ($field, $label) {
                $input = TextInput::make("$field.$locale")
                    ->label("$label (".strtoupper($locale).')')
                    ->maxLength(255);

                if ($field === 'title') {
                    $input = $input
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($locale) {
                            // 1) slug: se vuoto, genera da title
                            $currentSlug = $get("slug.$locale");
                            if (empty($currentSlug)) {
                                $set("slug.$locale", Str::slug((string) $state));
                            }

                            // 2) meta_title: se vuoto, copia title
                            $currentMetaTitle = $get("meta_title.$locale");
                            if (empty($currentMetaTitle) && ! empty($state)) {
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

    /**
     * Filament può passare `path` come stringa o array (upload / stato intermedio); `title` resta stringa scalare.
     */
    protected static function attachmentRepeaterItemLabel(array $state): ?string
    {
        $title = $state['title'] ?? null;
        if (is_string($title)) {
            $t = trim($title);

            if ($t !== '') {
                return $t;
            }
        } elseif (is_array($title)) {
            foreach ($title as $v) {
                if (is_string($v) && trim($v) !== '') {
                    return trim($v);
                }
            }
        }

        $path = $state['path'] ?? null;
        $pathString = self::coerceAttachmentPathToString($path);

        return $pathString !== null ? basename($pathString) : null;
    }

    protected static function coerceAttachmentPathToString(mixed $path): ?string
    {
        if (is_string($path)) {
            $p = trim($path);

            return $p !== '' ? $p : null;
        }

        if (! is_array($path)) {
            return null;
        }

        foreach ($path as $item) {
            if (is_string($item)) {
                $s = trim($item);

                if ($s !== '') {
                    return $s;
                }
            }
        }

        return null;
    }

    protected static function translatableTextareaInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()->schema(
            collect($locales)->map(fn ($locale) => Textarea::make("$field.$locale")
                ->label("$label (".strtoupper($locale).')')
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
