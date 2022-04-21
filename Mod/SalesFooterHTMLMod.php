<?php

namespace FacturaScripts\Plugins\Comisiones\Mod;

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

class SalesFooterHTMLMod implements SalesModInterface
{

    public function apply(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
    }

    public function assets(): void
    {
    }

    public function newFields(): array
    {
        return ['totalcomision'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === 'totalcomision') {
            return $this->totalcomision($i18n, $model);
        }
        return null;
    }

    private function totalcomision(Translator $i18n, SalesDocument $model): string
    {
        return empty($model->{'totalcomision'}) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('commission')
            . '<input type="number" name="totalcomision" value="' . $model->totalcomision . '" class="form-control" disabled />'
            . '</div>'
            . '</div>';
    }
}