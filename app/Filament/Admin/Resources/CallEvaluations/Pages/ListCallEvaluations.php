<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Pages;

use App\Exports\ReportArrayExport;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationForm;
use App\Filament\Admin\Resources\CallEvaluations\Tables\CallEvaluationsTable;
use App\Models\CallEvaluation;
use App\Support\Exports\CsvExport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCallEvaluations extends ListRecords
{
    protected static string $resource = CallEvaluationResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),
            Action::make('export_csv')
                ->label(__('admin.actions.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => app(CsvExport::class)->stream(
                    $this->exportFilename('csv'),
                    $this->exportHeadings(),
                    $this->exportRows(),
                )),
            Action::make('export_xlsx')
                ->label(__('admin.actions.export_xlsx'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new ReportArrayExport($this->exportHeadings(), $this->exportRows()),
                    $this->exportFilename('xlsx'),
                )),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function exportHeadings(): array
    {
        return CallEvaluationsTable::exportHeadings(CallEvaluationForm::criteria());
    }

    /**
     * The filtered table rows, newest call first.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    private function exportRows(): array
    {
        $criteria = CallEvaluationForm::criteria();

        return (clone $this->getFilteredTableQuery())
            ->reorder()
            ->orderByDesc('call_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CallEvaluation $evaluation): array => CallEvaluationsTable::exportRow($evaluation, $criteria))
            ->all();
    }

    private function exportFilename(string $extension): string
    {
        return 'miran-call-evaluations-'.now()->format('Ymd-His').'.'.$extension;
    }
}
