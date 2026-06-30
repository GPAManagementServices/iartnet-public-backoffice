<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
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

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('FAQ')
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
                                        $current = (string) ($get('slug_en') ?? '');
                                        if ($current === '') {
                                            $set('slug_en', Str::slug((string) $state));
                                        }

                                        // meta_title: se vuoto, copia title
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
                                        // slug: se vuoto, genera da title
                                        $current = (string) ($get('slug_it') ?? '');
                                        if ($current === '') {
                                            $set('slug_it', Str::slug((string) $state));
                                        }

                                        // meta_title: se vuoto, copia title
                                        $currentMeta = (string) ($get('meta_title.it') ?? '');
                                        if ($currentMeta === '' && (string) $state !== '') {
                                            $set('meta_title.it', (string) $state);
                                        }
                                    })
                                    ->columnSpan(6),

                                TextInput::make('slug_en')
                                    ->label('Slug (EN)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug_en', $state ? Str::slug((string) $state) : null))
                                    ->rules(fn (?Faq $record) => [Rule::unique('faqs', 'slug_en')->ignore($record?->id)])
                                    ->columnSpan(6),

                                TextInput::make('slug_it')
                                    ->label('Slug (IT)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug_it', $state ? Str::slug((string) $state) : null))
                                    ->rules(fn (?Faq $record) => [Rule::unique('faqs', 'slug_it')->ignore($record?->id)])
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
                            ]),
                        ]),
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
                    ->formatStateUsing(function ($state, Faq $record) {
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
        return parent::getEloquentQuery()->with(['opengraphPicture']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
