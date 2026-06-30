<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageHighlightItemResource\Pages;
use App\Models\HomepageHighlightItem;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class HomepageHighlightItemResource extends Resource
{
    protected static ?string $model = HomepageHighlightItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Homepage';

    protected static ?string $navigationLabel = 'Highlights';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Highlight item')->schema([
                Grid::make(12)->schema([
                    Select::make('title_variant')
                        ->label('Title variant')
                        ->required()
                        ->options([
                            HomepageHighlightItem::TITLE_VARIANT_AUTHOR_TITLE_SUBTITLE => 'Author, title, subtitle',
                            HomepageHighlightItem::TITLE_VARIANT_TITLE_SUBTITLE1_SUBTITLE2 => 'Title, subtitle 1, subtitle 2',
                        ])
                        ->default(HomepageHighlightItem::TITLE_VARIANT_AUTHOR_TITLE_SUBTITLE)
                        ->columnSpan(6),
                    TextInput::make('sort_order')
                        ->label('Sort order')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->columnSpan(3),
                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true)
                        ->columnSpan(3),
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(12),
                    TextInput::make('author')
                        ->label('Author')
                        ->maxLength(255)
                        ->columnSpan(4),
                    TextInput::make('subtitle_1')
                        ->label('Subtitle 1 / institution')
                        ->maxLength(255)
                        ->columnSpan(4),
                    TextInput::make('subtitle_2')
                        ->label('Subtitle 2')
                        ->maxLength(255)
                        ->columnSpan(4),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(6)
                        ->columnSpan(12),
                    TextInput::make('digital_object_slug')
                        ->label('Digital object slug')
                        ->required()
                        ->maxLength(255)
                        ->rules(fn (?HomepageHighlightItem $record) => [
                            Rule::unique('homepage_highlight_items', 'digital_object_slug')->ignore($record?->id),
                        ])
                        ->columnSpan(12),
                    CuratorPicker::make('cover_media_id')
                        ->label('Cover media')
                        ->columnSpan(6),
                    TextInput::make('cover_iiif_identifier')
                        ->label('IIIF image identifier')
                        ->maxLength(255)
                        ->columnSpan(6),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('title')->label('Title')->searchable()->sortable(),
                TextColumn::make('author')->label('Author')->searchable(),
                TextColumn::make('digital_object_slug')->label('Digital object')->searchable(),
                IconColumn::make('cover_media_id')->label('Media')->boolean()->trueIcon('heroicon-o-photo')->falseIcon('heroicon-o-x-mark'),
                TextColumn::make('cover_iiif_identifier')->label('IIIF')->toggleable(),
                ToggleColumn::make('is_published')->label('Published'),
                TextColumn::make('updated_at')->label('Updated')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index' => Pages\ListHomepageHighlightItems::route('/'),
            'create' => Pages\CreateHomepageHighlightItem::route('/create'),
            'edit' => Pages\EditHomepageHighlightItem::route('/{record}/edit'),
        ];
    }
}
