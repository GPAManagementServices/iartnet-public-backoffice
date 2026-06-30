<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Support\CaseInsensitiveJsonColumnSearch;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Categories';

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record) {
            return null;
        }

        return (string) $record->getTranslation('name', app()->getLocale())
            ?: 'Category #'.$record->getKey();
    }

    public static function form(Form $form): Form
    {
        $locales = config('translatable.locales') ?? ['it', 'en'];

        // EN first, IT second, others after
        $locales = collect($locales)
            ->sortBy(function (string $locale) {
                if ($locale === 'en') {
                    return 0;
                }
                if ($locale === 'it') {
                    return 1;
                }

                return 2;
            })
            ->values()
            ->toArray();

        return $form->schema([
            Tabs::make('CategoryTabs')->tabs([
                Tabs\Tab::make('General')
                    ->schema([
                        Section::make('Main data')
                            ->schema([
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        Category::TYPE_ACTIVITY => 'Activities',
                                        Category::TYPE_INSTITUTION => 'Institutions',
                                        Category::TYPE_PERSON => 'People',
                                        Category::TYPE_RESEARCH_CATALOGUE => 'Research catalogue',
                                        Category::TYPE_PROJECT => 'Project',
                                    ])
                                    ->required()
                                    ->native(false),

                                self::translatableTextInputs('name', 'Name', $locales),
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
                    ]),

                Tabs\Tab::make('SEO')
                    ->schema([
                        Section::make('Meta')
                            ->schema([
                                self::translatableTextInputs('meta_title', 'Meta title', $locales),
                                self::translatableTextareaInputs('meta_description', 'Meta description', $locales),
                            ])
                            ->columns(1),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function (?string $state) {
                        return match ($state) {
                            Category::TYPE_ACTIVITY => 'Activities',
                            Category::TYPE_INSTITUTION => 'Institutions',
                            Category::TYPE_PERSON => 'People',
                            Category::TYPE_RESEARCH_CATALOGUE => 'Research catalogue',
                            Category::TYPE_PROJECT => 'Project',
                            default => (string) $state,
                        };
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (Category $record) => (string) $record->getTranslation('name', app()->getLocale()))
                    ->searchable(query: fn (Builder $query, string $search): Builder => CaseInsensitiveJsonColumnSearch::whereMatches(
                        $query,
                        'name',
                        $search
                    )),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    // Helpers for translatable fields
    protected static function translatableTextInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()
            ->schema(
                collect($locales)->map(function ($locale) use ($field, $label) {
                    $input = TextInput::make("{$field}.{$locale}")
                        ->label("{$label} (".strtoupper($locale).')')
                        ->maxLength(255);

                    if ($field === 'name') {
                        $input = $input
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($locale) {
                                $currentSlug = $get("slug.$locale");

                                // non sovrascrivere se l'utente ha già compilato lo slug
                                if (! empty($currentSlug)) {
                                    return;
                                }

                                $set("slug.$locale", Str::slug((string) $state));
                            });
                    }

                    if ($field === 'slug') {
                        $input = $input
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) use ($locale) {
                                $set("slug.$locale", Str::slug((string) $state));
                            })
                            ->rule(function (?Model $record, callable $get) use ($locale) {
                                $column = $locale === 'it' ? 'slug_it' : 'slug_en';
                                $type = $get('type');

                                return Rule::unique(static::$model, $column)
                                    ->where('type', $type)
                                    ->ignore($record?->getKey());
                            });
                    }

                    return $input;
                })->toArray()
            );
    }

    protected static function translatableTextareaInputs(string $field, string $label, array $locales): Grid
    {
        return Grid::make()
            ->schema(
                collect($locales)->map(function ($locale) use ($field, $label) {
                    return Textarea::make("{$field}.{$locale}")
                        ->label("{$label} (".strtoupper($locale).')')
                        ->rows(3);
                })->toArray()
            );
    }
}
