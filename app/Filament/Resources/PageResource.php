<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Support\CaseInsensitiveJsonColumnSearch;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $modelLabel = 'Page';

    protected static ?string $pluralModelLabel = 'Pages';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Page')
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
                                    ->rules(fn (?Page $record) => [Rule::unique('pages', 'slug_en')->ignore($record?->id)])
                                    ->columnSpan(6),

                                TextInput::make('slug_it')
                                    ->label('Slug (IT)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug_it', $state ? Str::slug((string) $state) : null))
                                    ->rules(fn (?Page $record) => [Rule::unique('pages', 'slug_it')->ignore($record?->id)])
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

                                CuratorPicker::make('cover_image_id')
                                    ->label('Cover image')
                                    ->columnSpan(12),

                                TextInput::make('cover_image_alt.en')->label('Cover image alt (EN)')->columnSpan(6),
                                TextInput::make('cover_image_alt.it')->label('Cover image alt (IT)')->columnSpan(6),
                            ]),
                        ]),
                    ]),

                    Tab::make('Description')->schema([
                        Section::make('Description')->schema([
                            Grid::make(12)->schema([
                                RichEditor::make('description.en')
                                    ->label('Description (EN)')
                                    ->columnSpan(12),

                                RichEditor::make('description.it')
                                    ->label('Description (IT)')
                                    ->columnSpan(12),
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

                                CuratorPicker::make('opengraph_picture_id')
                                    ->label('OpenGraph picture')
                                    ->columnSpan(12),

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
                    ->formatStateUsing(function ($state, Page $record) {
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
        return parent::getEloquentQuery()->with(['opengraphPicture', 'coverImage']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
