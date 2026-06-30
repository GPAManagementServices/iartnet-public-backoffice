<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResearchCatalogueResource\Pages;
use App\Models\Category;
use App\Models\ResearchCatalogue;
use App\Support\CaseInsensitiveJsonColumnSearch;
use App\Support\RichTextSanitizer;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResearchCatalogueResource extends Resource
{
    protected static ?string $model = ResearchCatalogue::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Research catalogue';

    protected static ?string $modelLabel = 'Research catalogue';

    protected static ?string $pluralModelLabel = 'Research catalogue';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('ResearchCatalogue')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Main data')->schema([
                        Section::make('Main data')->schema([
                            Grid::make(12)->schema([
                                TextInput::make('title.en')
                                    ->label('Title (EN)')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // slug: se vuoto, genera da title
                                        if (((string) ($get('slug_en') ?? '')) === '' && (string) $state !== '') {
                                            $set('slug_en', Str::slug((string) $state));
                                        }

                                        // meta_title: se vuoto, copia title
                                        if (((string) ($get('meta_title.en') ?? '')) === '' && (string) $state !== '') {
                                            $set('meta_title.en', (string) $state);
                                        }
                                    })
                                    ->columnSpan(6),

                                TextInput::make('title.it')
                                    ->label('Title (IT)')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // slug: se vuoto, genera da title
                                        if (((string) ($get('slug_it') ?? '')) === '' && (string) $state !== '') {
                                            $set('slug_it', Str::slug((string) $state));
                                        }

                                        // meta_title: se vuoto, copia title
                                        if (((string) ($get('meta_title.it') ?? '')) === '' && (string) $state !== '') {
                                            $set('meta_title.it', (string) $state);
                                        }
                                    })
                                    ->columnSpan(6),

                                TextInput::make('slug_en')
                                    ->label('Slug (EN)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug_en', $state ? Str::slug((string) $state) : null))
                                    ->rules(fn (?ResearchCatalogue $record) => [Rule::unique('research_catalogues', 'slug_en')->ignore($record?->id)])
                                    ->columnSpan(6),

                                TextInput::make('slug_it')
                                    ->label('Slug (IT)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug_it', $state ? Str::slug((string) $state) : null))
                                    ->rules(fn (?ResearchCatalogue $record) => [Rule::unique('research_catalogues', 'slug_it')->ignore($record?->id)])
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
                                    ->columnSpan(4),

                                TextInput::make('author')
                                    ->label('Author')
                                    ->columnSpan(4),

                                TextInput::make('external_link')
                                    ->label('External link')
                                    ->url()
                                    ->columnSpan(4),

                                CuratorPicker::make('cover_image_id')
                                    ->label('Cover image')
                                    ->columnSpan(12),

                                TextInput::make('cover_image_alt.en')->label('Cover image alt (EN)')->columnSpan(6),
                                TextInput::make('cover_image_alt.it')->label('Cover image alt (IT)')->columnSpan(6),

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
                    ]),

                    Tabs\Tab::make('Relations')->schema([
                        Select::make('categories')
                            ->label('Categories')
                            ->multiple()
                            ->relationship(
                                name: 'categories',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('type', Category::TYPE_RESEARCH_CATALOGUE),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', app()->getLocale()))
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->columnSpan(12),
                    ]),

                    Tab::make('Description')->schema([
                        Section::make('Description')->schema([
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

                    Tab::make('SEO')->schema([
                        Section::make('SEO')->schema([
                            Grid::make(12)->schema([
                                TextInput::make('meta_title.en')->label('Meta title (EN)')->columnSpan(6),
                                TextInput::make('meta_title.it')->label('Meta title (IT)')->columnSpan(6),

                                Textarea::make('meta_description.en')->label('Meta description (EN)')->rows(4)->columnSpan(6),
                                Textarea::make('meta_description.it')->label('Meta description (IT)')->rows(4)->columnSpan(6),
                            ]),
                        ]),
                    ]),

                    Tab::make('OpenGraph')->schema([
                        Section::make('OpenGraph')->schema([
                            Grid::make(12)->schema([
                                TextInput::make('opengraph_title.en')->label('OpenGraph title (EN)')->columnSpan(6),
                                TextInput::make('opengraph_title.it')->label('OpenGraph title (IT)')->columnSpan(6),

                                Textarea::make('opengraph_description.en')->label('OpenGraph description (EN)')->rows(4)->columnSpan(6),
                                Textarea::make('opengraph_description.it')->label('OpenGraph description (IT)')->rows(4)->columnSpan(6),

                                CuratorPicker::make('opengraph_picture_id')->label('OpenGraph picture')->columnSpan(12),

                                TextInput::make('opengraph_picture_alt.en')->label('OpenGraph picture alt (EN)')->columnSpan(6),
                                TextInput::make('opengraph_picture_alt.it')->label('OpenGraph picture alt (IT)')->columnSpan(6),
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
                    ->formatStateUsing(function ($state, ResearchCatalogue $record) {
                        $locale = app()->getLocale();

                        return $record->getTranslation('title', $locale)
                            ?: ($record->getTranslation('title', 'en') ?: $record->getTranslation('title', 'it'));
                    })
                    ->searchable(query: fn (Builder $query, string $search): Builder => CaseInsensitiveJsonColumnSearch::whereMatches(
                        $query,
                        'title',
                        $search
                    ))
                    ->sortable(),

                // ✅ VERSIONE BADGE
                TextColumn::make('categories')
                    ->label('Categories')
                    ->state(function (ResearchCatalogue $record) {
                        $locale = app()->getLocale();

                        return $record->categories
                            ->map(fn ($c) => $c->getTranslation('name', $locale))
                            ->filter()
                            ->values()
                            ->all(); // array => badge multipli
                    })
                    ->badge()
                    ->separator(', ')
                    ->toggleable(),

                TextColumn::make('author')
                    ->label('Author')
                    ->toggleable()
                    ->searchable(),

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

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
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
        return parent::getEloquentQuery()->with(['categories', 'opengraphPicture', 'coverImage']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResearchCatalogues::route('/'),
            'create' => Pages\CreateResearchCatalogue::route('/create'),
            'edit' => Pages\EditResearchCatalogue::route('/{record}/edit'),
        ];
    }
}
