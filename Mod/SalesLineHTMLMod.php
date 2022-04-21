<?php

namespace FacturaScripts\Plugins\Comisiones\Mod;

use FacturaScripts\Core\Base\Contract\SalesLineModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;

class SalesLineHTMLMod implements SalesLineModInterface
{

    public function apply(SalesDocument &$model, array &$lines, array $formData)
    {
    }

    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id)
    {
    }

    public function assets(): void
    {
    }

    public function map(array $lines, SalesDocument $model): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['porcomision'];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newTitles(): array
    {
        return [];
    }

    public function renderField(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        if ($field === 'porcomision') {
            return $this->porcomision($i18n, $idlinea, $line, $model);
        }
        return null;
    }

    public function renderTitle(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        return null;
    }

    private function porcomision($i18n, $idlinea, $line, $model): string
    {
        if (empty($line->porcomision)) {
            return '';
        }

        return '<div class="col-6">'
            . '<div class="mb-2">' . $i18n->trans('percentage-commission')
            . '<input type="number" value="' . $line->porcomision . '" class="form-control" disabled />'
            . '</div>'
            . '</div>';
    }
}