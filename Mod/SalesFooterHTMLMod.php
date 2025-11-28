<?php
/**
 * This file is part of Comisiones plugin for FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Comisiones\Mod;

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;

class SalesFooterHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData): void
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData): void
    {
    }

    public function assets(): void
    {
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        return Tools::settings('default', 'comissionposition', 'modal') === 'footer'
            ? ['totalcomision']
            : [];
    }

    public function newModalFields(): array
    {
        return Tools::settings('default', 'comissionposition', 'modal') === 'modal'
            ? ['totalcomision']
            : [];
    }

    public function renderField(SalesDocument $model, string $field): ?string
    {
        if ($field === 'totalcomision') {
            return $this->totalcomision($model);
        }

        return null;
    }

    private function totalcomision(SalesDocument $model): string
    {
        if (false === $model->hasColumn('totalcomision')) {
            return '';
        }

        return '<div class="col-sm-6 col-md-4 col-lg">'
            . '<div class="mb-3">'
            . Tools::trans('commission')
            . '<input type="text" name="totalcomision" class="form-control" disabled'
                . ' value="' . Tools::money($model->totalcomision, $model->coddivisa, 2) . '"'
            . '/>'
            . '</div>'
        . '</div>';
    }
}
