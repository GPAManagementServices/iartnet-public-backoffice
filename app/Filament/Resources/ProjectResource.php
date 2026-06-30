<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Category;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Project;
use App\Support\CaseInsensitiveJsonColumnSearch;
use App\Support\RichTextSanitizer;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Projects';

    public static function form(Form $form): Form
    {
        $locales = ['en', 'it'];

        $currentLocale = app()->getLocale();
        if (! in_array($currentLocale, $locales, true)) {
            $currentLocale = 'en';
        }

        return $form->schema([
            Tabs::make('Project')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Main data')
                        ->schema([
                            Section::make('Main data')
                                ->schema([
                                    Grid::make(12)->schema([
                                        TextInput::make('title.en')
                                            ->label('Title (EN)')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $currentSlug = (string) ($get('slug.en') ?? '');
                                                if ($currentSlug === '' && (string) $state !== '') {
                                                    $set('slug.en', Str::slug((string) $state));
                                                }

                                                $currentMeta = (string) ($get('meta_title.en') ?? '');
                                                if ($currentMeta === '' && (string) $state !== '') {
                                                    $set('meta_title.en', (string) $state);
                                                }
                                            })
                                            ->columnSpan(6),

                                        TextInput::make('title.it')
                                            ->label('Title (IT)')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $currentSlug = (string) ($get('slug.it') ?? '');
                                                if ($currentSlug === '' && (string) $state !== '') {
                                                    $set('slug.it', Str::slug((string) $state));
                                                }

                                                $currentMeta = (string) ($get('meta_title.it') ?? '');
                                                if ($currentMeta === '' && (string) $state !== '') {
                                                    $set('meta_title.it', (string) $state);
                                                }
                                            })
                                            ->columnSpan(6),

                                        TextInput::make('subtitle.en')
                                            ->label('Subtitle (EN)')
                                            ->maxLength(255)
                                            ->nullable()
                                            ->columnSpan(6),

                                        TextInput::make('subtitle.it')
                                            ->label('Subtitle (IT)')
                                            ->maxLength(255)
                                            ->nullable()
                                            ->columnSpan(6),

                                        TextInput::make('slug.en')
                                            ->label('Slug (EN)')
                                            ->helperText('Used for the EN URL. Saved into slug_en.')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, $set) => $set('slug.en', $state ? Str::slug((string) $state) : null))
                                            ->rules(fn (?Project $record) => [
                                                Rule::unique('projects', 'slug_en')->ignore($record?->id),
                                            ])
                                            ->columnSpan(6),

                                        TextInput::make('slug.it')
                                            ->label('Slug (IT)')
                                            ->helperText('Used for the IT URL. Saved into slug_it.')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, $set) => $set('slug.it', $state ? Str::slug((string) $state) : null))
                                            ->rules(fn (?Project $record) => [
                                                Rule::unique('projects', 'slug_it')->ignore($record?->id),
                                            ])
                                            ->columnSpan(6),

                                        Select::make('status')
                                            ->label('Status')
                                            ->required()
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                                'archived' => 'Archived',
                                            ])
                                            ->default('draft')
                                            ->columnSpan(12),
                                    ]),
                                ]),
                        ]),

                    Tab::make('Homepage')
                        ->schema([
                            Section::make('Homepage visibility')
                                ->description('Research projects section on the public Homepage. Independent from the Projects listing page.')
                                ->schema([
                                    Toggle::make('show_in_homepage')
                                        ->label('Show on Homepage')
                                        ->helperText('Show this project in the Research projects section on the Homepage. Does not change publish status or detail page URL.')
                                        ->default(false),

                                    TextInput::make('homepage_order')
                                        ->label('Homepage order')
                                        ->numeric()
                                        ->minValue(0)
                                        ->nullable()
                                        ->helperText('Posizione editoriale nella Homepage. Valori validi: 1, 2, 3, ... — 0 o vuoto = nessuna posizione specifica. Se la posizione è già usata da un altro progetto, il progetto precedente viene dissociato da quella posizione. Gli elementi senza posizione restano visibili se abilitati e seguono l’ordinamento standard.'),
                                ]),
                        ]),

                    Tab::make('Projects listing')
                        ->schema([
                            Section::make('Projects page visibility')
                                ->description('Public /projects grid. Does not remove the project from the sitemap or hide the detail page when published.')
                                ->schema([
                                    Toggle::make('show_in_projects')
                                        ->label('Show on Projects page')
                                        ->helperText('Show this project on the public Projects page.')
                                        ->default(true),

                                    TextInput::make('projects_order')
                                        ->label('Projects page order')
                                        ->numeric()
                                        ->minValue(0)
                                        ->nullable()
                                        ->helperText('Posizione editoriale nella pagina Projects. Valori validi: 1, 2, 3, ... — 0 o vuoto = nessuna posizione specifica. Se la posizione è già usata da un altro progetto, il progetto precedente viene dissociato da quella posizione. Gli elementi senza posizione restano visibili se abilitati e seguono l’ordinamento standard.'),
                                ]),
                        ]),

                    Tab::make('Relations')
                        ->schema([
                            Section::make('People groups')
                                ->schema([
                                    Repeater::make('people')
                                        ->label('People groups')
                                        ->schema([
                                            Grid::make()
                                                ->schema(
                                                    collect($locales)->map(
                                                        fn ($locale) => TextInput::make("label.$locale")
                                                            ->label('Label ('.strtoupper($locale).')')
                                                            ->maxLength(255)
                                                    )->toArray()
                                                ),

                                            Select::make('people_ids')
                                                ->label('People')
                                                ->multiple()
                                                ->preload()
                                                ->searchable()
                                                ->options(function () use ($currentLocale) {
                                                    return Person::query()
                                                        ->orderBy('last_name')
                                                        ->orderBy('first_name')
                                                        ->get()
                                                        ->mapWithKeys(function (Person $person) use ($currentLocale) {
                                                            $first = $person->getTranslation('first_name', $currentLocale)
                                                                ?: $person->getTranslation('first_name', 'en')
                                                                ?: $person->getTranslation('first_name', 'it');

                                                            $last = $person->getTranslation('last_name', $currentLocale)
                                                                ?: $person->getTranslation('last_name', 'en')
                                                                ?: $person->getTranslation('last_name', 'it');

                                                            return [$person->id => trim($first.' '.$last)];
                                                        })
                                                        ->toArray();
                                                }),
                                        ])
                                        ->collapsible()
                                        ->itemLabel(function (array $state) use ($currentLocale): ?string {
                                            return $state['label'][$currentLocale] ?? null;
                                        }),
                                ]),

                            Section::make('Institutions')
                                ->schema([
                                    Select::make('institutions')
                                        ->label('Institutions')
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->options(function () use ($currentLocale) {
                                            return Institution::query()
                                                ->orderBy('id')
                                                ->get()
                                                ->mapWithKeys(function (Institution $inst) use ($currentLocale) {
                                                    $name = $inst->getTranslation('name', $currentLocale)
                                                        ?: $inst->getTranslation('name', 'en')
                                                        ?: $inst->getTranslation('name', 'it');

                                                    return [$inst->id => (string) $name];
                                                })
                                                ->toArray();
                                        }),
                                ]),

                            Section::make('Categories')
                                ->schema([
                                    Select::make('categories')
                                        ->label('Categories')
                                        ->multiple()
                                        ->preload()
                                        ->relationship('categories', 'name')
                                        ->getOptionLabelFromRecordUsing(fn (Category $record) => (string) (
                                            $record->getTranslation('name', app()->getLocale())
                                            ?: $record->getTranslation('name', 'en')
                                            ?: $record->getTranslation('name', 'it')
                                        ))
                                        ->searchable()
                                        ->native(false),
                                ])
                                ->columns(1),
                        ]),

                    Tab::make('Description')
                        ->schema([
                            Section::make('Description')
                                ->schema([
                                    Grid::make(12)->schema([
                                        RichEditor::make('description.en')
                                            ->label('Description (EN)')
                                            ->columnSpan(12)
                                            ->dehydrateStateUsing(function ($state) {
                                                return RichTextSanitizer::stripTrixAttachmentLinks(
                                                    is_string($state) ? $state : null
                                                );
                                            }),

                                        RichEditor::make('description.it')
                                            ->label('Description (IT)')
                                            ->columnSpan(12)
                                            ->dehydrateStateUsing(function ($state) {
                                                return RichTextSanitizer::stripTrixAttachmentLinks(
                                                    is_string($state) ? $state : null
                                                );
                                            }),
                                    ]),
                                ]),
                        ]),

                    Tab::make('SEO')
                        ->schema([
                            Section::make('SEO')
                                ->schema([
                                    Grid::make(12)->schema([
                                        TextInput::make('meta_title.en')
                                            ->label('Meta title (EN)')
                                            ->columnSpan(6),

                                        TextInput::make('meta_title.it')
                                            ->label('Meta title (IT)')
                                            ->columnSpan(6),

                                        Textarea::make('meta_description.en')
                                            ->label('Meta description (EN)')
                                            ->rows(4)
                                            ->columnSpan(6),

                                        Textarea::make('meta_description.it')
                                            ->label('Meta description (IT)')
                                            ->rows(4)
                                            ->columnSpan(6),
                                    ]),
                                ]),
                        ]),

                    Tab::make('Media')
                        ->schema([
                            Section::make('Images')
                                ->schema([
                                    Grid::make(12)->schema([
                                        CuratorPicker::make('cover_image_id')
                                            ->label('Cover image')
                                            ->columnSpan(12),

                                        TextInput::make('cover_image_alt.en')
                                            ->label('Cover image alt (EN)')
                                            ->columnSpan(6),

                                        TextInput::make('cover_image_alt.it')
                                            ->label('Cover image alt (IT)')
                                            ->columnSpan(6),

                                        Textarea::make('cover_image_caption.en')
                                            ->label('Cover caption / didascalia (EN)')
                                            ->rows(2)
                                            ->maxLength(5000)
                                            ->columnSpan(6),

                                        Textarea::make('cover_image_caption.it')
                                            ->label('Cover caption / didascalia (IT)')
                                            ->rows(2)
                                            ->maxLength(5000)
                                            ->columnSpan(6),
                                    ]),
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
                        ]),

                    Tab::make('OpenGraph')
                        ->schema([
                            Section::make('OpenGraph')
                                ->schema([
                                    Grid::make(12)->schema([
                                        TextInput::make('opengraph_title.en')
                                            ->label('OpenGraph title (EN)')
                                            ->columnSpan(6),

                                        TextInput::make('opengraph_title.it')
                                            ->label('OpenGraph title (IT)')
                                            ->columnSpan(6),

                                        Textarea::make('opengraph_description.en')
                                            ->label('OpenGraph description (EN)')
                                            ->rows(4)
                                            ->columnSpan(6),

                                        Textarea::make('opengraph_description.it')
                                            ->label('OpenGraph description (IT)')
                                            ->rows(4)
                                            ->columnSpan(6),

                                        CuratorPicker::make('opengraph_picture_id')
                                            ->label('OpenGraph picture')
                                            ->columnSpan(12),

                                        TextInput::make('opengraph_picture_alt.en')
                                            ->label('OpenGraph picture alt (EN)')
                                            ->columnSpan(6),

                                        TextInput::make('opengraph_picture_alt.it')
                                            ->label('OpenGraph picture alt (IT)')
                                            ->columnSpan(6),
                                    ]),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->formatStateUsing(function ($state, Project $record) {
                        $locale = app()->getLocale();

                        return $record->getTranslation('title', $locale)
                            ?: ($record->getTranslation('title', 'en') ?: $record->getTranslation('title', 'it'));
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $needle = '%'.mb_strtolower($search, 'UTF-8').'%';

                        return $query->where(function (Builder $q) use ($search, $needle) {
                            $q->whereRaw('LOWER('.$q->qualifyColumn('slug_it').') LIKE ?', [$needle])
                                ->orWhereRaw('LOWER('.$q->qualifyColumn('slug_en').') LIKE ?', [$needle]);
                            CaseInsensitiveJsonColumnSearch::orWhereMatches($q, 'title', $search);
                        });
                    })
                    ->sortable(),

                TextColumn::make('slug_it')
                    ->label('Slug (IT)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('slug_en')
                    ->label('Slug (EN)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'gray' => 'archived',
                    ]),

                IconColumn::make('show_in_homepage')
                    ->label('Homepage')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('homepage_order')
                    ->label('HP order')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('show_in_projects')
                    ->label('Projects')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('projects_order')
                    ->label('Prj order')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'opengraphPicture',
            'coverImage',
            'categories',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
