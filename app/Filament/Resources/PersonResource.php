<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonResource\Pages;
use App\Models\Category;
use App\Models\Institution;
use App\Models\PeopleRole;
use App\Models\Person;
use App\Support\CaseInsensitiveJsonColumnSearch;
use App\Support\EditorialHtmlSanitizer;
use App\Support\HttpExternalUrl;
use App\Support\RichTextSanitizer;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'People';

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        $locale = app()->getLocale();

        $first = (string) $record->getTranslation('first_name', $locale);
        $last = (string) $record->getTranslation('last_name', $locale);

        $full = trim($first.' '.$last);

        return $full !== '' ? $full : ('Person #'.$record->getKey());
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
            Tabs::make('PersonTabs')->tabs([
                Tabs\Tab::make('General')->schema([
                    Section::make('Main data')
                        ->schema([
                            self::translatableTextInputs('first_name', 'First name', $locales),
                            self::translatableTextInputs('last_name', 'Last name', $locales),
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

                    Section::make('Role & bio')
                        ->schema([
                            Select::make('people_role_id')
                                ->label('Global role (catalog)')
                                ->options(fn () => PeopleRole::query()
                                    ->orderBy('sort_order')
                                    ->orderBy('name_en')
                                    ->get()
                                    ->mapWithKeys(fn (PeopleRole $r) => [
                                        $r->id => $r->name_en.' · '.$r->name_it,
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false)
                                ->helperText('Optional. Labels sync to API JSON `role` on save.'),

                            self::translatableRichEditors('shortbio', 'Short bio', $locales),
                        ])
                        ->columns(1),
                ]),

                Tabs\Tab::make('Relations')->schema([
                    Section::make('Institution roles')
                        ->schema([
                            Repeater::make('institution_roles')
                                ->label('Institution roles')
                                ->default([])
                                ->schema([
                                    Select::make('institution_id')
                                        ->label('Institution')
                                        ->required()
                                        ->preload()
                                        ->searchable()
                                        ->options(fn () => Institution::query()
                                            ->get()
                                            ->mapWithKeys(fn ($inst) => [
                                                (string) $inst->id => (string) $inst->getTranslation('name', $currentLocale),
                                            ])
                                            ->toArray()
                                        )
                                        ->native(false),

                                    Select::make('people_role_id')
                                        ->label('Role (catalog)')
                                        ->options(fn () => PeopleRole::query()
                                            ->orderBy('sort_order')
                                            ->orderBy('name_en')
                                            ->get()
                                            ->mapWithKeys(fn (PeopleRole $r) => [
                                                $r->id => $r->name_en.' · '.$r->name_it,
                                            ])
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->native(false),
                                ])
                                ->collapsible()
                                ->itemLabel(function (array $state) {
                                    $inst = $state['institution_id'] ?? null;
                                    $prId = $state['people_role_id'] ?? null;
                                    if ($prId) {
                                        $r = PeopleRole::query()->find((int) $prId);

                                        return $r ? $r->name_en : "Role #{$prId}";
                                    }

                                    return $inst ? "Institution #{$inst}" : 'Institution role';
                                }),
                        ])
                        ->columns(1),

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
                            CuratorPicker::make('image_id')
                                ->label('Image')
                                ->relationship('image', 'id')
                                ->nullable(),

                            self::translatableTextInputs('image_alt', 'Image alt', $locales),

                            CuratorPicker::make('opengraph_picture_id')
                                ->label('OpenGraph picture')
                                ->relationship('opengraphPicture', 'id')
                                ->nullable(),
                        ])
                        ->columns(1),
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

                Tabs\Tab::make('Contacts')->schema([
                    Section::make('Contact details')
                        ->schema([
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->nullable(),

                            TextInput::make('website')
                                ->label('Website')
                                ->placeholder('https://example.com')
                                ->maxLength(2048)
                                ->nullable()
                                ->rule(function () {
                                    return function (string $attribute, mixed $value, \Closure $fail): void {
                                        if ($value === null || $value === '') {
                                            return;
                                        }
                                        if (! is_string($value) || ! HttpExternalUrl::isValid($value)) {
                                            $fail('The website must be a valid http or https URL.');
                                        }
                                    };
                                })
                                ->dehydrateStateUsing(fn (?string $state) => HttpExternalUrl::normalizeForStorage($state)),
                        ])
                        ->columns(1),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),

                TextColumn::make('first_name')
                    ->label('First name')
                    ->getStateUsing(fn (Person $record) => (string) $record->getTranslation('first_name', app()->getLocale()))
                    ->searchable(query: fn (Builder $query, string $search): Builder => CaseInsensitiveJsonColumnSearch::whereMatches(
                        $query,
                        'first_name',
                        $search
                    )),

                TextColumn::make('last_name')
                    ->label('Last name')
                    ->getStateUsing(fn (Person $record) => (string) $record->getTranslation('last_name', app()->getLocale()))
                    ->searchable(query: fn (Builder $query, string $search): Builder => CaseInsensitiveJsonColumnSearch::whereMatches(
                        $query,
                        'last_name',
                        $search
                    )),

                TextColumn::make('website')
                    ->label('Website')
                    ->toggleable()
                    ->searchable()
                    ->url(fn (Person $record) => $record->website)
                    ->openUrlInNewTab()
                    ->limit(40),

                TagsColumn::make('categories')
                    ->label('Categories')
                    ->getStateUsing(function (Person $record) {
                        $locale = app()->getLocale();

                        return $record->categories
                            ->map(fn (Category $cat) => (string) $cat->getTranslation('name', $locale))
                            ->filter(fn ($v) => is_string($v) && $v !== '')
                            ->values()
                            ->all();
                    }),

                TextColumn::make('status')->label('Status')->badge(),

                TextColumn::make('peopleRole.name_en')
                    ->label('Global role')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('updated_at')->label('Updated at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('institution_id')
                    ->label('Institution')
                    ->options(function () use ($locale): array {
                        return Institution::query()
                            ->get()
                            ->mapWithKeys(fn (Institution $inst) => [
                                (string) $inst->id => (string) $inst->getTranslation('name', $locale),
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $query->when($value, function (Builder $q) use ($value) {
                            // institutions è un JSON array di ID (nel tuo saving li salvi come string)
                            return $q->whereJsonContains('institutions', (string) $value);
                        });
                    }),
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
            'index' => Pages\ListPeople::route('/'),
            'create' => Pages\CreatePerson::route('/create'),
            'edit' => Pages\EditPerson::route('/{record}/edit'),
        ];
    }

    protected static function translatableTextInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()->schema(
            collect($locales)->map(function ($locale) use ($field, $label) {
                $input = TextInput::make("{$field}.{$locale}")
                    ->label("{$label} (".strtoupper($locale).')')
                    ->maxLength(255);

                $buildSlug = function (string $slugLocale, callable $get): string {
                    $first = trim((string) ($get("first_name.$slugLocale") ?? ''));
                    $last = trim((string) ($get("last_name.$slugLocale") ?? ''));

                    $parts = array_values(array_filter([
                        Str::slug($first),
                        Str::slug($last),
                    ], fn ($v) => $v !== ''));

                    return implode('-', $parts);
                };

                $updateSlugIfNotManual = function (string $slugLocale, callable $set, callable $get) use ($buildSlug) {
                    $isManual = (bool) ($get("__slug_manual.$slugLocale") ?? false);
                    if ($isManual) {
                        return;
                    }

                    $newSlug = $buildSlug($slugLocale, $get);
                    if ($newSlug === '') {
                        return;
                    }

                    $set("slug.$slugLocale", $newSlug);
                };

                if (in_array($field, ['first_name', 'last_name'], true)) {
                    $input = $input
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($field, $locale, $updateSlugIfNotManual) {
                            $state = (string) $state;

                            if ($locale === 'en') {
                                $itValue = (string) ($get("{$field}.it") ?? '');
                                if ($itValue === '' && $state !== '') {
                                    $set("{$field}.it", $state);
                                }
                            }

                            if ($locale === 'it') {
                                $enValue = (string) ($get("{$field}.en") ?? '');
                                if ($enValue === '' && $state !== '') {
                                    $set("{$field}.en", $state);
                                }
                            }

                            // meta_title: se vuoto, usa "First Last" nella lingua corrente
                            $currentMeta = $get("meta_title.$locale");
                            if (blank($currentMeta)) {
                                $first = trim((string) ($get("first_name.$locale") ?? ''));
                                $last = trim((string) ($get("last_name.$locale") ?? ''));

                                $fullName = trim($first.' '.$last);

                                if ($fullName !== '') {
                                    $set("meta_title.$locale", $fullName);
                                }
                            }

                            $updateSlugIfNotManual($locale, $set, $get);

                            $other = $locale === 'it' ? 'en' : 'it';
                            $updateSlugIfNotManual($other, $set, $get);
                        });
                }

                if ($field === 'slug') {
                    $input = $input
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($locale) {
                            $slug = Str::slug((string) $state);
                            $set("slug.$locale", $slug);
                            $set("__slug_manual.$locale", true);
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
                            $html = RichTextSanitizer::stripTrixAttachmentLinks(
                                is_string($state) ? $state : null
                            );

                            return app(EditorialHtmlSanitizer::class)->sanitize($html);
                        });
                })->toArray()
            );
    }
}
